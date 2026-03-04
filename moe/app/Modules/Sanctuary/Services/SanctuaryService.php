<?php

namespace App\Modules\Sanctuary\Services;

use CodeIgniter\Database\BaseConnection;
use App\Modules\Sanctuary\Interfaces\SanctuaryServiceInterface;

/**
 * SanctuaryService
 * 
 * Handles business logic for sanctuary operations including:
 * - Upgrade cost calculation and validation
 * - Daily claim eligibility checks
 * - Reward calculations
 * - Donation validation
 */
class SanctuaryService implements SanctuaryServiceInterface
{
    protected BaseConnection $db;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    // ================================================
    // UPGRADE LOGIC
    // ================================================

    /**
     * Get upgrade configuration (cost, description, icon)
     */
    public function getUpgradeConfig(): array
    {
        return [
            'training_dummy' => [
                'name' => 'Training Dummy',
                'desc' => '+5% Pet EXP from Battles',
                'cost' => 50000,
                'icon' => 'fa-dumbbell'
            ],
            'beastiary' => [
                'name' => 'Beastiary Library',
                'desc' => '+5% Shiny Pet Chance',
                'cost' => 100000,
                'icon' => 'fa-book-open'
            ],
            'crystal_vault' => [
                'name' => 'Crystal Vault',
                'desc' => '+10 Daily Gold Bonus',
                'cost' => 250000,
                'icon' => 'fa-gem'
            ],
        ];
    }

    /**
     * Validate and process upgrade purchase
     * 
     * @return array ['success' => bool, 'message' => string, 'newBalance' => int|null]
     */
    public function purchaseUpgrade(int $sanctuaryId, string $upgradeType, int $currentGold, array $existingUpgrades): array
    {
        $config = $this->getUpgradeConfig();

        // Validation
        if (!isset($config[$upgradeType])) {
            return ['success' => false, 'message' => 'Invalid upgrade type.', 'newBalance' => null];
        }

        if (isset($existingUpgrades[$upgradeType])) {
            return ['success' => false, 'message' => 'Upgrade already purchased.', 'newBalance' => null];
        }

        $cost = $config[$upgradeType]['cost'];
        if ($currentGold < $cost) {
            return ['success' => false, 'message' => 'Not enough Treasury gold!', 'newBalance' => null];
        }

        // Process purchase
        $this->db->query("UPDATE sanctuary SET gold = gold - ? WHERE id_sanctuary = ?", [$cost, $sanctuaryId]);
        $this->db->query("INSERT INTO sanctuary_upgrades (sanctuary_id, upgrade_type, level) VALUES (?, ?, 1)", [$sanctuaryId, $upgradeType]);

        return [
            'success' => true,
            'message' => "Purchased " . $config[$upgradeType]['name'] . "!",
            'newBalance' => $currentGold - $cost
        ];
    }

    // ================================================
    // DAILY CLAIM LOGIC
    // ================================================

    /**
     * Check if user can claim daily reward
     * 
     * @return array ['canClaim' => bool, 'nextClaimTime' => int]
     */
    public function canClaimDaily(int $userId, int $sanctuaryId): array
    {
        $lastClaim = $this->db->query(
            "SELECT last_claim FROM sanctuary_daily_claims WHERE user_id = ? AND sanctuary_id = ?",
            [$userId, $sanctuaryId]
        )->getRowArray();

        if (!$lastClaim) {
            return ['canClaim' => true, 'nextClaimTime' => 0];
        }

        $lastClaimTime = strtotime($lastClaim['last_claim']);
        $nextClaimTime = $lastClaimTime + (24 * 60 * 60);

        return [
            'canClaim' => time() >= $nextClaimTime,
            'nextClaimTime' => $nextClaimTime
        ];
    }

    /**
     * Calculate daily reward based on upgrades and pet happiness
     * 
     * @param array $upgrades Existing sanctuary upgrades
     * @param array|null $activePet Active pet data (hunger, mood)
     * @return array ['totalGold' => int, 'baseGold' => int, 'bonusGold' => int, 'happyBonus' => bool]
     */
    public function calculateDailyReward(array $upgrades, ?array $activePet): array
    {
        $baseGold = 50;
        $bonusGold = 0;
        $happyBonus = false;

        // Crystal Vault upgrade bonus
        if (isset($upgrades['crystal_vault'])) {
            $bonusGold += 10;
        }

        // Happy pet bonus
        if ($activePet && $activePet['hunger'] > 80 && $activePet['mood'] > 80) {
            $bonusGold += 20;
            $happyBonus = true;
        }

        return [
            'totalGold' => $baseGold + $bonusGold,
            'baseGold' => $baseGold,
            'bonusGold' => $bonusGold,
            'happyBonus' => $happyBonus
        ];
    }

