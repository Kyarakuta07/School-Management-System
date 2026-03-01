<?php

namespace App\Modules\Sanctuary\Services;

use CodeIgniter\Database\ConnectionInterface;

/**
 * RewardService
 * 
 * Handles business logic for reward operations including:
 * - Daily login rewards and streak tracking
 * - Reward calculation based on days
 * - Item reward assignment
 */
class RewardService
{
    protected ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    // ================================================
    // DAILY REWARD LOGIC
    // ================================================

    /**
     * Get daily reward status for user
     * 
     * @return array ['success' => bool, 'can_claim' => bool, 'current_day' => int, 'reward_gold' => int]
     */
    public function getDailyStatus(int $userId): array
    {
        $today = date('Y-m-d');

        // Get or create user streak record
        $streak = $this->getOrCreateStreak($userId);

        // Check if already claimed today
        if ($streak['last_claim_date'] === $today) {
            return [
                'success' => true,
                'can_claim' => false,
                'message' => 'Already claimed reward today! Come back tomorrow.',
                'current_day' => $streak['current_day'],
                'total_logins' => $streak['total_logins']
            ];
        }

        // Calculate current day (reset to 1 after day 30)
        $currentDay = $streak['current_day'];

        // Check if streak was broken (more than 1 day gap)
        if ($streak['last_claim_date']) {
            $lastClaim = strtotime($streak['last_claim_date']);
            $todayTimestamp = strtotime($today);
            $dayDiff = ($todayTimestamp - $lastClaim) / 86400;

            if ($dayDiff > 1) {
                // Streak broken, reset to day 1
                $currentDay = 1;
            }
        }

        // Calculate reward for current day
        $reward = $this->calculateDailyReward($currentDay);

        return [
            'success' => true,
            'can_claim' => true,
            'current_day' => $currentDay,
            'total_logins' => $streak['total_logins'],
            'reward_gold' => $reward['gold'],
            'reward_item_id' => $reward['item_id'] ?? null,
            'reward_item_name' => $reward['item_name'] ?? null
        ];
    }

    /**
     * Claim daily reward for user.
     * Guard check + mutation in single TX with FOR UPDATE lock to prevent double-claim.
     * 
     * @return array ['success' => bool, 'claimed_day' => int, 'gold_received' => int]
     */
    public function claimDaily(int $userId): array
    {
        $today = date('Y-m-d');

        $this->db->transBegin();

        try {
            // 1. Lock the streak record and check if can claim — inside TX
            $streak = $this->db->query(
                "SELECT * FROM daily_login_streak WHERE user_id = ? FOR UPDATE",
                [$userId]
            )->getRowArray();

            // Create if not exists (still inside TX)
            if (!$streak) {
                $this->db->query(
                    "INSERT INTO daily_login_streak (user_id, current_day, last_claim_date, total_logins) VALUES (?, 1, NULL, 0)",
                    [$userId]
                );
                $streak = $this->db->query(
                    "SELECT * FROM daily_login_streak WHERE user_id = ? FOR UPDATE",
                    [$userId]
                )->getRowArray();
            }

            if (!$streak) {
                $this->db->transRollback();
                return ['success' => false, 'error' => 'Failed to get streak record'];
            }

            // 2. Check already claimed today (INSIDE tx, with lock held)
            if ($streak['last_claim_date'] === $today) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'error' => 'Already claimed reward today! Come back tomorrow.'
                ];
            }

            // 3. Calculate current day (handle streak break)
            $currentDay = (int) $streak['current_day'];
            if ($streak['last_claim_date']) {
                $lastClaim = strtotime($streak['last_claim_date']);
                $todayTimestamp = strtotime($today);
                $dayDiff = ($todayTimestamp - $lastClaim) / 86400;
                if ($dayDiff > 1) {
                    $currentDay = 1; // Streak broken
                }
            }

            // 4. Calculate reward
            $reward = $this->calculateDailyReward($currentDay);
            $nextDay = ($currentDay >= 30) ? 1 : ($currentDay + 1);

            // 5. Update streak record
            $this->db->query(
                "UPDATE daily_login_streak 
                 SET current_day = ?, last_claim_date = ?, total_logins = total_logins + 1 
                 WHERE user_id = ?",
                [$nextDay, $today, $userId]
            );

            // 6. Give gold reward
            $goldReceived = 0;
            if ($reward['gold'] > 0) {
                $this->db->query("UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?", [$reward['gold'], $userId]);
                $goldReceived = $reward['gold'];
            }

