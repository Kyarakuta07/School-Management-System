<?php
/**
 * MOE Pet System - Battle Engine
 * Dragon City-style 3v3 turn-based combat
 * 
 * Handles damage calculation, element advantages, and RNG.
 * All calculations are server-side for security.
 */

// Load constants
require_once __DIR__ . '/constants.php';

class BattleEngine
{
    private $conn;

    // Element advantage wheel (Dragon City style)
    // Fire → Air → Earth → Water → Fire
    // Light ↔ Dark (mutual weakness)
    private const ELEMENT_ADVANTAGES = [
        'Fire' => 'Air',
        'Air' => 'Earth',
        'Earth' => 'Water',
        'Water' => 'Fire',
        'Light' => 'Dark',
        'Dark' => 'Light'
    ];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Calculate damage for an attack
     * 
     * @param array $attacker_pet Attacker pet data with base_attack, level
     * @param array $skill Skill data with base_damage, skill_element
     * @param array $defender_pet Defender pet data with base_defense, element
     * @return array Result with damage_dealt, is_critical, element_advantage, logs
     */
    public function calculateDamage(array $attacker_pet, array $skill, array $defender_pet): array
    {
        $logs = [];

        // Get skill element (fallback to pet's element if not specified)
        $skill_element = $skill['skill_element'] ?? $attacker_pet['element'];
        $skill_name = $skill['skill_name'] ?? 'Attack';
        $base_damage = (int) ($skill['base_damage'] ?? 25);

        // Base damage calculation
        $attack_power = (int) ($attacker_pet['base_attack'] ?? 10);
        $defense_power = (int) ($defender_pet['base_defense'] ?? 10);
        $attacker_level = (int) ($attacker_pet['level'] ?? 1);

        // Formula: base_damage + (attack * 0.1) - (defense * 0.05)
        $raw_damage = $base_damage + ($attack_power * 0.1) - ($defense_power * 0.05);
        $raw_damage = max(1, $raw_damage); // Minimum 1 damage

        // Level bonus: +2% per level
        $level_multiplier = 1 + ($attacker_level * 0.02);
        $damage = $raw_damage * $level_multiplier;

        $logs[] = "{$attacker_pet['species_name']} used {$skill_name}!";

        // Element advantage
        $element_mult = $this->getElementAdvantage($skill_element, $defender_pet['element']);
        $element_advantage = 'neutral';

        if ($element_mult >= ELEMENT_STRONG_MULTIPLIER) {
            $element_advantage = 'super_effective';
            $logs[] = "It's super effective! (2x damage)";
        } elseif ($element_mult <= ELEMENT_WEAK_MULTIPLIER) {
            $element_advantage = 'not_effective';
            $logs[] = "It's not very effective... (0.5x damage)";
        }

        $damage *= $element_mult;

        // Critical hit (15% chance, 1.5x damage)
        $is_critical = (mt_rand(1, 100) <= 15);
        if ($is_critical) {
            $damage *= 1.5;
            $logs[] = "CRITICAL HIT!";
        }

        // RNG variation (+/- 5%)
        $damage = $this->applyRngVariation($damage);

        // Round to integer
        $damage_dealt = (int) round($damage);
        $damage_dealt = max(1, $damage_dealt); // Minimum 1 damage

        $logs[] = "Dealt {$damage_dealt} damage to {$defender_pet['species_name']}!";

        return [
            'damage_dealt' => $damage_dealt,
            'is_critical' => $is_critical,
            'element_advantage' => $element_advantage,
            'skill_used' => $skill_name,
            'skill_element' => $skill_element,
            'logs' => $logs
        ];
    }

    /**
     * Get element advantage multiplier
     * 
     * @param string $attacker_element Attacker's element
     * @param string $defender_element Defender's element
     * @return float Multiplier (2.0 = strong, 0.5 = weak, 1.0 = neutral)
     */
    public function getElementAdvantage(string $attacker_element, string $defender_element): float
    {
        // Same element = neutral
        if ($attacker_element === $defender_element) {
            return ELEMENT_NEUTRAL_MULTIPLIER;
        }

        // Check if attacker is strong against defender
        if (
            isset(self::ELEMENT_ADVANTAGES[$attacker_element])
            && self::ELEMENT_ADVANTAGES[$attacker_element] === $defender_element
        ) {
            return ELEMENT_STRONG_MULTIPLIER;
        }

        // Check if attacker is weak against defender (reverse of above)
        if (
            isset(self::ELEMENT_ADVANTAGES[$defender_element])
            && self::ELEMENT_ADVANTAGES[$defender_element] === $attacker_element
        ) {
            return ELEMENT_WEAK_MULTIPLIER;
        }

        return ELEMENT_NEUTRAL_MULTIPLIER;
    }

    /**
     * Apply RNG variation to damage (+/- 5%)
     * 
     * @param float $damage Base damage
     * @return float Varied damage
     */
    private function applyRngVariation(float $damage): float
    {
        $min = DAMAGE_RNG_MIN; // 0.95
        $max = DAMAGE_RNG_MAX; // 1.05

        // Generate random float between min and max
        $rng = $min + (mt_rand() / mt_getrandmax()) * ($max - $min);

        return $damage * $rng;
    }

    /**
     * Get pet data with species info for battle
     * 
     * @param int $pet_id Pet ID
     * @return array|null Pet data or null if not found
     */
    public function getPetForBattle(int $pet_id): ?array
    {
        $query = "SELECT up.*, 
                         ps.name as species_name, 
                         ps.element, 
                         ps.base_attack, 
                         ps.base_defense, 
                         ps.base_speed,
                         ps.img_adult,
                         ps.rarity
                  FROM user_pets up
                  JOIN pet_species ps ON up.species_id = ps.id
                  WHERE up.id = ? AND up.status = 'ALIVE'";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $pet_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $pet = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $pet ?: null;
    }

    /**
     * Get skills for a pet
     * 
     * @param int $species_id Species ID
     * @return array List of skills
     */
    public function getPetSkills(int $species_id): array
    {
        $query = "SELECT * FROM pet_skills WHERE species_id = ? ORDER BY skill_slot";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $species_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $skills = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $skills[] = $row;
        }
        mysqli_stmt_close($stmt);

        // If no skills defined, return default skills based on element
        if (empty($skills)) {
            $skills = $this->getDefaultSkills($species_id);
        }

        return $skills;
    }

    /**
     * Get default skills if none defined in database
     */
    private function getDefaultSkills(int $species_id): array
    {
        // Get pet's element
        $query = "SELECT element FROM pet_species WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $species_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $species = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $element = $species['element'] ?? 'Fire';

        return [
            ['id' => 0, 'skill_name' => 'Basic Attack', 'base_damage' => 25, 'skill_element' => $element, 'skill_slot' => 1, 'is_special' => 0],
            ['id' => 0, 'skill_name' => 'Power Strike', 'base_damage' => 40, 'skill_element' => $element, 'skill_slot' => 2, 'is_special' => 0],
            ['id' => 0, 'skill_name' => 'Special Attack', 'base_damage' => 60, 'skill_element' => $element, 'skill_slot' => 3, 'is_special' => 1],
            ['id' => 0, 'skill_name' => 'Ultimate', 'base_damage' => 80, 'skill_element' => $element, 'skill_slot' => 4, 'is_special' => 1],
        ];
    }
}
