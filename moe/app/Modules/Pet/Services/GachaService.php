<?php

namespace App\Modules\Pet\Services;

use CodeIgniter\Database\ConnectionInterface;

/**
 * GachaService
 * 
 * Handles business logic for gacha/summoning operations including:
 * - Rarity rolling based on gacha type
 * - Shiny pet determination
 * - Pet creation and collection management
 * - Sanctuary upgrade bonuses (Beastiary Library)
 */
class GachaService
{
    protected ConnectionInterface $db;

    // Gacha rarity weights (type 1 = standard)
    const RARITY_WEIGHTS = [
        'Common' => 700,
        'Rare' => 220,
        'Epic' => 60,
        'Legendary' => 18,
        'Mythical' => 2
    ];

    const MAX_PET_COLLECTION = 25;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    // ================================================
    // GACHA ROLL LOGIC
    // ================================================

    /**
     * Perform a gacha roll and create a new pet
     * 
     * @param int $userId User's ID
     * @param int $gachaType 1=Standard (all rarities), 2=Rare+ guaranteed, 3=Premium (Epic+ guaranteed)
     * @param bool $ownTx If true, wraps in own transaction. If false, caller owns the TX.
     * @return array ['success' => bool, 'pet_id' => int, 'species' => array, 'is_shiny' => bool, 'rarity' => string, 'error' => string]
     */
    public function performGacha(int $userId, int $gachaType = 1, bool $ownTx = true): array
    {
        // Roll for rarity (pure computation, no lock needed)
        $rarity = $this->rollRarity($gachaType);

        // Get random species of that rarity (read-only, no lock needed)
        $species = $this->getRandomSpecies($rarity);
        if (!$species) {
            return ['success' => false, 'error' => 'No species found for rarity: ' . $rarity];
        }

        // Determine shiny status (read-only)
        $shinyData = $this->rollShiny($userId);

        // Wrap all write operations in a transaction (only if we own it)
        if ($ownTx) {
            $this->db->transBegin();
        }

        try {
            // Pet count check INSIDE TX with FOR UPDATE to prevent TOCTOU race (F5 fix)
            $countRow = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM user_pets WHERE user_id = ? FOR UPDATE",
                [$userId]
            )->getRowArray();
            $petCount = (int) ($countRow['cnt'] ?? 0);

            if ($petCount >= self::MAX_PET_COLLECTION) {
                if ($ownTx)
                    $this->db->transRollback();
                return [
                    'success' => false,
                    'error' => 'Pet collection full! You can only have 25 pets. Sell some pets first.'
                ];
            }

            // Create the new pet
            $newPetId = $this->createPet($userId, $species['id'], $shinyData['is_shiny'], $shinyData['shiny_hue']);

            // Auto-activate if user has no active pet
            $this->autoActivateFirstPet($userId, $newPetId);

            // Record discovery in Bestiary
            $this->recordDiscovery($userId, $species['id'], $shinyData['is_shiny']);

            if ($ownTx) {
                $this->db->transCommit();
            }
        } catch (\Throwable $e) {
            if ($ownTx) {
                $this->db->transRollback();
            }
            return ['success' => false, 'error' => 'Failed to create pet. Please try again.'];
        }

