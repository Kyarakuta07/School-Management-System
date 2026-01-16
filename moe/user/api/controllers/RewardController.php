<?php
/**
 * MOE Pet System - Reward Controller
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles reward-related endpoints:
 * - get_daily_reward: Check daily reward status
 * - claim_daily_reward: Claim daily reward
 * - get_achievements: Get user achievements
 */

require_once __DIR__ . '/../BaseController.php';

class RewardController extends BaseController
{
    /**
     * GET: Check daily reward status
     */
    public function getDailyReward()
    {
        $this->requireGet();

        $result = getDailyRewardStatus($this->conn, $this->user_id);
        echo json_encode($result);
    }

    /**
     * POST: Claim daily reward
     */
    public function claimDailyReward()
    {
        $this->requirePost();

        $result = claimDailyReward($this->conn, $this->user_id);

        if ($result['success'] && isset($result['gold_reward'])) {
            $this->logGoldTransaction(0, $this->user_id, $result['gold_reward'], 'daily_reward', 'Daily login reward');
        }

        echo json_encode($result);
    }

    /**
     * GET: Get user achievements
     */
    public function getAchievements()
    {
        $this->requireGet();

        // Get all achievements with user unlock and claim status
        $query = "SELECT a.*, ua.unlocked_at, ua.claimed
                  FROM achievements a
                  LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
                  ORDER BY a.category, a.requirement_value";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Get user progress for achievement tracking
        $progress = $this->getAchievementProgress();

        $achievements = [];
        $unlocked = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $row['unlocked'] = !is_null($row['unlocked_at']);
            $row['claimed'] = (bool) ($row['claimed'] ?? false);

            // Add current progress based on requirement_type
            $reqType = $row['requirement_type'];
            $row['current_progress'] = $progress[$reqType] ?? 0;

            // Check if should be unlocked but isn't yet
            if (!$row['unlocked'] && $row['current_progress'] >= $row['requirement_value']) {
                // Auto-unlock achievement
                $this->unlockAchievement($row['id']);
                $row['unlocked'] = true;
            }

            if ($row['unlocked'])
                $unlocked++;
            $achievements[] = $row;
        }
        mysqli_stmt_close($stmt);

        $this->success([
            'achievements' => $achievements,
            'progress' => $progress,
            'unlocked' => $unlocked,
            'total' => count($achievements)
        ]);
    }

    /**
     * Get achievement progress data
     */
    private function getAchievementProgress()
    {
        $progress = [];

        // Battle wins
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as count 
             FROM pet_battles pb
             JOIN user_pets up ON pb.attacker_pet_id = up.id
             WHERE up.user_id = ? AND pb.winner_pet_id = pb.attacker_pet_id"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['battle_wins'] = (int) ($row['count'] ?? 0);
        mysqli_stmt_close($stmt);

        // Pet collection count
        $stmt = mysqli_prepare($this->conn, "SELECT COUNT(*) as count FROM user_pets WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['pets_owned'] = (int) ($row['count'] ?? 0);
        $progress['pet_count'] = $progress['pets_owned']; // Alias for achievement matching
        mysqli_stmt_close($stmt);

        // Max pet level
        $stmt = mysqli_prepare($this->conn, "SELECT MAX(level) as max_level FROM user_pets WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['max_pet_level'] = (int) ($row['max_level'] ?? 0);
        $progress['max_level'] = $progress['max_pet_level']; // Alias
        mysqli_stmt_close($stmt);

        // Shiny pets count
        $stmt = mysqli_prepare($this->conn, "SELECT COUNT(*) as count FROM user_pets WHERE user_id = ? AND is_shiny = 1");
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['shiny_count'] = (int) ($row['count'] ?? 0);
        mysqli_stmt_close($stmt);

        // Rare pets count
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as count FROM user_pets up 
             JOIN pet_species ps ON up.species_id = ps.id 
             WHERE up.user_id = ? AND ps.rarity = 'Rare'"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['rare_count'] = (int) ($row['count'] ?? 0);
        mysqli_stmt_close($stmt);

        // Epic pets count
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as count FROM user_pets up 
             JOIN pet_species ps ON up.species_id = ps.id 
             WHERE up.user_id = ? AND ps.rarity = 'Epic'"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['epic_count'] = (int) ($row['count'] ?? 0);
        mysqli_stmt_close($stmt);

        // Legendary pets count
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as count FROM user_pets up 
             JOIN pet_species ps ON up.species_id = ps.id 
             WHERE up.user_id = ? AND ps.rarity = 'Legendary'"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['legendary_count'] = (int) ($row['count'] ?? 0);
        mysqli_stmt_close($stmt);

        // Login streak
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT current_day, total_logins FROM daily_login_streak WHERE user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['login_streak'] = (int) ($row['current_day'] ?? 0);
        $progress['total_logins'] = (int) ($row['total_logins'] ?? 0);
        mysqli_stmt_close($stmt);

        // Gold balance
        $progress['current_gold'] = $this->getUserGold();
        $progress['total_gold_earned'] = $progress['current_gold']; // Simplified

        // Gacha rolls (count from gold transactions or estimate)
        $progress['gacha_rolls'] = 0; // Will need a separate counter table

        // Pets revived
        $progress['pets_revived'] = 0; // Will need tracking

        // Rhythm games completed
        $progress['rhythm_games'] = 0; // Will need tracking

        return $progress;
    }

    /**
     * Helper: Unlock an achievement for user
     */
    private function unlockAchievement($achievement_id)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT IGNORE INTO user_achievements (user_id, achievement_id, unlocked_at, claimed) VALUES (?, ?, NOW(), 0)"
        );
        mysqli_stmt_bind_param($stmt, "ii", $this->user_id, $achievement_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /**
     * POST: Claim achievement reward
     */
    public function claimAchievement()
    {
        $this->requirePost();

        $input = $this->getInput();
        $achievement_id = isset($input['achievement_id']) ? (int) $input['achievement_id'] : 0;

        if (!$achievement_id) {
            $this->error('Achievement ID required');
            return;
        }

        // Check if achievement is unlocked and not yet claimed
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT ua.*, a.reward_gold, a.name 
             FROM user_achievements ua
             JOIN achievements a ON ua.achievement_id = a.id
             WHERE ua.user_id = ? AND ua.achievement_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $this->user_id, $achievement_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $record = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$record) {
            $this->error('Achievement not unlocked yet');
            return;
        }

        if ($record['claimed']) {
            $this->error('Already claimed this reward');
            return;
        }

        // Claim the reward
        mysqli_begin_transaction($this->conn);
        try {
            // Mark as claimed
            $stmt = mysqli_prepare(
                $this->conn,
                "UPDATE user_achievements SET claimed = 1 WHERE user_id = ? AND achievement_id = ?"
            );
            mysqli_stmt_bind_param($stmt, "ii", $this->user_id, $achievement_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Give gold reward
            $gold = (int) $record['reward_gold'];
            if ($gold > 0) {
                $this->addGold($gold);
                $this->logGoldTransaction(0, $this->user_id, $gold, 'achievement', 'Achievement: ' . $record['name']);
            }

            mysqli_commit($this->conn);

            $this->success([
                'gold_earned' => $gold,
                'new_balance' => $this->getUserGold()
            ], "Claimed {$gold} gold from achievement!");

        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            $this->error('Failed to claim reward');
        }
    }
}
