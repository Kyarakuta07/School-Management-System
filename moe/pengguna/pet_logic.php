<?php
/**
 * MOE Pet System - Core Logic Functions
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * This file contains all core pet mechanics:
 * - Lazy stat calculation (hunger/health decay)
 * - Evolution stage detection
 * - Gacha system with weighted rarity
 * - Battle calculation
 * - Buff system integration
 */

// ================================================
// CONFIGURATION CONSTANTS
// ================================================

// Stat decay rates (per hour)
define('HUNGER_DECAY_PER_HOUR', 5);      // Pet loses 5 hunger per hour
define('MOOD_DECAY_PER_HOUR', 3);        // Pet loses 3 mood per hour
define('HEALTH_DECAY_WHEN_STARVING', 8); // When hunger=0, lose 8 health/hour

// Evolution level thresholds
define('LEVEL_BABY', 5);   // Egg hatches to baby at level 5
define('LEVEL_ADULT', 15); // Baby evolves to adult at level 15

// EXP required per level (exponential growth)
define('BASE_EXP_PER_LEVEL', 100);
define('EXP_GROWTH_RATE', 1.2);

// Gacha rarity weights (must sum to 100)
define('GACHA_RARITY_WEIGHTS', [
    'Common'    => 60,
    'Rare'      => 25,
    'Epic'      => 12,
    'Legendary' => 3
]);

// Gacha costs
define('GACHA_COST_NORMAL', 100);
define('GACHA_COST_PREMIUM', 300);

// Battle reward ranges
define('BATTLE_WIN_GOLD_MIN', 20);
define('BATTLE_WIN_GOLD_MAX', 50);
define('BATTLE_WIN_EXP_MIN', 30);
define('BATTLE_WIN_EXP_MAX', 60);

// Shelter protection (pet doesn't decay stats but also doesn't gain EXP)
define('SHELTER_ENABLED', true);

// ================================================
// LAZY STAT CALCULATION
// ================================================

/**
 * Updates pet stats based on elapsed time since last update
 * This is the "lazy calculation" - stats are computed on-demand
 * 
 * @param mysqli $conn Database connection
 * @param int $pet_id The pet's ID
 * @return array Updated pet data or false on failure
 */
