<?php

namespace App\Modules\Battle\Services;

use App\Modules\Battle\Engines\Battle1v1Engine;
use App\Modules\Battle\Repositories\BattleRepository;
use CodeIgniter\Database\BaseConnection;

/**
 * Arena1v1Service
 * 
 * Domain logic for 1v1 Arena battles.
 * Handles validation and session orchestration.
 */
class Arena1v1Service extends BaseArenaService
{
    protected Battle1v1Engine $engine;
    protected BattleRepository $repository;
    protected \App\Modules\Sanctuary\Services\RewardDistributor $rewardDistributor;

    public function __construct(BaseConnection $db)
    {
        parent::__construct($db);
        $this->engine = service('battle1v1Engine');
        $this->repository = service('battleRepository', $db);
        $this->rewardDistributor = service('rewardDistributor');
    }

    /**
     * Get a list of opponents (Real players + AI Fallback)
     */
    public function getOpponents(int $userId, int $limit = 6): array
    {
        // 1. Fetch real players with alive pets
        $opponents = $this->db->table('nethera')
            ->where('id_nethera !=', $userId)
            ->orderBy('RAND()')
            ->limit($limit)
            ->get()->getResultArray();

        $results = [];
        foreach ($opponents as $opp) {
            $pet = $this->db->table('user_pets AS up')
                ->select('up.*, ps.name AS species_name, ps.element, ps.rarity, ps.img_egg, ps.img_baby, ps.img_adult')
                ->join('pet_species AS ps', 'ps.id = up.species_id')
                ->where('up.user_id', $opp['id_nethera'])
                ->where('up.status', 'ALIVE')
                ->orderBy('up.level', 'DESC')
                ->get()->getRowArray();

            if ($pet) {
                $hp = (int) ($pet['hp'] ?? (100 + ($pet['level'] * 5)));
                $maxHp = (int) (100 + ($pet['level'] * 5));
                $results[] = [
                    'pet_id' => (int) $pet['id'],
                    'owner_name' => $opp['username'],
                    'display_name' => $pet['nickname'] ?: $pet['species_name'],
                    'level' => (int) $pet['level'],
                    'hp' => $hp,
                    'max_hp' => $maxHp,
                    'atk' => (int) ($pet['atk'] ?? 50),
                    'def' => (int) ($pet['def'] ?? 50),
                    'rarity' => $pet['rarity'],
                    'element' => $pet['element'],
                    'img' => $this->getPetImageUrl($pet),
                    'img_egg' => $pet['img_egg'],
                    'img_baby' => $pet['img_baby'],
                    'img_adult' => $pet['img_adult'],
                    'wins' => (int) ($pet['wins'] ?? 0),
                    'losses' => (int) ($pet['losses'] ?? 0),
                    'is_ai' => false
                ];
            }
        }

        // 2. AI Fallback if not enough opponents
        if (count($results) < $limit) {
            $avgLevel = 10;
            if (!empty($results)) {
                $avgLevel = (int) (array_sum(array_column($results, 'level')) / count($results));
            } else {
                // Get player's own pet level for scaling
                $myPets = $this->db->table('user_pets')->where('user_id', $userId)->where('status', 'ALIVE')->get()->getResultArray();
                if (!empty($myPets)) {
                    $avgLevel = (int) (array_sum(array_column($myPets, 'level')) / count($myPets));
                }
            }

            $aiCount = $limit - count($results);
            $ais = $this->generateAIOpponents($aiCount, $avgLevel);
            foreach ($ais as $ai) {
                // Fetch species info for image
                $species = $this->db->table('pet_species')->where('id', $ai['species_id'])->get()->getRowArray();

                $results[] = [
                    'pet_id' => 0, // 0 indicates AI/Wild
                    'owner_name' => 'Wild Trainer',
                    'display_name' => $ai['name'],
                    'level' => $ai['level'],
                    'hp' => $ai['hp'],
                    'max_hp' => $ai['max_hp'],
                    'atk' => $ai['atk'],
                    'def' => $ai['def'],
                    'rarity' => $species['rarity'] ?? 'Common',
                    'element' => $ai['element'],
                    'img' => $species['img_adult'] ?? '',
                    'img_egg' => $species['img_egg'] ?? '',
                    'img_baby' => $species['img_baby'] ?? '',
                    'img_adult' => $species['img_adult'] ?? '',
                    'wins' => 0,
                    'losses' => 0,
                    'is_ai' => true,
                    'species_id' => $ai['species_id']
                ];
            }
        }

        return $results;
    }

