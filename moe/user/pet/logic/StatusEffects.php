<?php
/**
 * MOE Pet System - Status Effects Handler
 * 
 * Manages status effect constants, application, and processing.
 * Status effects are skill-based only.
 */

// Load database connection if not already loaded
require_once __DIR__ . '/constants.php';

class StatusEffects
{
    private $conn;

    // Status effect types
    public const BURN = 'burn';
    public const POISON = 'poison';
    public const FREEZE = 'freeze';
    public const STUN = 'stun';
    public const ATK_DOWN = 'atk_down';
    public const DEF_DOWN = 'def_down';

    // Effect categories
    public const DOT_EFFECTS = [self::BURN, self::POISON];
    public const CC_EFFECTS = [self::FREEZE, self::STUN];
    public const DEBUFF_EFFECTS = [self::ATK_DOWN, self::DEF_DOWN];

    // Effect configurations
    private const EFFECT_CONFIG = [
        self::BURN => [
            'icon' => '🔥',
            'name' => 'Burn',
            'damage_percent' => 5,  // 5% of max HP per turn
            'prevents_action' => false
        ],
        self::POISON => [
            'icon' => '☠️',
            'name' => 'Poison',
            'damage_percent' => 3,  // 3% of max HP per turn
            'prevents_action' => false
        ],
        self::FREEZE => [
            'icon' => '❄️',
            'name' => 'Freeze',
            'damage_percent' => 0,
            'prevents_action' => true
        ],
        self::STUN => [
            'icon' => '⚡',
            'name' => 'Stun',
            'damage_percent' => 0,
            'prevents_action' => true
        ],
        self::ATK_DOWN => [
            'icon' => '🔻',
            'name' => 'ATK Down',
            'damage_percent' => 0,
            'prevents_action' => false,
            'stat_modifier' => -0.25  // -25% attack
        ],
        self::DEF_DOWN => [
            'icon' => '🛡️',
            'name' => 'DEF Down',
            'damage_percent' => 0,
            'prevents_action' => false,
            'stat_modifier' => 0.25   // +25% damage taken
        ]
    ];

    // Cache for element resistances
    private $resistanceCache = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get configuration for a status effect
     */
    public static function getConfig(string $effect): ?array
    {
        return self::EFFECT_CONFIG[$effect] ?? null;
    }

    /**
     * Get all effect types
     */
    public static function getAllTypes(): array
    {
        return array_keys(self::EFFECT_CONFIG);
    }

    /**
     * Check if effect prevents action (CC)
     */
    public static function preventsAction(string $effect): bool
    {
        return self::EFFECT_CONFIG[$effect]['prevents_action'] ?? false;
    }

    /**
     * Get DOT damage for an effect
     * 
     * @param string $effect Effect type
     * @param int $maxHp Target's max HP
     * @return int Damage amount
     */
    public static function getDotDamage(string $effect, int $maxHp): int
    {
        $config = self::EFFECT_CONFIG[$effect] ?? null;
        if (!$config || $config['damage_percent'] <= 0) {
            return 0;
        }
        return (int) ceil($maxHp * ($config['damage_percent'] / 100));
    }

    /**
     * Get attack modifier from status effects
     * 
     * @param array $activeEffects Array of active status effects
     * @return float Multiplier (1.0 = normal, 0.75 = 25% reduced)
     */
    public static function getAttackModifier(array $activeEffects): float
    {
        $modifier = 1.0;
        foreach ($activeEffects as $effect) {
            if ($effect['type'] === self::ATK_DOWN) {
                $modifier += self::EFFECT_CONFIG[self::ATK_DOWN]['stat_modifier'];
            }
        }
        return max(0.5, $modifier); // Minimum 50% attack
    }

    /**
     * Get defense modifier from status effects
     * 
     * @param array $activeEffects Array of active status effects
     * @return float Multiplier (1.0 = normal, 1.25 = 25% more damage taken)
     */
    public static function getDefenseModifier(array $activeEffects): float
    {
        $modifier = 1.0;
        foreach ($activeEffects as $effect) {
            if ($effect['type'] === self::DEF_DOWN) {
                $modifier += self::EFFECT_CONFIG[self::DEF_DOWN]['stat_modifier'];
            }
        }
        return min(1.5, $modifier); // Maximum 50% extra damage
    }

    /**
     * Check if pet can act this turn
     * 
     * @param array $activeEffects Array of active status effects
     * @return array ['can_act' => bool, 'reason' => string|null]
     */
    public static function canAct(array $activeEffects): array
    {
        foreach ($activeEffects as $effect) {
            if (self::preventsAction($effect['type'])) {
                $config = self::getConfig($effect['type']);
                return [
                    'can_act' => false,
                    'reason' => $config['name'],
                    'icon' => $config['icon']
                ];
            }
        }
        return ['can_act' => true, 'reason' => null];
    }