            // 7. Give item reward (if applicable)
            $itemReceived = null;
            if ($reward['item_id']) {
                $this->db->query(
                    "INSERT INTO user_inventory (user_id, item_id, quantity) 
                     VALUES (?, ?, 1)
                     ON DUPLICATE KEY UPDATE quantity = quantity + 1",
                    [$userId, $reward['item_id']]
                );
                $itemReceived = $reward['item_name'];
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'claimed_day' => $currentDay,
                'gold_received' => $goldReceived,
                'item_received' => $itemReceived,
                'next_day' => $nextDay,
                'total_logins' => (int) $streak['total_logins'] + 1
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            return [
                'success' => false,
                'error' => 'Failed to claim reward: ' . $e->getMessage()
            ];
        }
    }

    // ================================================
    // PRIVATE HELPERS
    // ================================================

    /**
     * Get or create user daily login streak record
     */
    protected function getOrCreateStreak(int $userId): array
    {
        try {
            log_message('debug', '[RewardService] getOrCreateStreak for UserID: ' . $userId);

            // Safety check for table existence using a fallback query
            // since ConnectionInterface may not have tableExists() in all CI4 versions
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'daily_login_streak'")->getRowArray();
            if (!$tableCheck) {
                log_message('error', '[RewardService] Table daily_login_streak does not exist');
                throw new \RuntimeException('daily_login_streak table missing');
            }

            log_message('debug', '[RewardService] Table daily_login_streak verified');

            $streak = $this->db->query(
                "SELECT * FROM daily_login_streak WHERE user_id = ?",
                [$userId]
            )->getRowArray();

            log_message('debug', '[RewardService] Streak record check: ' . ($streak ? 'found' : 'missing'));

            // Create new record if doesn't exist
            if (!$streak) {
                log_message('debug', '[RewardService] Creating streak record for UserID: ' . $userId);
                $this->db->query(
                    "INSERT INTO daily_login_streak (user_id, current_day, last_claim_date, total_logins) VALUES (?, 1, NULL, 0)",
                    [$userId]
                );

                $streak = $this->db->query(
                    "SELECT * FROM daily_login_streak WHERE user_id = ?",
                    [$userId]
                )->getRowArray();

                log_message('debug', '[RewardService] Streak record created: ' . ($streak ? 'success' : 'failed'));
            }

            return $streak ?? [
                'user_id' => $userId,
                'current_day' => 1,
                'last_claim_date' => null,
                'total_logins' => 0
            ];
        } catch (\Throwable $e) {
            log_message('error', '[RewardService] Streak error (' . get_class($e) . '): ' . $e->getMessage());
            // Return default values so the API doesn't crash
            return [
                'user_id' => $userId,
                'current_day' => 1,
                'last_claim_date' => null,
                'total_logins' => 0
            ];
        }
    }

    /**
     * Calculate reward for specific day (1-30)
     * 
     * @return array ['gold' => int, 'item_id' => int|null, 'item_name' => string|null]
     */
    protected function calculateDailyReward(int $day): array
    {
        // HARDCORE ECONOMY: Reduced daily gold rewards
        // Base formula: 5 + floor(day * 1.5)
        $goldReward = 5 + floor($day * 1.5);

        // Special rewards on milestone days (also nerfed)
        $itemReward = null;
        $itemName = null;

        switch ($day) {
            case 7:  // Week 1
                $goldReward = 20;
                $itemName = 'Basic Food Pack';
                break;
            case 14: // Week 2
                $goldReward = 35;
                $itemName = 'Health Potion';
                break;
            case 21: // Week 3
                $goldReward = 50;
                $itemName = 'Evolution Stone';
                break;
            case 28: // Week 4
                $goldReward = 75;
                $itemName = 'Rare Gacha Ticket';
                break;
            case 30: // Month complete
                $goldReward = 100;
                $itemName = 'Epic Gacha Ticket';
                break;
        }

        // Get item ID if item name is set
        if ($itemName) {
            $itemReward = $this->getItemIdByName($itemName);
        }

        return [
            'gold' => $goldReward,
            'item_id' => $itemReward,
            'item_name' => $itemName
        ];
    }

    /**
     * Get item ID by name from shop_items table
     */
    protected function getItemIdByName(string $itemName): ?int
    {
        $result = $this->db->query(
            "SELECT id FROM shop_items WHERE name = ? LIMIT 1",
            [$itemName]
        )->getRowArray();

        return $result ? (int) $result['id'] : null;
    }
}
