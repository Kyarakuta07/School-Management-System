<?php

namespace App\Modules\Pet\Services;

use CodeIgniter\Database\ConnectionInterface;

/**
 * EvolutionService
 * 
 * Handles business logic for pet evolution including:
 * - Manual evolution via sacrifice system (3 fodder pets required)
 * - Evolution stage validation (egg→baby→adult)
 * - Fodder candidate retrieval (same rarity required)
 * - Gold cost validation
 * 
 * All validation + mutation inside single transaction with FOR UPDATE locks.
 */
class EvolutionService
{
    protected ConnectionInterface $db;

    const GOLD_COST = 500;
    const FODDER_REQUIRED = 3;

    // Evolution stages
    const STAGE_EGG = 'egg';
    const STAGE_BABY = 'baby';
    const STAGE_ADULT = 'adult';

    // Level requirements (from constants.php)
    const LEVEL_TIER_2 = 30;  // Baby → Adult requires level 30+
    const LEVEL_TIER_3 = 70;  // Adult → King requires level 70+

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    // ================================================
    // EVOLUTION LOGIC
    // ================================================

    /**
     * Get evolution candidates (fodder pets) for a main pet
     * 
     * @return array ['success' => bool, 'required_rarity' => string, 'candidates' => array]
     */
    public function getEvolutionCandidates(int $userId, int $mainPetId): array
    {
        // Get main pet's rarity and stage
        $mainPet = $this->db->query(
            "SELECT up.id, up.level, up.evolution_stage, ps.rarity 
             FROM user_pets up 
             JOIN pet_species ps ON up.species_id = ps.id 
             WHERE up.id = ? AND up.user_id = ?",
            [$mainPetId, $userId]
        )->getRowArray();

        if (!$mainPet) {
            return ['success' => false, 'error' => 'Main pet not found'];
        }

        $requiredRarity = $mainPet['rarity'];
        $currentStage = $mainPet['evolution_stage'] ?? self::STAGE_EGG;

        // Check if already at max stage
        if ($currentStage === self::STAGE_ADULT) {
            return ['success' => false, 'error' => 'Pet is already at King stage (max evolution)'];
        }

        // Get potential fodder pets (same rarity, not active, not the main pet, alive)
        $candidates = $this->db->query(
            "SELECT up.id, up.level, up.nickname, up.evolution_stage, up.species_id,
                    ps.name as species_name, ps.rarity, ps.img_egg, ps.img_baby, ps.img_adult
             FROM user_pets up 
             JOIN pet_species ps ON up.species_id = ps.id 
             WHERE up.user_id = ? 
               AND up.id != ? 
               AND up.is_active = 0 
               AND up.status = 'ALIVE'
               AND ps.rarity = ?
             ORDER BY up.level ASC",
            [$userId, $mainPetId, $requiredRarity]
        )->getResultArray();

        return [
            'success' => true,
            'required_rarity' => $requiredRarity,
            'current_stage' => $currentStage,
            'candidates' => $candidates
        ];
    }

