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

        // Check for Training Dummy upgrade (+5% Battle EXP)
        $owner_query = mysqli_prepare($conn, "SELECT user_id FROM user_pets WHERE id = ?");
        mysqli_stmt_bind_param($owner_query, "i", $winner_pet_id);
        mysqli_stmt_execute($owner_query);
        $owner_res = mysqli_stmt_get_result($owner_query);
        $owner_data = mysqli_fetch_assoc($owner_res);
        mysqli_stmt_close($owner_query);

        if ($owner_data) {
            require_once __DIR__ . '/stats.php';
            $winner_upgrades = getUserSanctuaryUpgrades($conn, $owner_data['user_id']);
            if (in_array('training_dummy', $winner_upgrades)) {
                $reward_exp = ceil($reward_exp * 1.05); // +5%
            }
        }

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

/**
 * Get available opponents for 1v1 arena battle
 * Returns a list of other users' pets that can be challenged
 *
 * @param mysqli $conn Database connection
 * @param int $user_id Current user's ID
 * @return array Array with success and opponents list
 */
function getOpponents($conn, $user_id)
{
    // Find other users who have alive pets
    $query = "SELECT up.id as pet_id, up.level, up.nickname, up.health, up.evolution_stage,
                     ps.name as species_name, ps.element, 
                     ps.img_egg, ps.img_baby, ps.img_adult, ps.rarity,
                     ps.base_attack, ps.base_defense,
                     n.nama_lengkap as owner_name
              FROM user_pets up
              JOIN pet_species ps ON up.species_id = ps.id
              JOIN nethera n ON up.user_id = n.id_nethera
              WHERE up.user_id != ? 
                AND up.status = 'ALIVE'
                AND up.is_active = 1
              ORDER BY RAND()
              LIMIT 5";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $opponents = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $level = (int) $row['level'];

        // Use evolution_stage from database (manual evolution system)
        $evolution_stage = $row['evolution_stage'] ?? 'egg';

        $opponents[] = [
            'pet_id' => (int) $row['pet_id'],
            'display_name' => $row['nickname'] ?: $row['species_name'],
            'species_name' => $row['species_name'],
            'level' => $level,
            'element' => $row['element'],
            'rarity' => $row['rarity'],
            'evolution_stage' => $evolution_stage,
            'img_egg' => $row['img_egg'],
            'img_baby' => $row['img_baby'],
            'img_adult' => $row['img_adult'],
            'hp' => (int) ($row['health'] ?? 100),
            'atk' => (int) ($row['base_attack'] ?? 10),
            'def' => (int) ($row['base_defense'] ?? 10),
            'owner_name' => $row['owner_name'] ?: 'Unknown Trainer'
        ];
    }
    mysqli_stmt_close($stmt);

    // If no real opponents, generate AI opponents
    if (count($opponents) === 0) {
        $opponents = generateAIOpponents1v1($conn, $user_id);
    }

    return [
        'success' => true,
        'opponents' => $opponents
    ];
}

/**
 * Initiate a battle between current user's active pet and an opponent
 *
 * @param mysqli $conn Database connection
 * @param int $user_id Current user's ID
 * @param int $opponent_pet_id Opponent pet's ID
 * @return array Battle result
 */
function initiateBattle($conn, $user_id, $opponent_pet_id)
{
    // Get user's active pet
    $stmt = mysqli_prepare($conn, "SELECT id FROM user_pets WHERE user_id = ? AND is_active = 1 AND status = 'ALIVE' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user_pet) {
        return ['success' => false, 'error' => 'No active pet found'];
    }

    $attacker_pet_id = (int) $user_pet['id'];

    // For AI opponents (negative ID), just return opponent info for client-side battle
    if ($opponent_pet_id < 0) {
        return [
            'success' => true,
            'mode' => 'client_battle',
            'attacker_pet_id' => $attacker_pet_id,
            'opponent_pet_id' => $opponent_pet_id,
            'message' => 'Battle initiated, proceed with client-side arena'
        ];
    }

    // For real opponents, can optionally do server-side battle
    return [
        'success' => true,
        'mode' => 'client_battle',
        'attacker_pet_id' => $attacker_pet_id,
        'opponent_pet_id' => $opponent_pet_id,
        'message' => 'Battle initiated, proceed with client-side arena'
    ];
}

/**
 * Generate AI opponents for 1v1 when no real players available
 */
function generateAIOpponents1v1($conn, $user_id)
{
    // Get user's average pet level for scaling
    $level_query = "SELECT AVG(level) as avg_level FROM user_pets WHERE user_id = ? AND status = 'ALIVE'";
    $level_stmt = mysqli_prepare($conn, $level_query);
    mysqli_stmt_bind_param($level_stmt, "i", $user_id);
    mysqli_stmt_execute($level_stmt);
    $level_result = mysqli_stmt_get_result($level_stmt);
    $level_row = mysqli_fetch_assoc($level_result);
    $avg_level = max(1, floor($level_row['avg_level'] ?? 1));
    mysqli_stmt_close($level_stmt);

    // Get random species for AI
    $species_query = "SELECT id, name, element, img_egg, img_baby, img_adult, rarity, base_attack, base_defense FROM pet_species ORDER BY RAND() LIMIT 5";
    $species_result = mysqli_query($conn, $species_query);

    $ai_opponents = [];
    $ai_names = ['Shadow', 'Phantom', 'Wild', 'Mystic', 'Ancient'];
    $index = 0;

    while ($species = mysqli_fetch_assoc($species_result)) {
        $ai_level = max(1, $avg_level + rand(-2, 2));

        // Calculate evolution stage based on level
        if ($ai_level >= 10) {
            $evolution_stage = 'adult';
        } elseif ($ai_level >= 5) {
            $evolution_stage = 'baby';
        } else {
            $evolution_stage = 'egg';
        }

        $ai_opponents[] = [
            'pet_id' => -($index + 1), // Negative ID for AI
            'display_name' => $ai_names[$index % 5] . ' ' . $species['name'],
            'species_name' => $species['name'],
            'level' => $ai_level,
            'element' => $species['element'],
            'rarity' => $species['rarity'],
            'evolution_stage' => $evolution_stage,
            'img_egg' => $species['img_egg'],
            'img_baby' => $species['img_baby'],
            'img_adult' => $species['img_adult'],
            'hp' => 100,
            'atk' => (int) ($species['base_attack'] ?? 10),
            'def' => (int) ($species['base_defense'] ?? 10),
            'owner_name' => 'Wild Trainer 🤖',
            'is_ai' => true
        ];
        $index++;
    }

    return $ai_opponents;
}
