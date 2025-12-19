<?php
/**
 * MOE Pet System - Central Loader
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * This file loads all pet logic modules in the correct order.
 * Include this file instead of pet_logic.php to use the modular structure.
 * 
 * Usage:
 *   include 'pet/pet_loader.php';
 */

$pet_logic_path = __DIR__ . '/logic/';

// Load constants first (required by other modules)
require_once $pet_logic_path . 'constants.php';

// Load core modules (in dependency order)
require_once $pet_logic_path . 'evolution.php';  // Required by stats.php
require_once $pet_logic_path . 'stats.php';      // Depends on evolution.php
require_once $pet_logic_path . 'gacha.php';      // Independent
require_once $pet_logic_path . 'items.php';      // Depends on gacha.php, evolution.php
require_once $pet_logic_path . 'battle.php';     // Depends on evolution.php
require_once $pet_logic_path . 'shelter.php';    // Independent
require_once $pet_logic_path . 'buffs.php';      // Independent
require_once $pet_logic_path . 'rewards.php';    // Daily login rewards
