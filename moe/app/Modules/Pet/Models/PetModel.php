<?php

namespace App\Modules\Pet\Models;

use CodeIgniter\Model;

/**
 * Pet Model
 * 
 * Handles database interactions for the `user_pets` table.
 * Replaces manual mysqli queries from the legacy PetController.
 */
class PetModel extends Model
{
    protected $table = 'user_pets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    // Columns that can be mass-assigned via save()/insert()/update()
    // Server-computed fields (level, exp, hp, etc.) are excluded — use dedicated mutator methods.
    protected $allowedFields = [
        'user_id',
        'species_id',
        'nickname',
        // 'level' — server-computed only, use addExpRaw()
        // 'evolution_stage' — server-computed only, written via EvolutionService raw SQL
        // 'exp' — server-computed only, use addExpRaw()
        // 'hp' — server-computed only, written via addExpRaw/batchPersistDecay
        // 'health' — server-computed only, written via addExpRaw/batchPersistDecay
        'hunger',
        'mood',
        'status',
        'is_shiny',
        'shiny_hue',
        'last_update_timestamp',
        'is_active',
        // 'has_shield' — server-computed only, written via ItemService raw SQL
        // 'total_wins' — server-computed only, written via BaseArenaService raw SQL
        // 'total_losses' — server-computed only, written via BaseArenaService raw SQL
        // 'current_streak' — server-computed only, written via BaseArenaService raw SQL
        // 'rank_points' — server-computed only, written via BaseArenaService raw SQL
    ];

    /** Level caps per evolution stage */
    public const LEVEL_CAPS = [
        'egg' => 30, // Baby
        'baby' => 70, // Adult
        'adult' => 99, // King
    ];

    // ==================================================
    // SERVER-SIDE MUTATORS (for computed fields excluded from $allowedFields)
    // ==================================================

    /**
     * Update pet mood — safe mutator, clamps to 0-100.
     */
    public function updateMood(int $petId, int $mood): bool
    {
        return $this->builder()
            ->where('id', $petId)
            ->update(['mood' => min(100, max(0, $mood))]);
    }

    /** EXP required for current level to reach next: floor(100 + 2 × level²) */
    public static function expForNextLevel(int $level): int
    {
        return (int) floor(100 + 2 * pow($level, 2));
    }

    // Timestamps
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // ==================================================
    // CUSTOM QUERIES
    // ==================================================

    /**
     * Get all pets belonging to a user, with species info.
     * Uses batched stat decay to avoid N writes per request.
     */
    public function getUserPetsWithStats(int $userId): array
    {
        $pets = $this->db->table('user_pets AS up')
            ->select('up.*, up.total_wins AS wins, up.total_losses AS losses, ps.name AS species_name, ps.element, ps.rarity, ps.base_attack, ps.base_defense, ps.base_speed, ps.img_egg, ps.img_baby, ps.img_adult')
            ->join('pet_species AS ps', 'ps.id = up.species_id', 'left')
            ->where('up.user_id', $userId)
            ->orderBy('up.is_active', 'DESC')
            ->orderBy('up.level', 'DESC')
            ->get()
            ->getResultArray();

        // Calculate decay for all pets (no DB writes yet)
        $pendingUpdates = [];
        foreach ($pets as &$pet) {
            $result = $this->processStatDecay($pet, false);
            $pet = $result['pet'];
            if ($result['updates']) {
                $pendingUpdates[$pet['id']] = $result['updates'];
            }
        }

        // Batch-persist all decay updates in one query
        if (!empty($pendingUpdates)) {
            $this->batchPersistDecay($pendingUpdates);
        }

        return $pets;
    }

    /**
     * Get user's active pet
     */
    public function getActivePet(int $userId): ?array
    {
        $pet = $this->db->table('user_pets AS up')
            ->select('up.*, up.total_wins AS wins, up.total_losses AS losses, ps.name AS species_name, ps.element, ps.rarity, ps.img_egg, ps.img_baby, ps.img_adult, ps.passive_buff_type, ps.passive_buff_value')
            ->join('pet_species AS ps', 'ps.id = up.species_id', 'left')
            ->where('up.user_id', $userId)
            ->where('up.is_active', 1)
            ->get()
            ->getRowArray();

        if ($pet) {
            $result = $this->processStatDecay($pet, true);
            $pet = $result['pet'];
        }

        return $pet;
    }