    /**
     * Get element resistance to a status effect
     * 
     * @param string $element Pet's element
     * @param string $statusEffect Status effect type
     * @return int Resistance percentage (0-100)
     */
    public function getElementResistance(string $element, string $statusEffect): int
    {
        $cacheKey = $element . '_' . $statusEffect;

        if (isset($this->resistanceCache[$cacheKey])) {
            return $this->resistanceCache[$cacheKey];
        }

        $query = "SELECT resistance_percent FROM element_status_resistance 
                  WHERE element = ? AND resists_status = ?";
        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            return 0; // No resistance if table doesn't exist
        }

        mysqli_stmt_bind_param($stmt, "ss", $element, $statusEffect);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $resistance = $row ? (int) $row['resistance_percent'] : 0;
        $this->resistanceCache[$cacheKey] = $resistance;

        return $resistance;
    }

    /**
     * Try to apply a status effect
     * 
     * @param array $skill Skill data with status_effect, status_chance, status_duration
     * @param array $target Target pet data with element
     * @param array $currentEffects Target's current active effects
     * @return array|null Applied effect data or null if failed
     */
    public function tryApplyEffect(array $skill, array $target, array $currentEffects = []): ?array
    {
        $effectType = $skill['status_effect'] ?? null;
        $baseChance = (int) ($skill['status_chance'] ?? 0);
        $duration = (int) ($skill['status_duration'] ?? 0);

        // No effect to apply
        if (!$effectType || $baseChance <= 0 || $duration <= 0) {
            return null;
        }

        // Check if already affected by same effect
        foreach ($currentEffects as $effect) {
            if ($effect['type'] === $effectType) {
                // Refresh duration if higher
                if ($duration > $effect['turns_left']) {
                    return [
                        'type' => $effectType,
                        'turns_left' => $duration,
                        'refreshed' => true
                    ];
                }
                return null; // Already affected, don't stack
            }
        }

        // Calculate final chance with resistance
        $targetElement = $target['element'] ?? 'Fire';
        $resistance = $this->getElementResistance($targetElement, $effectType);
        $finalChance = $baseChance * (1 - ($resistance / 100));

        // Roll for effect
        $roll = mt_rand(1, 100);
        if ($roll <= $finalChance) {
            $config = self::getConfig($effectType);
            return [
                'type' => $effectType,
                'turns_left' => $duration,
                'icon' => $config['icon'],
                'name' => $config['name'],
                'applied' => true
            ];
        }

        // Check if resisted
        if ($resistance > 0 && $roll <= $baseChance) {
            return [
                'type' => $effectType,
                'resisted' => true,
                'element' => $targetElement
            ];
        }

        return null;
    }

    /**
     * Process turn-start effects (DOT, CC expiry)
     * 
     * @param array $pet Pet data with max HP
     * @param array &$effects Reference to active effects array
     * @return array ['damage' => int, 'logs' => array, 'effects_removed' => array]
     */
    public function processTurnStart(array $pet, array &$effects): array
    {
        $totalDamage = 0;
        $logs = [];
        $removed = [];
        $petName = $pet['nickname'] ?? $pet['species_name'] ?? 'Pet';
        $maxHp = (int) ($pet['max_hp'] ?? $pet['current_hp'] ?? 100);

        foreach ($effects as $key => &$effect) {
            $config = self::getConfig($effect['type']);

            // Process DOT damage
            if (in_array($effect['type'], self::DOT_EFFECTS)) {
                $damage = self::getDotDamage($effect['type'], $maxHp);
                $totalDamage += $damage;
                $logs[] = "{$config['icon']} {$petName} took {$damage} {$config['name']} damage!";
            }

            // Decrement turns
            $effect['turns_left']--;

            // Remove expired effects
            if ($effect['turns_left'] <= 0) {
                $logs[] = "{$config['icon']} {$petName} is no longer {$config['name']}!";
                $removed[] = $effect['type'];
                unset($effects[$key]);
            }
        }

        // Re-index array
        $effects = array_values($effects);

        return [
            'damage' => $totalDamage,
            'logs' => $logs,
            'effects_removed' => $removed
        ];
    }

    /**
     * Format effects for API response
     */
    public static function formatForResponse(array $effects): array
    {
        $formatted = [];
        foreach ($effects as $effect) {
            $config = self::getConfig($effect['type']);
            $formatted[] = [
                'type' => $effect['type'],
                'name' => $config['name'],
                'icon' => $config['icon'],
                'turns_left' => $effect['turns_left']
            ];
        }
        return $formatted;
    }
}
