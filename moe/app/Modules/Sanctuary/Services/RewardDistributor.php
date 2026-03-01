<?php

namespace App\Modules\Sanctuary\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * RewardDistributor
 * 
 * Handles relational-safe reward distribution.
 * 
 * RAW method (no own TX): caller must wrap in their own transaction.
 * Uses GoldService.addGoldRaw() to avoid nested transactions.
 */
class RewardDistributor
{
    protected BaseConnection $db;
    protected \App\Modules\User\Services\GoldService $goldService;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
        $this->goldService = service('goldService');
    }

    /**
     * Apply rewards to a user — raw version WITHOUT own transaction.
     * Caller MUST wrap in their own transBegin/transCommit.
     * 
     * @param int $userId Target user
     * @param int $gold Gold amount
     * @param int $exp EXP amount (handled in BattleRepository for pets)
     * @param string $source Reward description
     * @return bool
     */
    public function applyRewards(int $userId, int $gold, int $exp, string $source): bool
    {
        if ($gold <= 0 && $exp <= 0)
            return true;

        // Apply Gold via GoldService raw method (no nested TX)
        if ($gold > 0) {
            $success = $this->goldService->addGoldRaw($userId, $gold, 'battle_reward', $source);
            if (!$success) {
                log_message('error', "[RewardDistributor] GoldService failed to add $gold to user $userId.");
                return false;
            }
        }

        // Note: EXP for the pet is handled in BattleRepository::finishBattle.
        // If we ever have User EXP, it would go here.

        return true;
    }
}