    /**
     * Set a pet as active (deactivate others first)
     */
    public function setActivePet(int $userId, int $petId): bool
    {
        $this->db->transStart();

        // Deactivate all pets
        $this->db->table('user_pets')
            ->where('user_id', $userId)
            ->update(['is_active' => 0]);

        // Activate the selected pet
        $this->db->table('user_pets')
            ->where('id', $petId)
            ->where('user_id', $userId)
            ->update(['is_active' => 1]);

        $this->db->transComplete();

        return $this->db->transStatus();
    }
    /**
     * Get a single pet with its species data
     */
    public function getPetWithSpecies(int $petId): ?array
    {
        return $this->db->table('user_pets AS up')
            ->select('up.*, up.total_wins AS wins, up.total_losses AS losses, ps.name AS species_name, ps.element, ps.rarity, ps.base_attack, ps.base_defense, ps.base_speed, ps.img_egg, ps.img_baby, ps.img_adult, s.nama_sanctuary AS sanctuary_name')
            ->join('pet_species AS ps', 'ps.id = up.species_id', 'left')
            ->join('nethera AS n', 'n.id_nethera = up.user_id', 'left')
            ->join('sanctuary AS s', 's.id_sanctuary = n.id_sanctuary', 'left')
            ->where('up.id', $petId)
            ->get()
            ->getRowArray();
    }

    /**
     * Get a single pet with species and owner name
     */
    public function getPetWithDetails(int $petId): ?array
    {
        return $this->db->table('user_pets AS up')
            ->select('up.*, ps.name AS species_name, ps.element, ps.rarity, ps.base_attack, ps.base_defense, ps.base_speed, ps.img_egg, ps.img_baby, ps.img_adult, n.nama_lengkap AS owner_name, s.nama_sanctuary AS sanctuary_name')
            ->join('pet_species AS ps', 'ps.id = up.species_id', 'left')
            ->join('nethera AS n', 'n.id_nethera = up.user_id', 'left')
            ->join('sanctuary AS s', 's.id_sanctuary = n.id_sanctuary', 'left')
            ->where('up.id', $petId)
            ->get()
            ->getRowArray();
    }