    /**
     * Generate a single AI opponent with full pet data for combat.
     */
    public function generateAISingleOpponent(int $speciesId, int $level): array
    {
        $species = null;
        if ($speciesId > 0) {
            $species = $this->db->table('pet_species')->where('id', $speciesId)->get()->getRowArray();
        }
        if (!$species) {
            // Random species fallback
            $count = $this->db->table('pet_species')->countAllResults();
            $offset = mt_rand(0, max(0, $count - 1));
            $species = $this->db->table('pet_species')->limit(1, $offset)->get()->getRowArray();
        }
        if (!$species) {
            // Absolute fallback
            return [
                'id' => 0,
                'user_id' => 0,
                'nickname' => 'Wild Pet',
                'species_name' => 'Unknown',
                'level' => $level,
                'element' => 'Normal',
                'evolution_stage' => 'egg',
                'base_attack' => 50,
                'base_defense' => 50,
                'base_speed' => 50,
                'atk' => 50,
                'def' => 50,
                'img_egg' => '',
                'img_baby' => '',
                'img_adult' => '',
                'rank_points' => 1000,
                'status' => 'ALIVE',
                'species_id' => 0,
                'rarity' => 'Common',
            ];
        }

        $hp = 100 + ($level * 5);
        $stage = ($level >= 20) ? 'adult' : (($level >= 10) ? 'baby' : 'egg');

        return [
            'id' => 0,
            'user_id' => 0,
            'nickname' => 'Wild ' . $species['name'],
            'species_name' => $species['name'],
            'species_id' => $species['id'],
            'level' => $level,
            'hp' => $hp,
            'max_hp' => $hp,
            'element' => $species['element'],
            'evolution_stage' => $stage,
            'base_attack' => (int) ($species['base_attack'] ?? 50),
            'base_defense' => (int) ($species['base_defense'] ?? 50),
            'base_speed' => (int) ($species['base_speed'] ?? 50),
            'atk' => (int) ($species['base_attack'] ?? 50) + ($level * 2),
            'def' => (int) ($species['base_defense'] ?? 50) + (int) ($level * 1.5),
            'img_egg' => $species['img_egg'] ?? '',
            'img_baby' => $species['img_baby'] ?? '',
            'img_adult' => $species['img_adult'] ?? '',
            'rank_points' => 1000,
            'status' => 'ALIVE',
            'rarity' => $species['rarity'] ?? 'Common',
        ];
    }

    protected function getPetImageUrl(array $pet): string
    {
        $stage = $pet['evolution_stage'] ?? 'egg';
        if ($stage === 'adult')
            return $pet['img_adult'];
        if ($stage === 'baby')
            return $pet['img_baby'];
        return $pet['img_egg'];
    }