    /**
     * Manually evolve a pet by sacrificing 3 other pets of the same rarity.
     * ALL validation + mutation inside a single transaction with FOR UPDATE locks.
     * 
     * @return array ['success' => bool, 'new_stage' => string, 'new_level' => int]
     */
    public function evolvePet(int $userId, int $mainPetId, array $fodderPetIds): array
    {
        // Basic input validation (no DB needed)
        if (count($fodderPetIds) !== self::FODDER_REQUIRED) {
            return ['success' => false, 'error' => 'Exactly 3 fodder pets required'];
        }

        // EVERYTHING inside a single transaction with FOR UPDATE locks
        $this->db->transBegin();

        try {
            // 1. Lock and read main pet
            $mainPet = $this->db->query(
                "SELECT up.*, ps.rarity 
                 FROM user_pets up 
                 JOIN pet_species ps ON up.species_id = ps.id 
                 WHERE up.id = ? AND up.user_id = ?
                 FOR UPDATE",
                [$mainPetId, $userId]
            )->getRowArray();

            if (!$mainPet) {
                $this->db->transRollback();
                return ['success' => false, 'error' => 'Main pet not found or not owned'];
            }

            // 2. Validate evolution stage and level
            $currentStage = $mainPet['evolution_stage'] ?? self::STAGE_EGG;
            $level = (int) $mainPet['level'];
            $validation = $this->validateEvolutionRequirements($currentStage, $level);

            if (!$validation['success']) {
                $this->db->transRollback();
                return $validation;
            }

            $nextStage = $validation['next_stage'];

            // 3. Lock and check user gold
            $user = $this->db->query(
                "SELECT gold FROM nethera WHERE id_nethera = ? FOR UPDATE",
                [$userId]
            )->getRowArray();

            if (!$user || (int) $user['gold'] < self::GOLD_COST) {
                $this->db->transRollback();
                $userGold = (int) ($user['gold'] ?? 0);
                return ['success' => false, 'error' => "Not enough gold! Need " . self::GOLD_COST . ", have $userGold"];
            }

            // 4. Lock and validate fodder pets
            $placeholders = implode(',', array_fill(0, count($fodderPetIds), '?'));
            $fodderPets = $this->db->query(
                "SELECT up.id, up.user_id, up.is_active, ps.rarity 
                 FROM user_pets up 
                 JOIN pet_species ps ON up.species_id = ps.id 
                 WHERE up.id IN ($placeholders)
                 FOR UPDATE",
                $fodderPetIds
            )->getResultArray();

            if (count($fodderPets) !== self::FODDER_REQUIRED) {
                $this->db->transRollback();
                return ['success' => false, 'error' => 'One or more fodder pets not found'];
            }

            foreach ($fodderPets as $fodder) {
                if ((int) $fodder['user_id'] !== $userId) {
                    $this->db->transRollback();
                    return ['success' => false, 'error' => 'You do not own all selected fodder pets'];
                }
                if ($fodder['rarity'] !== $mainPet['rarity']) {
                    $this->db->transRollback();
                    return ['success' => false, 'error' => "All pets must be same rarity ({$mainPet['rarity']})"];
                }
                if ((bool) $fodder['is_active']) {
                    $this->db->transRollback();
                    return ['success' => false, 'error' => 'Cannot sacrifice an active pet'];
                }
                if ((int) $fodder['id'] === $mainPetId) {
                    $this->db->transRollback();
                    return ['success' => false, 'error' => 'Cannot sacrifice the pet you are evolving'];
                }
            }

            // 5. Execute mutations
            // Deduct gold
            $this->db->query("UPDATE nethera SET gold = gold - ? WHERE id_nethera = ?", [self::GOLD_COST, $userId]);

            // Delete fodder pets
            foreach ($fodderPetIds as $fodderId) {
                $this->db->query("DELETE FROM user_pets WHERE id = ? AND user_id = ?", [$fodderId, $userId]);
            }

            // Evolve main pet
            $newLevel = $level + 1;
            $this->db->query(
                "UPDATE user_pets SET evolution_stage = ?, level = ?, exp = 0 WHERE id = ?",
                [$nextStage, $newLevel, $mainPetId]
            );

            // Record evolution history (if table exists)
            try {
                $historyJson = json_encode($fodderPetIds);
                $this->db->query(
                    "INSERT INTO pet_evolution_history (user_id, main_pet_id, fodder_pet_ids, gold_cost) 
                     VALUES (?, ?, ?, ?)",
                    [$userId, $mainPetId, $historyJson, self::GOLD_COST]
                );
            } catch (\Exception $e) {
                // Ignore if table doesn't exist
            }

            $this->db->transCommit();

            $stageEmoji = ($nextStage === self::STAGE_BABY) ? '🐣' : '🦅';
            return [
                'success' => true,
                'message' => "Evolution successful! Your pet evolved to $nextStage stage! $stageEmoji",
                'sacrificed_count' => self::FODDER_REQUIRED,
                'gold_spent' => self::GOLD_COST,
                'new_stage' => $nextStage,
                'new_level' => $newLevel
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            return ['success' => false, 'error' => 'Evolution failed: ' . $e->getMessage()];
        }
    }

    // ================================================
    // PRIVATE HELPERS
    // ================================================

    /**
     * Validate evolution requirements (stage and level)
     */
    protected function validateEvolutionRequirements(string $currentStage, int $level): array
    {
        if ($currentStage === self::STAGE_ADULT) {
            return ['success' => false, 'error' => 'Pet is already at King stage (max evolution)'];
        }

        if ($currentStage === self::STAGE_EGG && $level < self::LEVEL_TIER_2) {
            return ['success' => false, 'error' => "Pet must be Level " . self::LEVEL_TIER_2 . "+ to evolve from Baby to Adult (current: Lv.$level)"];
        }

        if ($currentStage === self::STAGE_BABY && $level < self::LEVEL_TIER_3) {
            return ['success' => false, 'error' => "Pet must be Level " . self::LEVEL_TIER_3 . "+ to evolve from Adult to King (current: Lv.$level)"];
        }

        $nextStage = ($currentStage === self::STAGE_EGG) ? self::STAGE_BABY : self::STAGE_ADULT;

        return ['success' => true, 'next_stage' => $nextStage];
    }
}