function updatePetStats($conn, $pet_id) {
    // Get current pet data
    $stmt = mysqli_prepare($conn, "SELECT * FROM user_pets WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $pet_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$pet) return false;
    
    // Skip calculation if pet is in shelter or dead
    if ($pet['status'] === 'SHELTER' || $pet['status'] === 'DEAD') {
        return $pet;
    }
    
    // Calculate time elapsed since last update
    $current_time = time();
    $last_update = (int)$pet['last_update_timestamp'];
    $hours_elapsed = ($current_time - $last_update) / 3600;
    
    // Only update if at least 1 minute has passed
    if ($hours_elapsed < (1/60)) {
        return $pet;
    }
    
    // Calculate new stats
    $new_hunger = max(0, $pet['hunger'] - (HUNGER_DECAY_PER_HOUR * $hours_elapsed));
    $new_mood = max(0, $pet['mood'] - (MOOD_DECAY_PER_HOUR * $hours_elapsed));
    $new_health = $pet['health'];
    $new_status = $pet['status'];
    
    // If starving (hunger = 0), health starts declining
    if ($new_hunger <= 0) {
        // Calculate how many hours the pet has been starving
        $starving_hours = $hours_elapsed; // Simplified - assume starving entire time if now at 0
        $new_health = max(0, $pet['health'] - (HEALTH_DECAY_WHEN_STARVING * $starving_hours));
    }
    
    // If health reaches 0, pet dies
    if ($new_health <= 0) {
        $new_health = 0;
        $new_status = 'DEAD';
    }
    
    // Update the database
    $update_stmt = mysqli_prepare($conn, 
        "UPDATE user_pets SET hunger = ?, mood = ?, health = ?, status = ?, last_update_timestamp = ? WHERE id = ?"
    );
    mysqli_stmt_bind_param($update_stmt, "ddssii", 
        $new_hunger, $new_mood, $new_health, $new_status, $current_time, $pet_id
    );
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    // Return updated pet data
    $pet['hunger'] = round($new_hunger);
    $pet['mood'] = round($new_mood);
    $pet['health'] = round($new_health);
    $pet['status'] = $new_status;
    $pet['last_update_timestamp'] = $current_time;
    
    return $pet;
}

/**
 * Get all pets for a user, with updated stats
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id The user's ID (nethera.id_nethera)
 * @return array Array of pet data with species info
 */
function getUserPetsWithStats($conn, $user_id) {
    $query = "SELECT up.*, ps.name as species_name, ps.element, ps.rarity, 
                     ps.img_egg, ps.img_baby, ps.img_adult,
                     ps.passive_buff_type, ps.passive_buff_value,
                     ps.base_attack, ps.base_defense, ps.base_speed, ps.description
              FROM user_pets up
              JOIN pet_species ps ON up.species_id = ps.id
              WHERE up.user_id = ?
              ORDER BY up.is_active DESC, up.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $pets = [];
    while ($pet = mysqli_fetch_assoc($result)) {
        // Update stats lazily for each pet
        $updated_pet = updatePetStats($conn, $pet['id']);
        if ($updated_pet) {
            // Merge updated stats with species info
            $pet['hunger'] = $updated_pet['hunger'];
            $pet['mood'] = $updated_pet['mood'];
            $pet['health'] = $updated_pet['health'];
            $pet['status'] = $updated_pet['status'];
            $pet['last_update_timestamp'] = $updated_pet['last_update_timestamp'];
            
            // Add evolution stage
            $pet['evolution_stage'] = getEvolutionStage($pet['level']);
            $pet['current_image'] = getEvolutionImage($pet);
        }
        $pets[] = $pet;
    }
    
    mysqli_stmt_close($stmt);
    return $pets;
}

// ================================================
// EVOLUTION SYSTEM
// ================================================

/**
 * Determine evolution stage based on level
 * 
 * @param int $level Pet's current level
 * @return string 'egg', 'baby', or 'adult'
 */
function getEvolutionStage($level) {
    if ($level < LEVEL_BABY) return 'egg';
    if ($level < LEVEL_ADULT) return 'baby';
    return 'adult';
}

/**
 * Get the correct image path based on evolution stage
 * 
 * @param array $pet Pet data including species image paths
 * @return string Image path relative to assets/pets/
 */
function getEvolutionImage($pet) {
    $stage = getEvolutionStage($pet['level']);
    switch ($stage) {
        case 'egg': return $pet['img_egg'];
        case 'baby': return $pet['img_baby'];
        case 'adult': return $pet['img_adult'];
        default: return $pet['img_egg'];
    }
}

/**
 * Calculate EXP required for next level
 * 
 * @param int $current_level Current level
 * @return int EXP needed to reach next level
 */
function getExpForNextLevel($current_level) {
    return floor(BASE_EXP_PER_LEVEL * pow(EXP_GROWTH_RATE, $current_level - 1));
}

/**
 * Add EXP to a pet and handle level ups
 * 
 * @param mysqli $conn Database connection
 * @param int $pet_id Pet ID
 * @param int $exp_amount Amount of EXP to add
 * @return array Result with level_up info
 */
function addExpToPet($conn, $pet_id, $exp_amount) {
    $stmt = mysqli_prepare($conn, "SELECT level, exp FROM user_pets WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $pet_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$pet) return ['success' => false, 'error' => 'Pet not found'];
    
    $current_level = $pet['level'];
    $current_exp = $pet['exp'] + $exp_amount;
    $level_ups = 0;
    $old_stage = getEvolutionStage($current_level);
    
    // Process level ups
    while ($current_exp >= getExpForNextLevel($current_level)) {
        $current_exp -= getExpForNextLevel($current_level);
        $current_level++;
        $level_ups++;
    }
    
    $new_stage = getEvolutionStage($current_level);
    $evolved = ($old_stage !== $new_stage);
    
    // Update database
    $update_stmt = mysqli_prepare($conn, "UPDATE user_pets SET level = ?, exp = ? WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "iii", $current_level, $current_exp, $pet_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    return [
        'success' => true,
        'new_level' => $current_level,
        'new_exp' => $current_exp,
        'level_ups' => $level_ups,
        'evolved' => $evolved,
        'new_stage' => $new_stage
    ];
}

// ================================================
// GACHA SYSTEM
// ================================================

/**
 * Perform a gacha roll and give user a new pet
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $gacha_type 1=Normal, 2=Rare+, 3=Epic+
 * @return array Result containing new pet info or error
 */
function performGacha($conn, $user_id, $gacha_type = 1) {
    // Determine rarity based on gacha type
    $rarity = rollRarity($gacha_type);
    
    // Get random species of that rarity
    $stmt = mysqli_prepare($conn, 
        "SELECT * FROM pet_species WHERE rarity = ? ORDER BY RAND() LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "s", $rarity);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $species = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$species) {
        return ['success' => false, 'error' => 'No species found for rarity: ' . $rarity];
    }
    
    // Determine if shiny (1% chance)
    $is_shiny = (rand(1, 100) === 1) ? 1 : 0;
    $shiny_hue = $is_shiny ? rand(30, 330) : 0;
    
    // Create the new pet
    $current_time = time();
    $insert_stmt = mysqli_prepare($conn,
        "INSERT INTO user_pets (user_id, species_id, nickname, level, exp, health, hunger, mood, status, is_shiny, shiny_hue, last_update_timestamp, is_active)
         VALUES (?, ?, NULL, 1, 0, 100, 100, 100, 'ALIVE', ?, ?, ?, 0)"
    );
    mysqli_stmt_bind_param($insert_stmt, "iiiii", 
        $user_id, $species['id'], $is_shiny, $shiny_hue, $current_time
    );
    mysqli_stmt_execute($insert_stmt);
    $new_pet_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_stmt);
    
    // If user has no active pet, make this one active
    $check_active = mysqli_prepare($conn, 
        "SELECT COUNT(*) as cnt FROM user_pets WHERE user_id = ? AND is_active = 1"
    );
    mysqli_stmt_bind_param($check_active, "i", $user_id);
    mysqli_stmt_execute($check_active);
    $active_result = mysqli_stmt_get_result($check_active);
    $active_count = mysqli_fetch_assoc($active_result)['cnt'];
    mysqli_stmt_close($check_active);
    
    if ($active_count == 0) {
        $set_active = mysqli_prepare($conn, "UPDATE user_pets SET is_active = 1 WHERE id = ?");
        mysqli_stmt_bind_param($set_active, "i", $new_pet_id);
        mysqli_stmt_execute($set_active);
        mysqli_stmt_close($set_active);
    }
    
    return [
        'success' => true,
        'pet_id' => $new_pet_id,
        'species' => $species,
        'is_shiny' => $is_shiny,
        'shiny_hue' => $shiny_hue,
        'rarity' => $rarity
    ];
}

/**
 * Roll for rarity based on gacha type
 * 
 * @param int $gacha_type 1=Normal (all rarities), 2=Rare+ guaranteed, 3=Epic+ guaranteed
 * @return string Rarity name
 */
function rollRarity($gacha_type) {
    $weights = GACHA_RARITY_WEIGHTS;
    
    // Adjust weights based on gacha type
    if ($gacha_type == 2) {
        // Rare or better guaranteed
        $weights['Common'] = 0;
        $weights['Rare'] = 70;
        $weights['Epic'] = 22;
        $weights['Legendary'] = 8;
    } else if ($gacha_type == 3) {
        // Epic or better guaranteed
        $weights['Common'] = 0;
        $weights['Rare'] = 0;
        $weights['Epic'] = 85;
        $weights['Legendary'] = 15;
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

// ================================================
// FEEDING & ITEM USAGE
// ================================================

/**
 * Use an item on a pet
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $pet_id Pet's ID
 * @param int $item_id Shop item ID
 * @return array Result of item usage
 */
function useItemOnPet($conn, $user_id, $pet_id, $item_id) {
    // Verify user owns the pet
    $pet_check = mysqli_prepare($conn, "SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($pet_check, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($pet_check);
    $pet_result = mysqli_stmt_get_result($pet_check);
    $pet = mysqli_fetch_assoc($pet_result);
    mysqli_stmt_close($pet_check);
    
    if (!$pet) {
        return ['success' => false, 'error' => 'Pet not found or not owned by user'];
    }
    
    // Check user has item in inventory
    $inv_check = mysqli_prepare($conn, 
        "SELECT ui.*, si.effect_type, si.effect_value, si.name as item_name 
         FROM user_inventory ui 
         JOIN shop_items si ON ui.item_id = si.id 
         WHERE ui.user_id = ? AND ui.item_id = ? AND ui.quantity > 0"
    );
    mysqli_stmt_bind_param($inv_check, "ii", $user_id, $item_id);
    mysqli_stmt_execute($inv_check);
    $inv_result = mysqli_stmt_get_result($inv_check);
    $inventory = mysqli_fetch_assoc($inv_result);
    mysqli_stmt_close($inv_check);
    
    if (!$inventory) {
        return ['success' => false, 'error' => 'Item not in inventory'];
    }
    
    // Apply item effect
    $effect_type = $inventory['effect_type'];
    $effect_value = $inventory['effect_value'];
    $result_message = '';
    
    switch ($effect_type) {
        case 'food':
            // Can't feed dead pet
            if ($pet['status'] === 'DEAD') {
                return ['success' => false, 'error' => 'Cannot feed a dead pet. Revive first!'];
            }
            $new_hunger = min(100, $pet['hunger'] + $effect_value);
            $stmt = mysqli_prepare($conn, "UPDATE user_pets SET hunger = ?, last_update_timestamp = ? WHERE id = ?");
            $current_time = time();
            mysqli_stmt_bind_param($stmt, "iii", $new_hunger, $current_time, $pet_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $result_message = "Fed pet! Hunger restored by $effect_value.";
            break;
            
        case 'potion':
            if ($pet['status'] === 'DEAD') {
                return ['success' => false, 'error' => 'Cannot heal a dead pet. Revive first!'];
            }
            $new_health = min(100, $pet['health'] + $effect_value);
            $stmt = mysqli_prepare($conn, "UPDATE user_pets SET health = ?, last_update_timestamp = ? WHERE id = ?");
            $current_time = time();
            mysqli_stmt_bind_param($stmt, "iii", $new_health, $current_time, $pet_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $result_message = "Healed pet! Health restored by $effect_value.";
            break;
            
        case 'revive':
            if ($pet['status'] !== 'DEAD') {
                return ['success' => false, 'error' => 'Pet is not dead!'];
            }
            $new_health = $effect_value; // effect_value is revival health percentage
            $stmt = mysqli_prepare($conn, "UPDATE user_pets SET health = ?, hunger = 50, mood = 50, status = 'ALIVE', last_update_timestamp = ? WHERE id = ?");
            $current_time = time();
            mysqli_stmt_bind_param($stmt, "iii", $new_health, $current_time, $pet_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $result_message = "Pet revived with $effect_value% health!";
            break;
            
            
        case 'exp_boost':
            if ($pet['status'] === 'DEAD') {
                return ['success' => false, 'error' => 'Cannot give EXP to a dead pet!'];
            }
            $exp_result = addExpToPet($conn, $pet_id, $effect_value);
            if ($exp_result['evolved']) {
                $result_message = "Pet gained $effect_value EXP! EVOLVED to " . $exp_result['new_stage'] . "!";
            } else if ($exp_result['level_ups'] > 0) {
                $result_message = "Pet gained $effect_value EXP! Level up! Now level " . $exp_result['new_level'] . ".";
            } else {
                $result_message = "Pet gained $effect_value EXP!";
            }
            break;
            
        default:
            return ['success' => false, 'error' => 'Unknown item type'];

            // ... case exp_boost selesai ...

        // TAMBAHKAN BAGIAN INI:
        case 'gacha_ticket':
            // effect_value 1 = Bronze, 2 = Silver, 3 = Gold
            $tier = $inventory['effect_value']; 
            
            // Panggil fungsi gacha (Gratis, tanpa potong gold)
            $gacha_result = performGacha($conn, $user_id, $tier);
            
            if ($gacha_result['success']) {
                $species_name = $gacha_result['species']['name'];
                $result_message = "Egg hatched! You got a $species_name!";
                
                // Kita kirim data lengkap gacha biar bisa muncul animasi di layar
                // Return array ini akan ditangkap oleh JavaScript
                return [
                    'success' => true,
                    'message' => $result_message,
                    'item_used' => $inventory['item_name'],
                    'is_gacha' => true,      // Penanda buat JS
                    'gacha_data' => $gacha_result // Data pet baru
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to hatch egg.'];
            }
            break;

        default:
            return ['success' => false, 'error' => 'Unknown item type'];
    }

    
    
    // Deduct item from inventory
    $deduct_stmt = mysqli_prepare($conn, "UPDATE user_inventory SET quantity = quantity - 1 WHERE user_id = ? AND item_id = ?");
    mysqli_stmt_bind_param($deduct_stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($deduct_stmt);
    mysqli_stmt_close($deduct_stmt);
    
    // Remove inventory entry if quantity is 0
    mysqli_query($conn, "DELETE FROM user_inventory WHERE quantity <= 0");
    
    return [
        'success' => true,
        'message' => $result_message,
        'item_used' => $inventory['item_name']
    ];
}

// ================================================
// BATTLE SYSTEM (ASYNC PVP)
// ================================================

/**
 * Calculate and record a battle between two pets
 * 
 * @param mysqli $conn Database connection
 * @param int $attacker_pet_id Attacking pet's ID
 * @param int $defender_pet_id Defending pet's ID
 * @return array Battle result
 */
function performBattle($conn, $attacker_pet_id, $defender_pet_id) {
    // Get both pets with species data
    $query = "SELECT up.*, ps.name as species_name, ps.element, ps.base_attack, ps.base_defense, ps.base_speed
              FROM user_pets up
              JOIN pet_species ps ON up.species_id = ps.id
              WHERE up.id IN (?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $attacker_pet_id, $defender_pet_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $pets = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $pets[$row['id']] = $row;
    }
    mysqli_stmt_close($stmt);
    
    if (count($pets) != 2) {
        return ['success' => false, 'error' => 'One or both pets not found'];
    }
    
    $attacker = $pets[$attacker_pet_id];
    $defender = $pets[$defender_pet_id];
    
    // Check both pets are alive
    if ($attacker['status'] !== 'ALIVE' || $defender['status'] !== 'ALIVE') {
        return ['success' => false, 'error' => 'Both pets must be alive to battle'];
    }
    
    // Calculate effective stats (base + level bonus)
    $atk_power = calculateBattleStat($attacker, 'attack');
    $atk_defense = calculateBattleStat($attacker, 'defense');
    $atk_speed = calculateBattleStat($attacker, 'speed');
    
    $def_power = calculateBattleStat($defender, 'attack');
    $def_defense = calculateBattleStat($defender, 'defense');
    $def_speed = calculateBattleStat($defender, 'speed');
    
    // Element advantage multiplier
    $element_mult = getElementMultiplier($attacker['element'], $defender['element']);
    
    // Battle calculation
    $battle_log = [];
    $atk_hp = 100;
    $def_hp = 100;
    $round = 1;
    
    // Determine who goes first (speed + small random factor)
    $atk_initiative = $atk_speed + rand(1, 20);
    $def_initiative = $def_speed + rand(1, 20);
    
    $attacker_first = ($atk_initiative >= $def_initiative);
    
    while ($atk_hp > 0 && $def_hp > 0 && $round <= 10) {
        if ($attacker_first) {
            // Attacker attacks
            $damage = max(1, ($atk_power * $element_mult) - ($def_defense * 0.5) + rand(-5, 10));
            $def_hp -= $damage;
            $battle_log[] = [
                'round' => $round,
                'actor' => $attacker['species_name'],
                'action' => 'attack',
                'damage' => round($damage),
                'target_hp' => max(0, round($def_hp))
            ];
            
            if ($def_hp <= 0) break;
            
            // Defender counterattacks
            $counter_mult = getElementMultiplier($defender['element'], $attacker['element']);
            $damage = max(1, ($def_power * $counter_mult) - ($atk_defense * 0.5) + rand(-5, 10));
            $atk_hp -= $damage;
            $battle_log[] = [
                'round' => $round,
                'actor' => $defender['species_name'],
                'action' => 'counter',
                'damage' => round($damage),
                'target_hp' => max(0, round($atk_hp))
            ];
        } else {
            // Defender attacks first
            $counter_mult = getElementMultiplier($defender['element'], $attacker['element']);
            $damage = max(1, ($def_power * $counter_mult) - ($atk_defense * 0.5) + rand(-5, 10));
            $atk_hp -= $damage;
            $battle_log[] = [
                'round' => $round,
                'actor' => $defender['species_name'],
                'action' => 'attack',
                'damage' => round($damage),
                'target_hp' => max(0, round($atk_hp))
            ];
            
            if ($atk_hp <= 0) break;
            
            // Attacker counterattacks
            $damage = max(1, ($atk_power * $element_mult) - ($def_defense * 0.5) + rand(-5, 10));
            $def_hp -= $damage;
            $battle_log[] = [
                'round' => $round,
                'actor' => $attacker['species_name'],
                'action' => 'counter',
                'damage' => round($damage),
                'target_hp' => max(0, round($def_hp))
            ];
        }
        
        $round++;
    }
    
    // Determine winner
    $winner_pet_id = null;
    if ($atk_hp > $def_hp) {
        $winner_pet_id = $attacker_pet_id;
    } else if ($def_hp > $atk_hp) {
        $winner_pet_id = $defender_pet_id;
    }
    // if equal, it's a draw (winner_pet_id = null)
    
    // Calculate rewards
    $reward_gold = 0;
    $reward_exp = 0;
    if ($winner_pet_id) {
        $reward_gold = rand(BATTLE_WIN_GOLD_MIN, BATTLE_WIN_GOLD_MAX);
        $reward_exp = rand(BATTLE_WIN_EXP_MIN, BATTLE_WIN_EXP_MAX);
        
        // Give EXP to winning pet
        addExpToPet($conn, $winner_pet_id, $reward_exp);
    }
    
    // Record battle
    $log_json = json_encode($battle_log);
    $insert_battle = mysqli_prepare($conn,
        "INSERT INTO pet_battles (attacker_pet_id, defender_pet_id, winner_pet_id, battle_log, reward_gold, reward_exp)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($insert_battle, "iiisii", 
        $attacker_pet_id, $defender_pet_id, $winner_pet_id, $log_json, $reward_gold, $reward_exp
    );
    mysqli_stmt_execute($insert_battle);
    $battle_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_battle);
    
    return [
        'success' => true,
        'battle_id' => $battle_id,
        'winner_pet_id' => $winner_pet_id,
        'attacker' => [
            'pet_id' => $attacker_pet_id,
            'name' => $attacker['species_name'],
            'final_hp' => max(0, round($atk_hp))
        ],
        'defender' => [
            'pet_id' => $defender_pet_id,
            'name' => $defender['species_name'],
            'final_hp' => max(0, round($def_hp))
        ],
        'battle_log' => $battle_log,
        'rewards' => [
            'gold' => $reward_gold,
            'exp' => $reward_exp
        ]
    ];
}

/**
 * Calculate effective battle stat including level bonus
 */
function calculateBattleStat($pet, $stat_type) {
    $base_stat = 0;
    switch ($stat_type) {
        case 'attack': $base_stat = $pet['base_attack']; break;
        case 'defense': $base_stat = $pet['base_defense']; break;
        case 'speed': $base_stat = $pet['base_speed']; break;
    }
    
    // Add level bonus (2% per level)
    $level_bonus = $base_stat * ($pet['level'] * 0.02);
    
    // Mood affects performance (-20% at 0 mood, +10% at 100 mood)
    $mood_modifier = 0.9 + ($pet['mood'] / 500); // 0.9 to 1.1
    
    return ($base_stat + $level_bonus) * $mood_modifier;
}

/**
 * Get element advantage multiplier
 * Fire > Air > Earth > Water > Fire
 * Light <-> Dark (mutual weakness)
 */
function getElementMultiplier($attacker_element, $defender_element) {
    $advantages = [
        'Fire' => 'Air',
        'Air' => 'Earth',
        'Earth' => 'Water',
        'Water' => 'Fire',
        'Light' => 'Dark',
        'Dark' => 'Light'
    ];
    
    if (isset($advantages[$attacker_element]) && $advantages[$attacker_element] === $defender_element) {
        return 1.3; // 30% bonus damage
    }
    
    // Check if at disadvantage
    if (isset($advantages[$defender_element]) && $advantages[$defender_element] === $attacker_element) {
        return 0.8; // 20% less damage
    }
    
    return 1.0; // neutral
}

// ================================================
// SCHOOL BUFF INTEGRATION
// ================================================

/**
 * Get active pet buff for a school activity
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param string $activity_type Type of school activity (sports, study, art, music)
 * @return array Buff info or empty if no buff
 */
function getActivePetBuff($conn, $user_id, $activity_type) {
    // Get user's active pet
    $query = "SELECT up.*, ps.passive_buff_type, ps.passive_buff_value, ps.name as species_name
              FROM user_pets up
              JOIN pet_species ps ON up.species_id = ps.id
              WHERE up.user_id = ? AND up.is_active = 1 AND up.status = 'ALIVE'
              LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$pet) {
        return ['has_buff' => false];
    }
    
    $buff_type = $pet['passive_buff_type'];
    $buff_value = $pet['passive_buff_value'];
    
    // Check if buff applies to this activity
    $applies = false;
    if ($buff_type === 'all_exp') {
        $applies = true;
    } else if ($buff_type === $activity_type . '_exp') {
        $applies = true;
    }
    
    if ($applies) {
        return [
            'has_buff' => true,
            'pet_name' => $pet['nickname'] ?? $pet['species_name'],
            'buff_type' => $buff_type,
            'buff_value' => $buff_value,
            'message' => "+" . $buff_value . "% EXP from " . ($pet['nickname'] ?? $pet['species_name'])
        ];
    }
    
    return ['has_buff' => false];
}

// ================================================
// SHELTER SYSTEM
// ================================================

/**
 * Toggle shelter mode for a pet
 * In shelter: stats don't decay, but pet can't gain EXP or battle
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $pet_id Pet's ID
 * @return array Result
 */
function toggleShelter($conn, $user_id, $pet_id) {
    // Verify ownership
    $stmt = mysqli_prepare($conn, "SELECT status FROM user_pets WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$pet) {
        return ['success' => false, 'error' => 'Pet not found'];
    }
    
    if ($pet['status'] === 'DEAD') {
        return ['success' => false, 'error' => 'Cannot shelter a dead pet'];
    }
    
    // Toggle status
    $new_status = ($pet['status'] === 'SHELTER') ? 'ALIVE' : 'SHELTER';
    $is_active = ($new_status === 'SHELTER') ? 0 : 0; // Sheltered pets can't be active
    
    $update = mysqli_prepare($conn, "UPDATE user_pets SET status = ?, is_active = ?, last_update_timestamp = ? WHERE id = ?");
    $current_time = time();
    mysqli_stmt_bind_param($update, "siii", $new_status, $is_active, $current_time, $pet_id);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
    
    return [
        'success' => true,
        'new_status' => $new_status,
        'message' => ($new_status === 'SHELTER') 
            ? 'Pet is now in shelter. Stats will not decay.' 
            : 'Pet has left the shelter and is now active!'
    ];
}

/**
 * Set a pet as the active pet (for buffs and display)
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $pet_id Pet's ID
 * @return array Result
 */
function setActivePet($conn, $user_id, $pet_id) {
    // Verify ownership and status
    $stmt = mysqli_prepare($conn, "SELECT status FROM user_pets WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$pet) {
        return ['success' => false, 'error' => 'Pet not found'];
    }
    
    if ($pet['status'] !== 'ALIVE') {
        return ['success' => false, 'error' => 'Only alive pets can be set as active'];
    }
    
    // Deactivate all pets for this user
    $deactivate = mysqli_prepare($conn, "UPDATE user_pets SET is_active = 0 WHERE user_id = ?");
    mysqli_stmt_bind_param($deactivate, "i", $user_id);
    mysqli_stmt_execute($deactivate);
    mysqli_stmt_close($deactivate);
    
    // Activate selected pet
    $activate = mysqli_prepare($conn, "UPDATE user_pets SET is_active = 1 WHERE id = ?");
    mysqli_stmt_bind_param($activate, "i", $pet_id);
    mysqli_stmt_execute($activate);
    mysqli_stmt_close($activate);
    
    return ['success' => true, 'message' => 'Pet is now your active companion!'];
}
?>
