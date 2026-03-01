<?php

namespace App\Modules\Sanctuary\Services;

use CodeIgniter\Database\BaseConnection;
use App\Modules\Pet\Models\AchievementModel;
use App\Modules\Pet\Interfaces\PetServiceInterface;

/**
 * AchievementService
 * 
 * Handles achievement tracking, unlocking, and reward claiming.
 * 
 * F5/F7 fix: No longer imports BattleModel or UserModel directly.
 * - Battle win counts → raw DB query on pet_battles table (read-only)
 * - Gold balance → service('goldService') via GoldServiceInterface
 * - Activity logging → service('activityLog')
 */
class AchievementService
{
    protected BaseConnection $db;
    protected AchievementModel $achievementModel;
    protected PetServiceInterface $petService;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
        $this->achievementModel = new AchievementModel();
        $this->petService = service('petService');
    }

    /**
     * Trigger a check for achievements of a specific type
     */
    public function triggerCheck(int $userId, string $type): array
    {
        $unlocked = [];
        $progress = $this->getProgressByType($userId, $type);

        // Get locked achievements of this type
        $locked = $this->db->table('achievements a')
            ->select('a.*')
            ->join('user_achievements ua', "ua.achievement_id = a.id AND ua.user_id = $userId", 'left')
            ->where('a.requirement_type', $type)
            ->where('ua.unlocked_at IS NULL')
            ->get()->getResultArray();

        foreach ($locked as $achievement) {
            if ($progress >= (int) $achievement['requirement_value']) {
                $this->achievementModel->unlock($userId, (int) $achievement['id']);
                $unlocked[] = $achievement;
            }
        }

        return $unlocked;
    }

    /**
     * Get user's comprehensive achievement progress.
     * Consolidated: uses local DB queries instead of cross-module model imports.
     */
    public function getAllProgress(int $userId): array
    {
        // Query 1: Pet stats in single aggregate query
        $petRow = $this->db->query(
            "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN up.is_shiny = 1 THEN 1 ELSE 0 END) AS shiny,
                MAX(up.level) AS max_level,
                SUM(CASE WHEN ps.rarity = 'Rare' THEN 1 ELSE 0 END) AS rare_count,
                SUM(CASE WHEN ps.rarity = 'Epic' THEN 1 ELSE 0 END) AS epic_count,
                SUM(CASE WHEN ps.rarity = 'Legendary' THEN 1 ELSE 0 END) AS legendary_count
             FROM user_pets up
             JOIN pet_species ps ON ps.id = up.species_id
             WHERE up.user_id = ?",
            [$userId]
        )->getRowArray();

        // Query 2: Battle wins (local query — no BattleModel import)
        // pet_battles uses winner_pet_id (pet-based), must join user_pets to get user wins
        $battleWins = (int) $this->db->table('pet_battles pb')
            ->join('user_pets up', 'up.id = pb.winner_pet_id')
            ->where('up.user_id', $userId)
            ->countAllResults();

        // Query 3: Gold + login streak in one round-trip (no UserModel import)
        $userLoginRow = $this->db->query(
            "SELECT n.gold, dls.current_day, dls.total_logins
             FROM nethera n
             LEFT JOIN daily_login_streak dls ON dls.user_id = n.id_nethera
             WHERE n.id_nethera = ?",
            [$userId]
        )->getRowArray();

        $progress = [
            'battle_wins' => $battleWins,
            'pets_owned' => (int) ($petRow['total'] ?? 0),
            'max_pet_level' => (int) ($petRow['max_level'] ?? 0),
            'shiny_count' => (int) ($petRow['shiny'] ?? 0),
            'rare_count' => (int) ($petRow['rare_count'] ?? 0),
            'epic_count' => (int) ($petRow['epic_count'] ?? 0),
            'legendary_count' => (int) ($petRow['legendary_count'] ?? 0),
            'current_gold' => (int) ($userLoginRow['gold'] ?? 0),
            'login_streak' => (int) ($userLoginRow['current_day'] ?? 0),
            'total_logins' => (int) ($userLoginRow['total_logins'] ?? 0),
        ];

        // Legacy aliases
        $progress['pet_count'] = $progress['pets_owned'];
        $progress['max_level'] = $progress['max_pet_level'];
        $progress['total_gold_earned'] = $progress['current_gold'];

        return $progress;
    }

    /**
     * Claim an achievement reward.
     */
    public function claimReward(int $userId, int $achievementId): array
    {
        $this->db->transBegin();

        try {
            $record = $this->db->query(
                "SELECT ua.*, a.reward_gold, a.name 
                 FROM user_achievements ua
                 JOIN achievements a ON a.id = ua.achievement_id
                 WHERE ua.user_id = ? AND ua.achievement_id = ?
                 FOR UPDATE",
                [$userId, $achievementId]
            )->getRowArray();

            if (!$record) {
                $this->db->transRollback();
                return ['success' => false, 'error' => 'Achievement not unlocked'];
            }
            if ($record['claimed']) {
                $this->db->transRollback();
                return ['success' => false, 'error' => 'Reward already claimed'];
            }

            $this->achievementModel->claim($userId, $achievementId);

            $goldReward = (int) $record['reward_gold'];
            if ($goldReward > 0) {
                service('goldService')->addGoldRaw($userId, $goldReward, 'daily_reward', "Achievement: " . $record['name']);
            }

            service('activityLog')->log('CLAIM_REWARD', 'ACHIEVEMENT', "Claimed reward for '{$record['name']}' ({$goldReward} gold)", $userId);

            $this->db->transCommit();

            return [
                'success' => true,
                'gold_earned' => $goldReward,
                'achievement_name' => $record['name']
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return ['success' => false, 'error' => 'Failed to claim reward'];
        }
    }

    // ================================================
    // PRIVATE HELPERS
    // ================================================

    protected function getProgressByType(int $userId, string $type): int
    {
        switch ($type) {
            case 'battle_wins':
                // Local query instead of BattleModel::countUserWins()
                // pet_battles uses winner_pet_id, join user_pets to get user wins
                return (int) $this->db->table('pet_battles pb')
                    ->join('user_pets up', 'up.id = pb.winner_pet_id')
                    ->where('up.user_id', $userId)
                    ->countAllResults();
            case 'pets_owned':
            case 'pet_count':
                return (int) $this->db->table('user_pets')->where('user_id', $userId)->countAllResults();
            case 'max_pet_level':
            case 'max_level':
                return $this->getMaxPetLevel($userId);
            case 'shiny_count':
                return (int) $this->db->table('user_pets')->where('user_id', $userId)->where('is_shiny', 1)->countAllResults();
            case 'current_gold':
            case 'total_gold_earned':
                // Local query instead of UserModel::getGold()
                $row = $this->db->table('nethera')->select('gold')->where('id_nethera', $userId)->get()->getRowArray();
                return (int) ($row['gold'] ?? 0);
            default:
                return 0;
        }
    }

    protected function getMaxPetLevel(int $userId): int
    {
        $row = $this->db->table('user_pets')->selectMax('level', 'max_level')
            ->where('user_id', $userId)
            ->get()->getRowArray();
        return (int) ($row['max_level'] ?? 0);
    }

    protected function getSpeciesCountByRarity(int $userId, string $rarity): int
    {
        return (int) $this->db->table('user_pets up')
            ->join('pet_species ps', 'ps.id = up.species_id')
            ->where('up.user_id', $userId)
            ->where('ps.rarity', $rarity)
            ->countAllResults();
    }
}