        return [
            'success' => true,
            'pet_id' => $newPetId,
            'species' => $species,
            'is_shiny' => $shinyData['is_shiny'],
            'shiny_hue' => $shinyData['shiny_hue'],
            'rarity' => $rarity
        ];
    }

    // ================================================
    // RARITY DETERMINATION
    // ================================================

    /**
     * Roll for rarity based on gacha type
     * 
     * @param int $gachaType 1=Standard, 2=Rare+, 3=Premium (Epic+)
     * @return string Rarity name
     */
    protected function rollRarity(int $gachaType): string
    {
        $weights = self::RARITY_WEIGHTS;

        if ($gachaType === 2) {
            // Rare or better guaranteed
            $weights['Common'] = 0;
            $weights['Rare'] = 750;
            $weights['Epic'] = 180;
            $weights['Legendary'] = 60;
            $weights['Mythical'] = 10;
        } elseif ($gachaType === 3) {
            // Epic or better guaranteed (Premium Gacha)
            $weights['Common'] = 0;
            $weights['Rare'] = 0;
            $weights['Epic'] = 800;
            $weights['Legendary'] = 180;
            $weights['Mythical'] = 20;
        }

        // Weighted random selection
        $total = array_sum($weights);
        $roll = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $rarity => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return $rarity;
            }
        }

        return 'Common'; // Fallback
    }

    /**
     * Get random species for a given rarity
     */
    protected function getRandomSpecies(string $rarity): ?array
    {
        // Optimization: avoid ORDER BY RAND() on larger datasets
        $count = $this->db->table('pet_species')
            ->where('rarity', $rarity)
            ->countAllResults();

        if ($count === 0)
            return null;

        $offset = mt_rand(0, $count - 1);

        return $this->db->table('pet_species')
            ->where('rarity', $rarity)
            ->limit(1, $offset)
            ->get()
            ->getRowArray();
    }

    // ================================================
    // SHINY MECHANICS
    // ================================================

    /**
     * Determine if pet is shiny with sanctuary upgrade bonus
     * 
     * @return array ['is_shiny' => bool, 'shiny_hue' => int]
     */
    protected function rollShiny(int $userId): array
    {
        $shinyChance = 1; // Base 1% chance

        // Check for Beastiary Library upgrade (+5% chance)
        $upgrades = $this->getUserSanctuaryUpgrades($userId);
        if (in_array('Beastiary Library', $upgrades)) {
            $shinyChance += 5; // Total 6%
        }

        $isShiny = (rand(1, 100) <= $shinyChance);
        $shinyHue = $isShiny ? rand(30, 330) : 0;

        return [
            'is_shiny' => $isShiny,
            'shiny_hue' => $shinyHue
        ];
    }

    /**
     * Get user's sanctuary upgrades
     * 
     * NOTE: The sanctuary relationship is stored in the `nethera` table (id_sanctuary column),
     * NOT via an owner_id column in the sanctuary table.
     */
    protected function getUserSanctuaryUpgrades(int $userId): array
    {
        // Get user's sanctuary ID from the nethera table
        $user = $this->db->query(
            "SELECT id_sanctuary FROM nethera WHERE id_nethera = ?",
            [$userId]
        )->getRowArray();

        if (!$user || empty($user['id_sanctuary'])) {
            return [];
        }

        $sanctuaryId = $user['id_sanctuary'];

        // Get upgrades for this sanctuary
        $upgrades = $this->db->query(
            "SELECT upgrade_type FROM sanctuary_upgrades WHERE sanctuary_id = ?",
            [$sanctuaryId]
        )->getResultArray();

        return array_column($upgrades, 'upgrade_type');
    }

    // ================================================
    // PET CREATION & MANAGEMENT
    // ================================================

    /**
     * Get user's current pet count
     */
    protected function getUserPetCount(int $userId): int
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as pet_count FROM user_pets WHERE user_id = ?",
            [$userId]
        )->getRowArray();

        return (int) ($result['pet_count'] ?? 0);
    }

    /**
     * Create new pet in database
     */
    protected function createPet(int $userId, int $speciesId, bool $isShiny, int $shinyHue): int
    {
        $currentTime = time();

        $this->db->query(
            "INSERT INTO user_pets (user_id, species_id, nickname, level, evolution_stage, exp, hp, health, hunger, mood, status, is_shiny, shiny_hue, last_update_timestamp, is_active)
             VALUES (?, ?, NULL, 1, 'egg', 0, 100, 100, 100, 100, 'ALIVE', ?, ?, ?, 0)",
            [$userId, $speciesId, (int) $isShiny, $shinyHue, $currentTime]
        );

        return (int) $this->db->insertID();
    }

    /**
     * Auto-activate pet if user has no active pet
     */
    protected function autoActivateFirstPet(int $userId, int $newPetId): void
    {
        $activeCount = $this->db->query(
            "SELECT COUNT(*) as cnt FROM user_pets WHERE user_id = ? AND is_active = 1",
            [$userId]
        )->getRowArray();

        if ((int) $activeCount['cnt'] === 0) {
            $this->db->query("UPDATE user_pets SET is_active = 1 WHERE id = ?", [$newPetId]);
        }
    }

    /**
     * Record pet discovery for the Bestiary
     */
    protected function recordDiscovery(int $userId, int $speciesId, bool $isShiny): void
    {
        // Use REPLACE or INSERT IGNORE to handle existing discoveries
        $this->db->query(
            "INSERT INTO user_pet_discovery (user_id, species_id, is_shiny_discovered) 
             VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             is_shiny_discovered = GREATEST(is_shiny_discovered, VALUES(is_shiny_discovered))",
            [$userId, $speciesId, (int) $isShiny]
        );
    }

    /**
     * Get discovery stats for a user
     */
    public function getDiscoveryStats(int $userId): array
    {
        $totalSpecies = $this->db->table('pet_species')->countAllResults();
        $discovered = $this->db->table('user_pet_discovery')
            ->where('user_id', $userId)
            ->countAllResults();

        return [
            'discovered' => $discovered,
            'total' => $totalSpecies,
            'percentage' => ($totalSpecies > 0) ? round(($discovered / $totalSpecies) * 100) : 0
        ];
    }
}