    /**
     * Process daily claim (update DB, grant rewards)
     * 
     * @return array ['success' => bool, 'message' => string, 'goldEarned' => int]
     */
    public function processDailyClaim(int $userId, int $sanctuaryId, array $upgrades, ?array $activePet): array
    {
        $eligibility = $this->canClaimDaily($userId, $sanctuaryId);

        if (!$eligibility['canClaim']) {
            return ['success' => false, 'message' => 'Daily reward already claimed today.', 'goldEarned' => 0];
        }

        $reward = $this->calculateDailyReward($upgrades, $activePet);
        $totalGold = $reward['totalGold'];

        // Grant gold
        $this->db->query("UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?", [$totalGold, $userId]);

        // Log transaction
        $this->db->query(
            "INSERT INTO trapeza_transactions (sender_id, receiver_id, amount, transaction_type, description, status) 
             VALUES (0, ?, ?, 'daily_reward', 'Sanctuary Daily Reward', 'completed')",
            [$userId, $totalGold]
        );

        // Update claim record
        $existingClaim = $this->db->query(
            "SELECT id FROM sanctuary_daily_claims WHERE user_id = ? AND sanctuary_id = ?",
            [$userId, $sanctuaryId]
        )->getRowArray();

        if ($existingClaim) {
            $this->db->query(
                "UPDATE sanctuary_daily_claims SET last_claim = NOW() WHERE user_id = ? AND sanctuary_id = ?",
                [$userId, $sanctuaryId]
            );
        } else {
            $this->db->query(
                "INSERT INTO sanctuary_daily_claims (user_id, sanctuary_id, last_claim) VALUES (?, ?, NOW())",
                [$userId, $sanctuaryId]
            );
        }

        $message = "Claimed {$totalGold} Gold!";
        if ($reward['happyBonus']) {
            $message .= " 🌟 Happy Bonus!";
        }

        return ['success' => true, 'message' => $message, 'goldEarned' => $totalGold];
    }

    // ================================================
    // DONATION LOGIC
    // ================================================

    /**
     * Validate and process donation to sanctuary treasury
     * 
     * @return array ['success' => bool, 'message' => string, 'newUserGold' => int|null, 'newSanctuaryGold' => int|null]
     */
    public function processDonation(int $userId, int $sanctuaryId, int $amount, int $userGold, int $sanctuaryGold): array
    {
        // Validation — early return before touching DB
        if ($amount < 10) {
            return ['success' => false, 'message' => 'Minimum donation is 10 Gold.', 'newUserGold' => null, 'newSanctuaryGold' => null];
        }

        if ($amount > 100000) {
            return ['success' => false, 'message' => 'Maximum donation is 100,000 Gold.', 'newUserGold' => null, 'newSanctuaryGold' => null];
        }

        // Use DB transaction to ensure atomicity
        $this->db->transStart();

        // Fetch fresh gold with row lock to prevent race conditions
        $freshUser = $this->db->query("SELECT gold FROM nethera WHERE id_nethera = ? FOR UPDATE", [$userId])->getRowArray();
        $userGold = (int) ($freshUser['gold'] ?? 0);

        if ($amount > $userGold) {
            $this->db->transRollback();
            return ['success' => false, 'message' => 'Not enough gold!', 'newUserGold' => null, 'newSanctuaryGold' => null];
        }

        // Process donation atomically
        $this->db->query("UPDATE nethera SET gold = gold - ? WHERE id_nethera = ?", [$amount, $userId]);
        $this->db->query("UPDATE sanctuary SET gold = gold + ? WHERE id_sanctuary = ?", [$amount, $sanctuaryId]);

        // Log transaction
        $this->db->query(
            "INSERT INTO trapeza_transactions (sender_id, receiver_id, amount, transaction_type, description, status) 
             VALUES (?, 0, ?, 'donation', 'Sanctuary Donation', 'completed')",
            [$userId, $amount]
        );

        $this->db->transComplete();

        // Check transaction status
        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Donation failed. Please try again.', 'newUserGold' => null, 'newSanctuaryGold' => null];
        }

        return [
            'success' => true,
            'message' => "Donated {$amount} Gold to the Treasury!",
            'newUserGold' => $userGold - $amount,
            'newSanctuaryGold' => $sanctuaryGold + $amount
        ];
    }
}