    /**
     * Resolve a 1v1 battle result
     */
    public function resolveBattle(int $userId, int $attackerPetId, int $defenderPetId, ?string $reportedWinner = null): array
    {
        // 1. Domain Validation
        $attacker = $this->getPetForBattle($attackerPetId);
        $defender = ($defenderPetId > 0) ? $this->getPetForBattle($defenderPetId) : null;

        if (!$attacker || $attacker['user_id'] != $userId) {
            throw new \RuntimeException("Unauthorized or invalid attacker pet");
        }

        // 2. Determine winner using client-reported result
        // The battle was already fought turn-by-turn via attack1v1/enemyTurn1v1 API calls.
        // The client tracks HP and reports who won. We trust this because each turn
        // was already validated server-side (damage calculation, skill validation).
        $winnerPetId = 0;
        if ($reportedWinner === 'attacker') {
            $winnerPetId = $attackerPetId;
        } elseif ($reportedWinner === 'defender') {
            // Player lost or forfeited — even against AI (pet_id=0)
            $winnerPetId = ($defenderPetId > 0) ? $defenderPetId : -1; // -1 = AI won
        } else {
            // Fallback: if no valid winner reported, attacker wins (graceful default)
            $winnerPetId = $attackerPetId;
        }

        $playerWon = ($winnerPetId === $attackerPetId);
        $expReward = $playerWon ? ($defender ? min(150, 10 + (int) ($defender['level'] * 2)) : 20) : 0;
        $rewardGold = 0;
        if ($playerWon) {
            $rewardGold = $defender ? min(100, 5 + (int) ($defender['level'] * 1.5)) : 15;
        }

        // 3. Persistence (via Repository & Transaction)
        $this->db->transStart();

        try {
            $opponentData = [];
            if ($defenderPetId > 0) {
                $def = $this->getPetForBattle($defenderPetId);
                $opponentData[] = [
                    'id' => $defenderPetId,
                    'user_id' => $def['user_id'] ?? 0,
                    'is_ai' => ($defenderPetId > 0 && ($def['user_id'] ?? 0) == 0)
                ];
            } else {
                $opponentData[] = ['id' => 0, 'user_id' => 0, 'is_ai' => true];
            }
            $dbBattleId = $this->repository->createSession('1v1', $userId, [$attackerPetId], $opponentData);

            // Session guard: lock and verify session is still 'active' (prevents double-commit)
            $session = $this->db->query(
                "SELECT status FROM battle_sessions WHERE id = ? FOR UPDATE",
                [$dbBattleId]
            )->getRowArray();

            if (!$session || $session['status'] !== 'active') {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'player_won' => false,
                    'reward_gold' => 0,
                    'reward_exp' => 0,
                    'elo_change' => 0,
                    'new_rank_points' => (int) ($attacker['rank_points'] ?? 1000)
                ];
            }

            $battleSuccess = $this->repository->finishBattle(
                $attackerPetId,
                $defenderPetId,
                $winnerPetId,
                $rewardGold,
                $expReward,
                $dbBattleId,
                '1v1'
            );

            if (!$battleSuccess) {
                throw new \Exception("Failed to persist battle outcome in repository.");
            }

            // 4. Reward Distribution & Rank Points (ELO)
            $attackerRp = (int) ($attacker['rank_points'] ?? 1000);
            $defenderRp = (int) ($defender['rank_points'] ?? 1000);

            $eloChange = 0;
            $newRp = $attackerRp;

            if ($playerWon) {
                $rewardSuccess = $this->rewardDistributor->applyRewards($userId, $rewardGold, $expReward, '1v1 Arena Victory');
                if (!$rewardSuccess) {
                    throw new \Exception("Failed to distribute rewards for user $userId.");
                }

                $eloChange = $this->calculateEloRp($attackerRp, $defenderRp, true);
                $newRp = $this->updateUserArenaStats($userId, $attackerPetId, true, $eloChange);
            } else {
                $eloChange = $this->calculateEloRp($attackerRp, $defenderRp, false);
                $newRp = $this->updateUserArenaStats($userId, $attackerPetId, false, $eloChange);
            }

            $this->db->transComplete();
            $success = $this->db->transStatus();

            if (!$success) {
                error_log("[Arena1v1Service] Transaction failed during commit for battle involving user $userId.");
            }

        } catch (\Exception $e) {
            $this->db->transRollback();
            error_log("[Arena1v1Service] Exception during battle resolution: " . $e->getMessage());
            $success = false;
        }

        return [
            'success' => $success,
            'player_won' => $playerWon,
            'reward_gold' => $rewardGold,
            'reward_exp' => $expReward,
            'elo_change' => $eloChange,
            'new_rank_points' => $newRp
        ];
    }
}
