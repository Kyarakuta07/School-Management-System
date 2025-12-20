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

        // Get all achievements with user unlock status
        $query = "SELECT a.*, ua.unlocked_at
                  FROM achievements a
                  LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
                  ORDER BY a.category, a.requirement_value";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $achievements = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['unlocked'] = !is_null($row['unlocked_at']);
            $achievements[] = $row;
        }
        mysqli_stmt_close($stmt);

        // Get user progress for achievement tracking
        $progress = $this->getAchievementProgress();

        $this->success([
            'achievements' => $achievements,
            'progress' => $progress
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

        // Pet collection
        $stmt = mysqli_prepare($this->conn, "SELECT COUNT(*) as count FROM user_pets WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['pets_owned'] = (int) $row['count'];
        mysqli_stmt_close($stmt);

        // Gold balance
        $progress['current_gold'] = $this->getUserGold();

        // Max pet level
        $stmt = mysqli_prepare($this->conn, "SELECT MAX(level) as max_level FROM user_pets WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $progress['max_pet_level'] = (int) ($row['max_level'] ?? 0);
        mysqli_stmt_close($stmt);

        return $progress;
    }
}
