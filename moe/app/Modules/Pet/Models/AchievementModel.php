<?php

namespace App\Modules\Pet\Models;

use CodeIgniter\Model;

class AchievementModel extends Model
{
    protected $table = 'achievements';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'name',
        'description',
        'requirement_type',
        'requirement_value',
        'reward_gold',
        'icon'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get achievements for a user with their unlock status
     */
    public function getUserAchievements(int $userId)
    {
        return $this->db->table('achievements a')
            ->select('a.*, ua.unlocked_at, ua.claimed')
            ->join('user_achievements ua', "ua.achievement_id = a.id AND ua.user_id = $userId", 'left')
            ->get()
            ->getResultArray();
    }

    /**
     * Unlock an achievement for a user
     */
    public function unlock(int $userId, int $achievementId)
    {
        // INSERT IGNORE for idempotent unlock — concurrent triggers won't duplicate (F12 fix)
        return $this->db->query(
            "INSERT IGNORE INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?, ?, ?)",
            [$userId, $achievementId, date('Y-m-d H:i:s')]
        );
    }

    /**
     * Mark an achievement as claimed
     */
    public function claim(int $userId, int $achievementId)
    {
        return $this->db->table('user_achievements')
            ->where('user_id', $userId)
            ->where('achievement_id', $achievementId)
            ->update(['claimed' => 1]);
    }

    /**
     * Batch-unlock multiple achievements in a single transaction.
     * Replaces N individual INSERT calls with one insertBatch.
     */
    public function batchUnlock(int $userId, array $achievementIds): void
    {
        if (empty($achievementIds))
            return;

        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        // Use INSERT IGNORE for idempotent unlock — prevents duplicate key errors
        // from concurrent achievement triggers (F12 fix)
        foreach ($achievementIds as $id) {
            $this->db->query(
                "INSERT IGNORE INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?, ?, ?)",
                [$userId, (int) $id, $now]
            );
        }

        $this->db->transComplete();
    }
}
