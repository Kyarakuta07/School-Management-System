<?php
/**
 * MOE Pet System - Battle Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles pet battles, stat calculations, and element advantages.
 */

// Ensure constants are loaded
if (!defined('BATTLE_WIN_GOLD_MIN')) {
    require_once __DIR__ . '/constants.php';
}

/**
 * Calculate and record a battle between two pets
 *
 * @param mysqli $conn Database connection
 * @param int $attacker_pet_id Attacking pet's ID
 * @param int $defender_pet_id Defending pet's ID
 * @return array Battle result
 */
function performBattle($conn, $attacker_pet_id, $defender_pet_id)
{
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

    if ($attacker['status'] !== 'ALIVE' || $defender['status'] !== 'ALIVE') {
        return ['success' => false, 'error' => 'Both pets must be alive to battle'];
    }

    // Calculate effective stats
    $atk_power = calculateBattleStat($attacker, 'attack');
    $atk_defense = calculateBattleStat($attacker, 'defense');
    $atk_speed = calculateBattleStat($attacker, 'speed');

    $def_power = calculateBattleStat($defender, 'attack');
    $def_defense = calculateBattleStat($defender, 'defense');
    $def_speed = calculateBattleStat($defender, 'speed');

    $element_mult = getElementMultiplier($attacker['element'], $defender['element']);

    // Battle simulation
    $battle_log = [];
    $atk_hp = 100;
    $def_hp = 100;
    $round = 1;

    $atk_initiative = $atk_speed + rand(1, 20);
    $def_initiative = $def_speed + rand(1, 20);
    $attacker_first = ($atk_initiative >= $def_initiative);

    while ($atk_hp > 0 && $def_hp > 0 && $round <= 10) {
        if ($attacker_first) {
            $damage = max(1, ($atk_power * $element_mult) - ($def_defense * 0.5) + rand(-5, 10));
            $def_hp -= $damage;
            $battle_log[] = ['round' => $round, 'actor' => $attacker['species_name'], 'action' => 'attack', 'damage' => round($damage), 'target_hp' => max(0, round($def_hp))];

            if ($def_hp <= 0)
                break;

            $counter_mult = getElementMultiplier($defender['element'], $attacker['element']);
            $damage = max(1, ($def_power * $counter_mult) - ($atk_defense * 0.5) + rand(-5, 10));
            $atk_hp -= $damage;
            $battle_log[] = ['round' => $round, 'actor' => $defender['species_name'], 'action' => 'counter', 'damage' => round($damage), 'target_hp' => max(0, round($atk_hp))];
        } else {
            $counter_mult = getElementMultiplier($defender['element'], $attacker['element']);
            $damage = max(1, ($def_power * $counter_mult) - ($atk_defense * 0.5) + rand(-5, 10));
            $atk_hp -= $damage;
            $battle_log[] = ['round' => $round, 'actor' => $defender['species_name'], 'action' => 'attack', 'damage' => round($damage), 'target_hp' => max(0, round($atk_hp))];

            if ($atk_hp <= 0)
                break;

            $damage = max(1, ($atk_power * $element_mult) - ($def_defense * 0.5) + rand(-5, 10));
            $def_hp -= $damage;
            $battle_log[] = ['round' => $round, 'actor' => $attacker['species_name'], 'action' => 'counter', 'damage' => round($damage), 'target_hp' => max(0, round($def_hp))];
        }
        $round++;
    }

    // Determine winner
    $winner_pet_id = null;
    $loser_pet_id = null;
    $new_hp = 100;

    if ($atk_hp > $def_hp) {
        $winner_pet_id = $attacker_pet_id;
        $loser_pet_id = $defender_pet_id;
    } else if ($def_hp > $atk_hp) {
        $winner_pet_id = $defender_pet_id;
        $loser_pet_id = $attacker_pet_id;
    }

    // Calculate rewards
    $reward_gold = 0;
    $reward_exp = 0;
    if ($winner_pet_id) {
        $reward_gold = rand(BATTLE_WIN_GOLD_MIN, BATTLE_WIN_GOLD_MAX);
        $reward_exp = rand(BATTLE_WIN_EXP_MIN, BATTLE_WIN_EXP_MAX);
        addExpToPet($conn, $winner_pet_id, $reward_exp);
    }

    // Hardcore mechanics: HP loss & death
    $shield_blocked = false;

    if ($loser_pet_id) {
        $shield_check = mysqli_prepare($conn, "SELECT hp, has_shield FROM user_pets WHERE id = ?");
        mysqli_stmt_bind_param($shield_check, "i", $loser_pet_id);
        mysqli_stmt_execute($shield_check);
        $shield_result = mysqli_stmt_get_result($shield_check);
        $loser_data = mysqli_fetch_assoc($shield_result);
        $current_hp = $loser_data ? $loser_data['hp'] : 100;
        $has_shield = $loser_data ? ($loser_data['has_shield'] ?? 0) : 0;
        mysqli_stmt_close($shield_check);

        if ($has_shield) {
            $shield_blocked = true;
            $new_hp = $current_hp;
            $new_status = 'ALIVE';

            $consume_shield = mysqli_prepare($conn, "UPDATE user_pets SET has_shield = 0 WHERE id = ?");
            mysqli_stmt_bind_param($consume_shield, "i", $loser_pet_id);
            mysqli_stmt_execute($consume_shield);
            mysqli_stmt_close($consume_shield);

            $battle_log[] = ['round' => 'END', 'actor' => '🛡️ Divine Shield', 'action' => 'blocked', 'damage' => 0, 'target_hp' => $current_hp];
        } else {
            $new_hp = max(0, $current_hp - 20);
            $new_status = 'ALIVE';
            if ($new_hp <= 0) {
                $new_hp = 0;
                $new_status = 'DEAD';
            }
        }

        $update_hp = mysqli_prepare($conn, "UPDATE user_pets SET hp = ?, status = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_hp, "isi", $new_hp, $new_status, $loser_pet_id);
        mysqli_stmt_execute($update_hp);
        mysqli_stmt_close($update_hp);
    }

    // Record battle
    $log_json = json_encode($battle_log);
    $insert_battle = mysqli_prepare(
        $conn,
        "INSERT INTO pet_battles (attacker_pet_id, defender_pet_id, winner_pet_id, battle_log, reward_gold, reward_exp)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($insert_battle, "iiisii", $attacker_pet_id, $defender_pet_id, $winner_pet_id, $log_json, $reward_gold, $reward_exp);
    mysqli_stmt_execute($insert_battle);
    $battle_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_battle);

    return [
        'success' => true,
        'battle_id' => $battle_id,
        'winner_pet_id' => $winner_pet_id,
        'loser_pet_id' => $loser_pet_id,
        'attacker' => ['pet_id' => $attacker_pet_id, 'name' => $attacker['species_name'], 'final_hp' => max(0, round($atk_hp))],
        'defender' => ['pet_id' => $defender_pet_id, 'name' => $defender['species_name'], 'final_hp' => max(0, round($def_hp))],
        'battle_log' => $battle_log,
        'rewards' => ['gold' => $reward_gold, 'exp' => $reward_exp],
        'hardcore_damage' => ($loser_pet_id && !$shield_blocked) ? 20 : 0,
        'shield_blocked' => $shield_blocked,
        'pet_died' => $loser_pet_id && !$shield_blocked && $new_hp <= 0
    ];
}

/**
 * Calculate effective battle stat including level bonus
 */
function calculateBattleStat($pet, $stat_type)
{
    $base_stat = 0;
    switch ($stat_type) {
        case 'attack':
            $base_stat = $pet['base_attack'];
            break;
        case 'defense':
            $base_stat = $pet['base_defense'];
            break;
        case 'speed':
            $base_stat = $pet['base_speed'];
            break;
    }

    $level_bonus = $base_stat * ($pet['level'] * 0.02);
    $mood_modifier = 0.9 + ($pet['mood'] / 500);

    return ($base_stat + $level_bonus) * $mood_modifier;
}

/**
 * Get element advantage multiplier
 * Fire > Air > Earth > Water > Fire
 * Light <-> Dark (mutual weakness)
 */
function getElementMultiplier($attacker_element, $defender_element)
{
    $advantages = [
        'Fire' => 'Air',
        'Air' => 'Earth',
        'Earth' => 'Water',
        'Water' => 'Fire',
        'Light' => 'Dark',
        'Dark' => 'Light'
    ];

    if (isset($advantages[$attacker_element]) && $advantages[$attacker_element] === $defender_element) {
        return 1.3;
    }

    if (isset($advantages[$defender_element]) && $advantages[$defender_element] === $attacker_element) {
        return 0.8;
    }

    return 1.0;
}
