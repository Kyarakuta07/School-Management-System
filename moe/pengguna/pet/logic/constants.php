<?php
/**
 * MOE Pet System - Configuration Constants
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * All system-wide constants for the pet system.
 */

// ================================================
// STAT DECAY RATES (per hour)
// ================================================
define('HUNGER_DECAY_PER_HOUR', 5);      // Pet loses 5 hunger per hour
define('MOOD_DECAY_PER_HOUR', 3);        // Pet loses 3 mood per hour
define('HEALTH_DECAY_WHEN_STARVING', 8); // When hunger=0, lose 8 health/hour

// ================================================
// EVOLUTION LEVEL THRESHOLDS
// ================================================
define('LEVEL_BABY', 5);   // Egg hatches to baby at level 5
define('LEVEL_ADULT', 15); // Baby evolves to adult at level 15

// ================================================
// EXP SYSTEM
// ================================================
define('BASE_EXP_PER_LEVEL', 100);
define('EXP_GROWTH_RATE', 1.2);

// ================================================
// GACHA SYSTEM
// ================================================
define('GACHA_RARITY_WEIGHTS', [
    'Common' => 60,
    'Rare' => 25,
    'Epic' => 12,
    'Legendary' => 3
]);

define('GACHA_COST_NORMAL', 100);
define('GACHA_COST_PREMIUM', 300);

// ================================================
// BATTLE SYSTEM
// ================================================
define('BATTLE_WIN_GOLD_MIN', 20);
define('BATTLE_WIN_GOLD_MAX', 50);
define('BATTLE_WIN_EXP_MIN', 30);
define('BATTLE_WIN_EXP_MAX', 60);

// ================================================
// SHELTER
// ================================================
define('SHELTER_ENABLED', true);
