<?php
/**
 * MOE Pet System - Battle Engine
 * Dragon City-style 3v3 turn-based combat
 * 
 * Handles damage calculation, element advantages, and RNG.
 * All calculations are server-side for security.
 */

// Load constants and helpers
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/StatusEffects.php';

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
     * Calculate damage for an attack with IMPROVED FORMULA + STATUS EFFECTS
     * 
     * @param array $attacker_pet Attacker pet data with base_attack, level, rarity
     * @param array $skill Skill data with base_damage, skill_element, status_effect, status_chance
     * @param array $defender_pet Defender pet data with base_defense, element
     * @param array $attacker_effects Active status effects on attacker (for ATK modifiers)
     * @param array $defender_effects Active status effects on defender (for DEF modifiers)
     * @return array Result with damage_dealt, is_critical, element_advantage, status_applied, logs
     */
    public function calculateDamage(array $attacker_pet, array $skill, array $defender_pet, array $attacker_effects = [], array $defender_effects = []): array
    {
        $logs = [];

        // Get skill info
        $skill_element = $skill['skill_element'] ?? $attacker_pet['element'];
        $skill_name = $skill['skill_name'] ?? 'Attack';
        $base_damage = (int) ($skill['base_damage'] ?? 25);

        // Get stats
        $attack_power = (int) ($attacker_pet['base_attack'] ?? $attacker_pet['atk'] ?? 10);
        $defense_power = (int) ($defender_pet['base_defense'] ?? $defender_pet['def'] ?? 10);
        $attacker_level = (int) ($attacker_pet['level'] ?? 1);
        $attacker_rarity = $attacker_pet['rarity'] ?? 'Common';

        // ============================================
        // VARIANCE MECHANICS
        // ============================================

        // 1. Dodge check (5% chance)
        $is_dodge = (mt_rand(1, 100) <= 5);
        if ($is_dodge) {
            $logs[] = "💨 {$defender_pet['species_name']} dodged the attack!";
            return [
                'damage_dealt' => 0,
                'is_dodge' => true,
                'is_critical' => false,
                'is_glancing' => false,
                'is_lucky' => false,
                'element_advantage' => 'neutral',
                'skill_used' => $skill_name,
                'skill_element' => $skill_element,
                'logs' => $logs
            ];
        }

        // 2. Glancing blow (10% chance)
        $is_glancing = (mt_rand(1, 100) <= 10);

        // 3. Lucky hit (5% chance, only if not glancing)
        $is_lucky = !$is_glancing && (mt_rand(1, 100) <= 5);

        // ============================================
        // IMPROVED DAMAGE FORMULA
        // ============================================

        // 1. Raw damage = Base + ATK scaling (40% of ATK)
        $atk_scaling = $attack_power * 0.4;
        $raw_damage = $base_damage + $atk_scaling;

        // 2. Level bonus (2% per level, capped at 2x)
        $level_multiplier = min(2.0, 1 + ($attacker_level * 0.02));

        // 3. Defense reduction with DIMINISHING RETURNS
        $defense_reduction = 100 / (100 + $defense_power);

        // 4. Rarity bonus
        $rarity_bonus = $this->getRarityBonus($attacker_rarity);

        $logs[] = "{$attacker_pet['species_name']} used {$skill_name}!";

        // 5. Element advantage
        $element_mult = $this->getElementAdvantage($skill_element, $defender_pet['element'] ?? 'Fire');
        $element_advantage = 'neutral';

        if ($element_mult >= ELEMENT_STRONG_MULTIPLIER) {
            $element_advantage = 'super_effective';
            $logs[] = "It's super effective! (2x damage)";
        } elseif ($element_mult <= ELEMENT_WEAK_MULTIPLIER) {
            $element_advantage = 'not_effective';
            $logs[] = "It's not very effective... (0.5x damage)";
        }

        // 6. Critical hit (15% chance, 1.5x damage)
        $is_critical = (mt_rand(1, 100) <= 15);
        $crit_multiplier = $is_critical ? 1.5 : 1.0;
        if ($is_critical) {
            $logs[] = "CRITICAL HIT!";
        }

        // 7. RNG variation (+/- 5%)
        $variance = $this->applyRngVariation(1.0);

        // 8. Glancing blow penalty (50% damage)
        $glancing_mult = $is_glancing ? 0.5 : 1.0;
        if ($is_glancing) {
            $logs[] = "⚡ Glancing blow!";
        }

        // 9. Lucky hit bonus (+25% damage)
        $lucky_mult = $is_lucky ? 1.25 : 1.0;
        if ($is_lucky) {
            $logs[] = "🍀 Lucky hit!";
        }

        // ============================================
        // STATUS EFFECT MODIFIERS
        // ============================================

        // 10. ATK Down modifier on attacker
        $atk_modifier = StatusEffects::getAttackModifier($attacker_effects);

        // 11. DEF Down modifier on defender (increases damage taken)
        $def_modifier = StatusEffects::getDefenseModifier($defender_effects);

        // FINAL DAMAGE CALCULATION with variance and status modifiers
        $damage = $raw_damage * $level_multiplier * $defense_reduction * (1 + $rarity_bonus) * $element_mult * $crit_multiplier * $variance * $glancing_mult * $lucky_mult * $atk_modifier * $def_modifier;

        // Round to integer
        $damage_dealt = (int) round($damage);
        $damage_dealt = max(1, $damage_dealt); // Minimum 1 damage

        $logs[] = "Dealt {$damage_dealt} damage to {$defender_pet['species_name']}!";

        // ============================================
        // TRY TO APPLY STATUS EFFECT FROM SKILL
        // ============================================
        $status_applied = null;
        if (!empty($skill['status_effect'])) {
            $statusHandler = new StatusEffects($this->conn);
            $status_applied = $statusHandler->tryApplyEffect($skill, $defender_pet, $defender_effects);

            if ($status_applied) {
                if (!empty($status_applied['applied'])) {
                    $logs[] = "{$status_applied['icon']} {$defender_pet['species_name']} is now {$status_applied['name']}!";
                } elseif (!empty($status_applied['refreshed'])) {
                    $config = StatusEffects::getConfig($status_applied['type']);
                    $logs[] = "{$config['icon']} {$defender_pet['species_name']}'s {$config['name']} was extended!";
                } elseif (!empty($status_applied['resisted'])) {
                    $config = StatusEffects::getConfig($status_applied['type']);
                    $logs[] = "{$defender_pet['species_name']} resisted {$config['name']}!";
                }
            }
        }

        return [
            'damage_dealt' => $damage_dealt,
            'is_dodge' => false,
            'is_critical' => $is_critical,
            'is_glancing' => $is_glancing,
            'is_lucky' => $is_lucky,
            'element_advantage' => $element_advantage,
            'skill_used' => $skill_name,
            'skill_element' => $skill_element,
            'status_applied' => $status_applied,
            'logs' => $logs
        ];
    }

    /**
     * Get damage bonus based on pet rarity
     * 
     * @param string $rarity Pet rarity (Common, Rare, Epic, Legendary)
     * @return float Bonus multiplier (0.0 to 0.25)
     */
    private function getRarityBonus(string $rarity): float
    {
        $bonuses = [
            'Common' => 0.0,
            'Rare' => 0.05,
            'Epic' => 0.12,
            'Legendary' => 0.25
        ];
        return $bonuses[$rarity] ?? 0.0;
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
                         ps.img_egg,
                         ps.img_baby,
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
