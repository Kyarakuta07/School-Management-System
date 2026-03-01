<?php

namespace App\Modules\Battle\Services;

use App\Modules\Battle\Engines\Battle3v3Engine;
use App\Modules\Battle\Repositories\BattleRepository;
use CodeIgniter\Database\BaseConnection;

/**
 * Arena3v3Service
 * 
 * Domain logic for 3v3 Arena battles.
 * Handles validation and session orchestration.
 */
class Arena3v3Service extends BaseArenaService
{
    protected Battle3v3Engine $engine;
    protected BattleRepository $repository;
    protected \App\Modules\Sanctuary\Services\RewardDistributor $rewardDistributor;

    public function __construct(BaseConnection $db)
    {
        parent::__construct($db);
        $this->engine = service('battle3v3Engine');
        $this->repository = service('battleRepository', $db);
        $this->rewardDistributor = service('rewardDistributor');
    }

    /**
     * Acquire an advisory file lock for a specific battle.
     * Prevents concurrent cache read-modify-write race conditions.
     *
     * @return resource|false Lock file handle (pass to releaseBattleLock), or false on failure
     */
    protected function acquireBattleLock(string $battleId)
    {
        $lockDir = WRITEPATH . 'cache/locks';
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0777, true);
        }
        $lockFile = $lockDir . '/' . md5($battleId) . '.lock';
        $fp = fopen($lockFile, 'c');
        if ($fp && flock($fp, LOCK_EX)) {
            return $fp;
        }
        if ($fp)
            fclose($fp);
        return false;
    }

    protected function releaseBattleLock($fp): void
    {
        if (is_resource($fp)) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function initBattle(int $userId, array $playerPetIds, ?int $opponentUserId = null): array
    {
        // F14 fix: Prevent duplicate active sessions (multi-tab / cache loss orphan protection)
        $existingSession = $this->db->query(
            "SELECT id FROM battle_sessions WHERE current_turn_user_id = ? AND battle_type = '3v3' AND status = 'active' LIMIT 1",
            [$userId]
        )->getRowArray();

        if ($existingSession) {
            // Mark the orphaned session as abandoned so user can start fresh
            $this->db->table('battle_sessions')
                ->where('id', $existingSession['id'])
                ->update(['status' => 'abandoned']);
            // Also clean up any lingering cache
            $oldBattleId = 'b3v3_' . $existingSession['id'] . '_' . $userId;
            \Config\Services::cache()->delete($oldBattleId);
        }

        // 1. Validation
        foreach ($playerPetIds as $id) {
            $pet = $this->getPetForBattle($id);
            if (!$pet || $pet['user_id'] != $userId) {
                throw new \RuntimeException("Unauthorized or invalid pet: $id");
            }
        }
        // 2. Format Player Team & Fetch Skills
        $playerPets = [];
        foreach ($playerPetIds as $id) {
            $pet = $this->getPetForBattle($id);
            $formatted = $this->formatPetForArena($pet);
            $formatted['skills'] = $this->getPetSkills($pet['species_id']);
            $playerPets[] = $formatted;
        }

        // 3. Identify Opponents
        $enemyPets = [];
        if ($opponentUserId) {
            $oppPets = $this->db->table('user_pets')
                ->where('user_id', $opponentUserId)
                ->where('status', 'ALIVE')
                ->limit(3)
                ->get()->getResultArray();
            foreach ($oppPets as $p) {
                $petData = $this->getPetForBattle($p['id']);
                $formatted = $this->formatPetForArena($petData);
                $formatted['skills'] = $this->getPetSkills($petData['species_id']);
                $enemyPets[] = $formatted;
            }
        }

        if (count($enemyPets) < 3) {
            $avgLevel = (int) (array_sum(array_column($playerPets, 'level')) / count($playerPets));
            $aiEnemies = $this->generateAIOpponents(3 - count($enemyPets), $avgLevel);
            foreach ($aiEnemies as $ai) {
                $ai['skills'] = $this->getPetSkills($ai['species_id']);
                $enemyPets[] = $ai;
            }
        }

        // 4. Session Creation (via Repository) - Pass all 6 pets to satisfy Ph6 (6 rows)
        $dbBattleId = $this->repository->createSession('3v3', $userId, $playerPetIds, $enemyPets);

        $opponentName = 'Wild Trainer';
        if ($opponentUserId) {
            $opp = $this->db->table('nethera')->where('id_nethera', $opponentUserId)->get()->getRowArray();
            if ($opp)
                $opponentName = $opp['username'];
        }

        $battleId = 'b3v3_' . $dbBattleId . '_' . $userId;
        $state = [
            'battle_id' => $battleId,
            'db_id' => $dbBattleId,
            'user_id' => $userId,
            'player_pets' => $playerPets,
            'enemy_pets' => $enemyPets,
            'opponent_name' => $opponentName,
            'active_player_index' => 0,
            'active_enemy_index' => 0,
            'current_turn' => 'player',
            'status' => 'active',
            'logs' => ["Battle started!"]
        ];

        \Config\Services::cache()->save($battleId, $state, 3600);
        return $state;
    }

    public function applyAttack(string $battleId, int $userId, int $skillId, int $targetIndex): array
    {
        $lock = $this->acquireBattleLock($battleId);
        if (!$lock)
            throw new \RuntimeException("Failed to acquire battle lock");

        try {
            $state = (array) \Config\Services::cache()->get($battleId);
            if (!$state || !isset($state['user_id']) || $state['user_id'] != $userId || $state['status'] !== 'active') {
                throw new \RuntimeException("Invalid battle state");
            }

            $attacker = $state['player_pets'][$state['active_player_index']];
            $defender = $state['enemy_pets'][$targetIndex];

            $skill = $this->getSkill($skillId, $attacker);

            // 4. Delegate Simulation to Engine
            $result = $this->engine->simulateAttack($state, $attacker, $skill, $defender);

            // Update state logic remains in Service (Orchestration)
            $state['enemy_pets'][$targetIndex]['hp'] = max(0, $state['enemy_pets'][$targetIndex]['hp'] - $result['damage_dealt']);
            if ($state['enemy_pets'][$targetIndex]['hp'] <= 0) {
                $state['enemy_pets'][$targetIndex]['hp'] = 0;
                $state['enemy_pets'][$targetIndex]['is_fainted'] = true;
                $state['logs'][] = "{$state['enemy_pets'][$targetIndex]['name']} fainted!";

                $nextEnemy = -1;
                foreach ($state['enemy_pets'] as $i => $p) {
                    if (!$p['is_fainted']) {
                        $nextEnemy = $i;
                        break;
                    }
                }
                if ($nextEnemy === -1) {
                    $state['status'] = 'victory';
                } else {
                    $state['active_enemy_index'] = $nextEnemy;
                }
            }

            $state['logs'] = array_merge($state['logs'], $result['logs']);

            // Turn management: only switch if battle is still active
            if ($state['status'] === 'active') {
                $state['current_turn'] = 'enemy';
            }

            \Config\Services::cache()->save($battleId, $state, 3600);

            return array_merge($result, [
                'battle_state' => $state,
                'new_enemy_hp' => $state['enemy_pets'][$targetIndex]['hp'],
                'is_fainted' => $state['enemy_pets'][$targetIndex]['is_fainted']
            ]);
        } finally {
            $this->releaseBattleLock($lock);
        }
    }

    public function processEnemyTurn(string $battleId, int $userId = 0): array
    {
        $lock = $this->acquireBattleLock($battleId);
        if (!$lock)
            throw new \RuntimeException("Failed to acquire battle lock");

        try {
            $state = (array) \Config\Services::cache()->get($battleId);
            if (!$state || !isset($state['status']) || $state['status'] !== 'active')
                throw new \RuntimeException("Invalid battle");

            // A3 fix: Verify battle ownership if userId provided
            if ($userId > 0 && isset($state['user_id']) && (int) $state['user_id'] !== $userId) {
                throw new \RuntimeException("Battle does not belong to user");
            }

            $attacker = $state['enemy_pets'][$state['active_enemy_index']];
            $defender = $state['player_pets'][$state['active_player_index']];

            $skill = $this->getAiSkillSelection($attacker, $defender, true);

            // 5. Delegate Simulation to Engine
            $result = $this->engine->simulateAttack($state, $attacker, $skill, $defender);

            $state['player_pets'][$state['active_player_index']]['hp'] = max(0, $state['player_pets'][$state['active_player_index']]['hp'] - $result['damage_dealt']);
            $playerFainted = false;
            if ($state['player_pets'][$state['active_player_index']]['hp'] <= 0) {
                $state['player_pets'][$state['active_player_index']]['hp'] = 0;
                $state['player_pets'][$state['active_player_index']]['is_fainted'] = true;
                $playerFainted = true;
                $state['logs'][] = "{$state['player_pets'][$state['active_player_index']]['name']} fainted!";

                $nextPlayer = -1;
                foreach ($state['player_pets'] as $i => $p) {
                    if (!$p['is_fainted']) {
                        $nextPlayer = $i;
                        break;
                    }
                }
                if ($nextPlayer === -1) {
                    $state['status'] = 'defeat';
                }
            }

            $state['logs'] = array_merge($state['logs'], $result['logs']);

            if ($state['status'] === 'active') {
                $state['current_turn'] = 'player';
            }

            \Config\Services::cache()->save($battleId, $state, 3600);

            return array_merge($result, [
                'battle_state' => $state,
                'player_fainted' => $playerFainted
            ]);
        } finally {
            $this->releaseBattleLock($lock);
        }
    }

    public function finishBattle(string $battleId, int $userId): array
    {
        // Atomic guard: get + delete cache in one locked operation.
        // If cache is already gone, battle was already finished — reject.
        $lock = $this->acquireBattleLock($battleId);
        if (!$lock)
            throw new \RuntimeException("Failed to acquire battle lock");

        try {
            $state = (array) \Config\Services::cache()->get($battleId);
            if (!$state || !isset($state['user_id']) || $state['user_id'] != $userId) {
                throw new \RuntimeException("Invalid battle or not authorized");
            }

            // Delete cache immediately under lock to prevent double-finish
            \Config\Services::cache()->delete($battleId);
        } finally {
            $this->releaseBattleLock($lock);
        }

        // At this point we have exclusive ownership of this finish operation
        // 'active' = forfeit (player quit mid-battle) → treat as defeat
        $playerWon = ($state['status'] === 'victory');
        $avgEnemyLevel = (int) (array_sum(array_column($state['enemy_pets'], 'level')) / count($state['enemy_pets']));
        $reward_gold = $playerWon ? max(20, min(150, 20 + ($avgEnemyLevel * 3))) : 5;
        $reward_exp = $playerWon ? max(40, min(250, 40 + ($avgEnemyLevel * 5))) : 10;

        // RP Calculation
        $playerPet = $state['player_pets'][0];
        $playerRp = (int) ($playerPet['rank_points'] ?? 1000);
        $avgEnemyRp = (int) (array_sum(array_column($state['enemy_pets'], 'rank_points')) / count($state['enemy_pets']));
        if (!$avgEnemyRp)
            $avgEnemyRp = 1000;

        // 6. Persistence & Reward Distribution (Atomic)
        $this->db->transStart();

        try {
            $battleSuccess = $this->repository->finishBattle(
                $state['player_pets'][0]['id'],
                $state['enemy_pets'][0]['id'] > 0 ? $state['enemy_pets'][0]['id'] : 0,
                $playerWon ? $state['player_pets'][0]['id'] : ($state['enemy_pets'][0]['id'] > 0 ? $state['enemy_pets'][0]['id'] : 0),
                $reward_gold,
                $reward_exp,
                $state['db_id'] ?? null,
                '3v3'
            );

            if (!$battleSuccess) {
                throw new \Exception("Failed to persist 3v3 battle outcome.");
            }

            if ($playerWon) {
                $rewardSuccess = $this->rewardDistributor->applyRewards($userId, $reward_gold, $reward_exp, '3v3 Arena Victory');
                if (!$rewardSuccess) {
                    throw new \Exception("Failed to distribute rewards for user $userId.");
                }
            }

            // RP update
            $eloChange = $this->calculateEloRp($playerRp, $avgEnemyRp, $playerWon);
            $newRp = $this->updateUserArenaStats($userId, $state['player_pets'][0]['id'], $playerWon, $eloChange);

            $this->db->transComplete();
            $success = $this->db->transStatus();

            if (!$success) {
                error_log("[Arena3v3Service] Transaction failed during commit for 3v3 battle involving user $userId.");
            }

        } catch (\Exception $e) {
            $this->db->transRollback();
            error_log("[Arena3v3Service] Exception during 3v3 battle resolution: " . $e->getMessage());
            $success = false;
        }

        return [
            'success' => $success,
            'won' => $playerWon,
            'reward_gold' => $reward_gold,
            'reward_exp' => $reward_exp,
            'elo_change' => $eloChange ?? 0,
            'new_rank_points' => $newRp ?? $playerRp
        ];
    }

    public function switchPet(string $battleId, int $userId, int $newIndex): array
    {
        $lock = $this->acquireBattleLock($battleId);
        if (!$lock)
            throw new \RuntimeException("Failed to acquire battle lock");

        try {
            $state = (array) \Config\Services::cache()->get($battleId);
            if (!$state || !isset($state['user_id']) || $state['user_id'] != $userId || $state['status'] !== 'active') {
                throw new \RuntimeException("Invalid battle state or not authorized");
            }

            if ($state['current_turn'] !== 'player') {
                throw new \RuntimeException("It is not your turn");
            }

            if (!isset($state['player_pets'][$newIndex])) {
                throw new \RuntimeException("Invalid pet index selected");
            }

            $pet = $state['player_pets'][$newIndex];
            if (($pet['hp'] ?? 0) <= 0 || ($pet['is_fainted'] ?? false)) {
                throw new \RuntimeException("Cannot switch to a fainted pet");
            }

            $oldPetName = $state['player_pets'][$state['active_player_index']]['name'];
            $state['active_player_index'] = $newIndex;
            $state['current_turn'] = 'enemy'; // Switching uses a turn
            $state['logs'][] = "{$oldPetName} was switched for {$pet['name']}!";

            \Config\Services::cache()->save($battleId, $state, 3600);

            return ['success' => true, 'battle_state' => $state];
        } finally {
            $this->releaseBattleLock($lock);
        }
    }
}
