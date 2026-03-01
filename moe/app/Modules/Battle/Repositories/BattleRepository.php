<?php

namespace App\Modules\Battle\Repositories;

use CodeIgniter\Database\BaseConnection;
use App\Modules\Battle\Models\BattleModel;
use App\Modules\Pet\Interfaces\PetServiceInterface;

/**
 * BattleRepository
 * 
 * Handles all database writes for the Battle domain to ensure consistency
 * and isolate persistence from simulation logic.
 * 
 * RAW: No own transaction — caller (Arena1v1/3v3Service) owns the TX.
 * Uses PetService.addExpRaw() to avoid nested transactions.
 */
class BattleRepository
{
    protected BaseConnection $db;
    protected BattleModel $battleModel;
    protected PetServiceInterface $petService;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
        $this->battleModel = new BattleModel();
        $this->petService = service('petService');
    }

    /**
     * Persist a battle result and update participants' stats.
     * No own TX — caller must wrap in transBegin/transCommit.
     * 
     * @param int $attackerPetId
     * @param int $defenderPetId
     * @param int $winnerPetId
     * @param int $rewardGold
     * @param int $rewardExp
     * @param int|null $sessionId Optional battle session ID to close
     * @param string $mode '1v1' or '3v3'
     * @return bool Success status
     */
    public function finishBattle(
        int $attackerPetId,
        int $defenderPetId,
        int $winnerPetId,
        int $rewardGold,
        int $rewardExp,
        ?int $sessionId = null,
        string $mode = '1v1'
    ): bool {
        // 1. Record the battle
        $this->battleModel->insert([
            'attacker_pet_id' => $attackerPetId,
            'defender_pet_id' => ($defenderPetId > 0) ? $defenderPetId : null,
            'winner_pet_id' => ($winnerPetId > 0) ? $winnerPetId : null,
            'reward_gold' => $rewardGold,
            'reward_exp' => $rewardExp,
            'mode' => $mode,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // 2. Update Pet Stats (with row-level locking)
        if ($winnerPetId > 0) {
            $this->db->query("SELECT id FROM user_pets WHERE id = ? FOR UPDATE", [$winnerPetId]);
            $this->db->table('user_pets')->where('id', $winnerPetId)->set('total_wins', 'total_wins + 1', false)->update();
        }

        $loserPetId = ($winnerPetId === $attackerPetId) ? $defenderPetId : $attackerPetId;
        if ($loserPetId > 0) {
            $this->db->query("SELECT id FROM user_pets WHERE id = ? FOR UPDATE", [$loserPetId]);
            $this->db->table('user_pets')->where('id', $loserPetId)->set('total_losses', 'total_losses + 1', false)->update();

            // 2b. Battle HP penalty: loser takes -10 HP, can die
            $loserPet = $this->db->table('user_pets')->select('hp, health')->where('id', $loserPetId)->get()->getRowArray();
            if ($loserPet) {
                $currentHp = (int) ($loserPet['hp'] ?? $loserPet['health'] ?? 100);
                $newHp = max(0, $currentHp - 10);
                $hpUpdate = ['hp' => $newHp];
                if ($newHp <= 0) {
                    $hpUpdate['status'] = 'DEAD';
                }
                $this->db->table('user_pets')->where('id', $loserPetId)->update($hpUpdate);
            }
        }

        // 3. Apply Pet EXP (Only for the attacker's pet if they won) — raw, no nested TX
        if ($winnerPetId === $attackerPetId && $rewardExp > 0) {
            $this->petService->addExpRaw($attackerPetId, $rewardExp);
        }

        // 4. Close Session if applicable
        if ($sessionId) {
            $this->db->table('battle_sessions')
                ->where('id', $sessionId)
                ->update([
                    'status' => 'completed',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }

        return true;
    }

    /**
     * Create a new battle session and record ALL participants.
     * No own TX — caller must wrap in transBegin/transCommit.
     */
    public function createSession(string $type, int $userId, array $playerPetIds, array $opponentPets = []): int
    {
        $this->db->table('battle_sessions')->insert([
            'battle_type' => $type,
            'status' => 'active',
            'current_turn_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $sessionId = $this->db->insertID();

        // Record Player Pets
        foreach ($playerPetIds as $idx => $pid) {
            $this->db->table('battle_participants')->insert([
                'battle_id' => $sessionId,
                'user_id' => $userId,
                'pet_id' => $pid,
                'team_index' => 0, // 0 = Player Team
                'is_ai' => 0
            ]);
        }

        // Record Opponent Pets (Satisfies Ph6: 2 rows for 1v1, 6 for 3v3)
        foreach ($opponentPets as $idx => $oppPet) {
            $this->db->table('battle_participants')->insert([
                'battle_id' => $sessionId,
                'user_id' => $oppPet['user_id'] ?? 0,
                'pet_id' => $oppPet['id'] ?? -1,
                'team_index' => 1, // 1 = Opponent Team
                'is_ai' => ($oppPet['is_ai'] ?? false) ? 1 : 0
            ]);
        }

        return $sessionId;
    }

    // ── Read Operations (moved from BattleHistoryController) ──

    /**
     * Get battle history with full pet + species + owner details (6-JOIN query).
     *
     * @param int|null $petId  If set, filter to battles involving this pet
     * @param int      $userId Used for "all my pets" subquery when petId is null
     */
    public function getHistoryWithDetails(?int $petId, int $userId, int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('pet_battles AS b')
            ->select('b.*, ap.user_id AS attacker_user_id, ap.nickname AS attacker_name, ap.evolution_stage AS attacker_stage, ap.level AS attacker_level, 
                      dp.user_id AS defender_user_id, dp.nickname AS defender_name, dp.evolution_stage AS defender_stage, dp.level AS defender_level,
                      ps_a.name AS attacker_species, ps_a.img_egg AS atk_egg, ps_a.img_baby AS atk_baby, ps_a.img_adult AS atk_adult,
                      ps_d.name AS defender_species, ps_d.img_egg AS def_egg, ps_d.img_baby AS def_baby, ps_d.img_adult AS def_adult,
                      na.username AS attacker_username, nd.username AS defender_username')
            ->join('user_pets AS ap', 'ap.id = b.attacker_pet_id')
            ->join('pet_species AS ps_a', 'ps_a.id = ap.species_id')
            ->join('user_pets AS dp', 'dp.id = b.defender_pet_id', 'left')
            ->join('pet_species AS ps_d', 'ps_d.id = dp.species_id', 'left')
            ->join('nethera AS na', 'na.id_nethera = ap.user_id', 'left')
            ->join('nethera AS nd', 'nd.id_nethera = dp.user_id', 'left');

        if ($petId) {
            $builder->groupStart()
                ->where('b.attacker_pet_id', $petId)
                ->orWhere('b.defender_pet_id', $petId)
                ->groupEnd();
        } else {
            $subquery = $this->db->table('user_pets')->select('id')->where('user_id', $userId)->getCompiledSelect();
            $builder->groupStart()
                ->where("b.attacker_pet_id IN ({$subquery})", null, false)
                ->orWhere("b.defender_pet_id IN ({$subquery})", null, false)
                ->groupEnd();
        }

        return $builder->orderBy('b.created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray();
    }

    /**
     * Map raw battle history rows to frontend-friendly format.
     */
    public function mapForFrontend(array $history, int $userId): array
    {
        $petService = service('petService');

        foreach ($history as &$battle) {
            $isAttacker = ($userId == $battle['attacker_user_id']);
            $battle['battle_role'] = $isAttacker ? 'attacker' : 'defender';
            $battle['won'] = ($battle['winner_pet_id'] == ($isAttacker ? $battle['attacker_pet_id'] : $battle['defender_pet_id']));

            if ($isAttacker) {
                $battle['my_pet_id'] = $battle['attacker_pet_id'];
                $battle['my_pet_name'] = $battle['attacker_name'] ?: ($battle['attacker_species'] ?? 'My Pet');
                $battle['my_pet_level'] = (int) ($battle['attacker_level'] ?? 1);
                $battle['my_pet_image'] = $petService->getPetImageUrl(['evolution_stage' => $battle['attacker_stage'], 'img_egg' => $battle['atk_egg'] ?? '', 'img_baby' => $battle['atk_baby'] ?? '', 'img_adult' => $battle['atk_adult'] ?? '']);

                $battle['opp_pet_id'] = $battle['defender_pet_id'];
                $battle['opp_pet_name'] = $battle['defender_name'] ?: ($battle['defender_species'] ?? ($battle['mode'] === '3v3' ? 'Enemy Team' : 'Wild Pet'));
                $battle['opp_pet_level'] = (int) ($battle['defender_level'] ?? 10);
                $battle['opp_pet_image'] = $petService->getPetImageUrl(['evolution_stage' => $battle['defender_stage'] ?? 'egg', 'img_egg' => $battle['def_egg'] ?? '', 'img_baby' => $battle['def_baby'] ?? '', 'img_adult' => $battle['def_adult'] ?? '']);
                $battle['opp_username'] = $battle['defender_username'] ?? 'Wild Trainer';
            } else {
                $battle['my_pet_id'] = $battle['defender_pet_id'];
                $battle['my_pet_name'] = $battle['defender_name'] ?: ($battle['defender_species'] ?? 'My Pet');
                $battle['my_pet_level'] = (int) ($battle['defender_level'] ?? 1);
                $battle['my_pet_image'] = $petService->getPetImageUrl(['evolution_stage' => $battle['defender_stage'] ?? 'egg', 'img_egg' => $battle['def_egg'] ?? '', 'img_baby' => $battle['def_baby'] ?? '', 'img_adult' => $battle['def_adult'] ?? '']);

                $battle['opp_pet_id'] = $battle['attacker_pet_id'];
                $battle['opp_pet_name'] = $battle['attacker_name'] ?: ($battle['attacker_species'] ?? 'Wild Pet');
                $battle['opp_pet_level'] = (int) ($battle['attacker_level'] ?? 1);
                $battle['opp_pet_image'] = $petService->getPetImageUrl(['evolution_stage' => $battle['attacker_stage'], 'img_egg' => $battle['atk_egg'] ?? '', 'img_baby' => $battle['atk_baby'] ?? '', 'img_adult' => $battle['atk_adult'] ?? '']);
                $battle['opp_username'] = $battle['attacker_username'] ?? 'Wild Trainer';
            }

            $battle['reward_gold'] = (int) ($battle['reward_gold'] ?? 0);
            $battle['reward_exp'] = (int) ($battle['reward_exp'] ?? 0);
        }

        return $history;
    }

    /**
     * Get user arena statistics (wins, losses, streak).
     */
    public function getUserArenaStats(int $userId): array
    {
        $row = $this->db->table('nethera')
            ->select('arena_wins, arena_losses, current_win_streak')
            ->where('id_nethera', $userId)
            ->get()->getRowArray();
        return $row ?: [];
    }

    /**
     * Get leaderboard data by type (rank, wins, streak).
     * Cached for 5 minutes.
     */
    public function getLeaderboard(string $type = 'rank', int $limit = 50): array
    {
        return cache()->remember("battle_leaderboard_{$type}_{$limit}", 300, function () use ($type, $limit) {
            if ($type === 'rank') {
                return $this->db->table('user_pets AS up')
                    ->select('up.id, up.nickname, up.rank_points, up.user_id, ps.name AS species_name, n.username AS owner')
                    ->join('pet_species AS ps', 'ps.id = up.species_id')
                    ->join('nethera AS n', 'n.id_nethera = up.user_id')
                    ->orderBy('up.rank_points', 'DESC')
                    ->limit($limit)
                    ->get()->getResultArray();
            }

            $field = ($type === 'streak') ? 'current_win_streak' : 'arena_wins';
            return $this->db->table('nethera')
                ->select('id_nethera AS user_id, username AS owner, arena_wins, arena_losses, current_win_streak')
                ->orderBy($field, 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
        });
    }
}