    /**
     * Get random opponents for a user.
     * Uses count + random offsets instead of ORDER BY RAND() to avoid full table scan.
     */
    public function getOpponents(int $userId, int $limit = 5): array
    {
        $totalCount = $this->db->table('user_pets')
            ->where('user_id !=', $userId)
            ->where('is_active', 1)
            ->where('status', 'ALIVE')
            ->countAllResults();

        if ($totalCount === 0)
            return [];

        // Pick random offsets
        $offsets = [];
        $pick = min($limit, $totalCount);
        while (count($offsets) < $pick) {
            $r = mt_rand(0, $totalCount - 1);
            $offsets[$r] = true; // use keys for uniqueness
        }

        $results = [];
        foreach (array_keys($offsets) as $offset) {
            $row = $this->db->table('user_pets AS up')
                ->select('
                    up.id AS pet_id, 
                    COALESCE(up.nickname, ps.name) AS display_name, 
                    up.level, 
                    up.hp, 
                    ps.base_attack AS atk, 
                    ps.base_defense AS def, 
                    up.is_shiny, 
                    ps.rarity, 
                    ps.element, 
                    n.nama_lengkap AS owner_name,
                    up.total_wins AS wins,
                    up.total_losses AS losses,
                    ps.img_egg,
                    ps.img_baby,
                    ps.img_adult
                ')
                ->join('pet_species AS ps', 'ps.id = up.species_id')
                ->join('nethera AS n', 'n.id_nethera = up.user_id')
                ->where('up.user_id !=', $userId)
                ->where('up.is_active', 1)
                ->where('up.status', 'ALIVE')
                ->limit(1, $offset)
                ->get()
                ->getRowArray();

            if ($row) {
                $results[] = $row;
            }
        }

        return $results;
    }

    /**
     * Verify if a pet belongs to a user
     */
    public function verifyOwnership(int $userId, int $petId): bool
    {
        return $this->where('id', $petId)
            ->where('user_id', $userId)
            ->countAllResults() > 0;
    }

    /**
     * Get all pet species (Cached for 24 hours)
     */
    public function getAllSpecies(): array
    {
        $cache = \Config\Services::cache();
        $cacheKey = 'all_pet_species';

        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $species = $this->db->table('pet_species')->get()->getResultArray();
        $species = is_array($species) ? $species : [];

        // Cache for 24 hours (86400 seconds)
        $cache->save($cacheKey, $species, 86400);

        return $species;
    }

    /**
     * Get a specific species by ID (uses Cached all species)
     */
    public function getSpecies(int $speciesId): ?array
    {
        $all = $this->getAllSpecies();
        foreach ($all as $s) {
            if ((int) $s['id'] === $speciesId) {
                return $s;
            }
        }
        return null;
    }

    /**
     * Get user's gold balance and sanctuary ID.
     */
    public function getUserGoldAndSanctuary(int $userId): array
    {
        $row = $this->db->table('nethera')
            ->select('gold, id_sanctuary')
            ->where('id_nethera', $userId)
            ->get()->getRowArray();
        return $row ?: ['gold' => 0, 'id_sanctuary' => null];
    }

    /**
     * Check if a sanctuary has a specific upgrade (e.g. 'Beastiary Library').
     */
    public function hasSanctuaryUpgrade(?int $sanctuaryId, string $upgradeType): bool
    {
        if (!$sanctuaryId)
            return false;
        return (bool) $this->db->table('sanctuary_upgrades')
            ->where('sanctuary_id', $sanctuaryId)
            ->where('upgrade_type', $upgradeType)
            ->countAllResults();
    }

    /**
     * Get user's pet discovery list (species IDs and shiny status).
     * @return array{discoveredIds: int[], shinyDiscoveredIds: int[]}
     */
    public function getUserDiscovery(int $userId): array
    {
        $rows = $this->db->table('user_pet_discovery')
            ->where('user_id', $userId)
            ->get()->getResultArray();

        return [
            'discoveredIds' => array_column($rows, 'species_id'),
            'shinyDiscoveredIds' => array_column(
                array_filter($rows, fn($d) => $d['is_shiny_discovered']),
                'species_id'
            ),
        ];
    }
    /**
     * Get skills associated with a species
     */
    public function getPetSkills(int $speciesId): array
    {
        return $this->db->table('pet_skills')
            ->where('species_id', $speciesId)
            ->orderBy('skill_slot', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get aggregate statistics for a user's pet collection
     * Consolidates multiple counts into a single query for performance.
     * 
     * @return array [total, shiny, Common, Rare, Epic, Legendary]
     */
    public function getUserPetStats(int $userId): array
    {
        $rows = $this->db->table('user_pets AS up')
            ->select('up.is_shiny, ps.rarity')
            ->join('pet_species AS ps', 'ps.id = up.species_id')
            ->where('up.user_id', $userId)
            ->get()->getResultArray();

        $stats = [
            'total' => count($rows),
            'shiny' => 0,
            'Common' => 0,
            'Rare' => 0,
            'Epic' => 0,
            'Legendary' => 0
        ];

        foreach ($rows as $row) {
            if ($row['is_shiny']) {
                $stats['shiny']++;
            }
            if (isset($stats[$row['rarity']])) {
                $stats[$row['rarity']]++;
            }
        }

        return $stats;
    }

    /**
     * Calculate battle-ready stats for a pair of pets (attacker/defender)
     * Centralizes the formula for HP, Atk, and Def based on level and evolution.
     */
    public function calculateBattleStats(array $attacker, array $defender): array
    {
        $atkEvo = $this->getEvolutionMultiplier($attacker['evolution_stage'] ?? 'egg');
        $defEvo = $this->getEvolutionMultiplier($defender['evolution_stage'] ?? 'egg');

        return [
            'attackerMaxHp' => (int) round((100 + ($attacker['level'] * 10)) * $atkEvo),
            'defenderMaxHp' => (int) round((100 + ($defender['level'] * 10)) * $defEvo),
            'attackerBattleAtk' => (int) round(($attacker['base_attack'] ?? 50) * (1 + $attacker['level'] * 0.1) * $atkEvo),
            'defenderBattleAtk' => (int) round(($defender['base_attack'] ?? 50) * (1 + $defender['level'] * 0.1) * $defEvo),
            'attackerBattleDef' => (int) round(($attacker['base_defense'] ?? 40) * (1 + $attacker['level'] * 0.05) * $atkEvo),
            'defenderBattleDef' => (int) round(($defender['base_defense'] ?? 40) * (1 + $defender['level'] * 0.05) * $defEvo),
        ];
    }

    private function getEvolutionMultiplier(string $stage): float
    {
        switch ($stage) {
            case 'adult':
                return 1.6;
            case 'baby':
                return 1.3;
            default:
                return 1.0;
        }
    }

    /**
     * Add EXP to a pet — standalone version with own transaction.
     * For use inside an outer transaction, use addExpRaw() instead.
     */
    public function addExp(int $petId, int $amount): array
    {
        $this->db->transBegin();

        try {
            $result = $this->addExpRaw($petId, $amount);
            $this->db->transCommit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return ['leveled_up' => false, 'at_cap' => false];
        }
    }

    /**
     * Add EXP to a pet — raw version WITHOUT own transaction.
     * Caller MUST wrap in their own transBegin/transCommit.
     * Uses SELECT ... FOR UPDATE to prevent race conditions.
     */
    public function addExpRaw(int $petId, int $amount): array
    {
        // 1. Lock the pet record using FOR UPDATE to prevent race conditions
        $pet = $this->db->query("SELECT id, level, exp, health, evolution_stage FROM user_pets WHERE id = ? FOR UPDATE", [$petId])->getRowArray();

        if (!$pet) {
            return ['leveled_up' => false, 'at_cap' => false];
        }

        $oldLevel = (int) $pet['level'];
        $stage = $pet['evolution_stage'] ?? 'egg';
        $levelCap = self::LEVEL_CAPS[$stage] ?? 30;

        if ($oldLevel >= $levelCap) {
            return [
                'leveled_up' => false,
                'at_cap' => true,
                'old_level' => $oldLevel,
                'new_level' => $oldLevel,
                'level_cap' => $levelCap
            ];
        }

        $currentExp = (int) $pet['exp'] + $amount;
        $newLevel = $oldLevel;
        $atCap = false;

        // Calculate potential level ups
        while ($currentExp >= self::expForNextLevel($newLevel) && $newLevel < $levelCap) {
            $currentExp -= self::expForNextLevel($newLevel);
            $newLevel++;
        }

        // Enforce cap precisely
        if ($newLevel >= $levelCap) {
            $newLevel = $levelCap;
            $currentExp = 0; // Cap reached, excessive exp discarded
            $atCap = true;
        }

        $leveledUp = ($newLevel > $oldLevel);
        $updateData = [
            'level' => $newLevel,
            'exp' => $currentExp
        ];

        if ($leveledUp) {
            // Stats gain on level up
            $updateData['health'] = (int) ($pet['health'] ?? 100) + (($newLevel - $oldLevel) * 10);
            $updateData['hp'] = $updateData['health']; // Full heal on level up
        }

        $this->db->table('user_pets')->where('id', $petId)->update($updateData);

        return [
            'leveled_up' => $leveledUp,
            'at_cap' => $atCap,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'level_cap' => $levelCap
        ];
    }
    /**
     * Process stat decay for a pet based on elapsed time.
     * Lazy Update pattern: stats are calculated when accessed.
     * 
     * @param array $pet    Pet data
     * @param bool  $persist If true, immediately writes to DB. If false, returns updates for batching.
     * @return array ['pet' => array, 'updates' => array|null]
     */
    public function processStatDecay(array $pet, bool $persist = true): array
    {
        // Skip decay for dead pets or those in shelter
        if (!isset($pet['status']) || $pet['status'] === 'DEAD' || $pet['status'] === 'SHELTER') {
            return ['pet' => $pet, 'updates' => null];
        }

        $lastUpdate = (int) ($pet['last_update_timestamp'] ?? 0);
        $now = time();

        if ($lastUpdate === 0) {
            $updates = ['last_update_timestamp' => $now];
            if ($persist) {
                $this->update($pet['id'], $updates);
            }
            $pet['last_update_timestamp'] = $now;
            return ['pet' => $pet, 'updates' => $persist ? null : $updates];
        }

        $diffSec = $now - $lastUpdate;

        // Only process if at least 1 minute has passed
        if ($diffSec < 60) {
            return ['pet' => $pet, 'updates' => null];
        }

        $hours = $diffSec / 3600;
        $updates = ['last_update_timestamp' => $now];

        // 1. Calculate Hunger Decay (-4/hr)
        $currentHunger = (float) $pet['hunger'];
        $newHunger = max(0, $currentHunger - ($hours * 4));
        $updates['hunger'] = (int) round($newHunger);

        // 2. Calculate Mood Decay (-3/hr)
        $currentMood = (float) $pet['mood'];
        $newMood = max(0, $currentMood - ($hours * 3));
        $updates['mood'] = (int) round($newMood);

        // 3. Health Penalty
        $hungerPenaltyHours = 0;
        if ($currentHunger <= 0) {
            $hungerPenaltyHours = $hours;
        } elseif ($newHunger <= 0) {
            $hoursToReachZero = $currentHunger / 4;
            $hungerPenaltyHours = max(0, $hours - $hoursToReachZero);
        }

        $moodPenaltyHours = 0;
        if ($currentMood <= 0) {
            $moodPenaltyHours = $hours;
        } elseif ($newMood <= 0) {
            $hoursToReachZero = $currentMood / 3;
            $moodPenaltyHours = max(0, $hours - $hoursToReachZero);
        }

        $totalHealthLoss = (int) round(($hungerPenaltyHours * 10) + ($moodPenaltyHours * 5));

        if ($totalHealthLoss > 0) {
            $currentHp = (int) ($pet['hp'] ?? $pet['health'] ?? 100);
            $newHp = max(0, $currentHp - $totalHealthLoss);
            $updates['hp'] = $newHp;

            if ($newHp <= 0) {
                $updates['status'] = 'DEAD';
                $pet['status'] = 'DEAD';
            }
        }

        if ($persist) {
            // Use raw builder to bypass $allowedFields — hp/status are computed fields
            $this->db->table('user_pets')->where('id', $pet['id'])->update($updates);
        }

        return ['pet' => array_merge($pet, $updates), 'updates' => $persist ? null : $updates];
    }

    /**
     * Batch-persist stat decay updates for multiple pets in a single query.
     * Replaces N individual UPDATEs with one batch UPDATE using CASE/WHEN.
     */
    protected function batchPersistDecay(array $pendingUpdates): void
    {
        if (empty($pendingUpdates))
            return;

        $ids = array_keys($pendingUpdates);
        $fields = ['hunger', 'mood', 'hp', 'status', 'last_update_timestamp'];

        $setClauses = [];
        foreach ($fields as $field) {
            $cases = [];
            foreach ($pendingUpdates as $petId => $upd) {
                if (isset($upd[$field])) {
                    $val = $this->db->escape($upd[$field]);
                    $cases[] = "WHEN id = {$petId} THEN {$val}";
                }
            }
            if (!empty($cases)) {
                $setClauses[] = "`{$field}` = CASE " . implode(' ', $cases) . " ELSE `{$field}` END";
            }
        }

        if (!empty($setClauses)) {
            $idList = implode(',', $ids);
            $sql = "UPDATE user_pets SET " . implode(', ', $setClauses) . " WHERE id IN ({$idList})";
            $this->db->query($sql);
        }
    }
}
