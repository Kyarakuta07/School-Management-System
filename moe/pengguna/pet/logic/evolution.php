<?php
/**
 * MOE Pet System - Evolution Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles pet evolution stages, level ups, and manual evolution system.
 */

// Ensure constants are loaded
if (!defined('LEVEL_BABY')) {
    require_once __DIR__ . '/constants.php';
}

/**
 * Determine evolution stage based on level
 *
 * @param int $level Pet's current level
 * @return string 'egg', 'baby', or 'adult'
 */
function getEvolutionStage($level)
{
    if ($level < LEVEL_BABY)
        return 'egg';
    if ($level < LEVEL_ADULT)
        return 'baby';
    return 'adult';
}

/**
 * Get the correct image path based on evolution stage
 *
 * @param array $pet Pet data including species image paths
 * @return string Image path relative to assets/pets/
 */
function getEvolutionImage($pet)
{
    $stage = getEvolutionStage($pet['level']);
    switch ($stage) {
        case 'egg':
            return $pet['img_egg'];
        case 'baby':
            return $pet['img_baby'];
        case 'adult':
            return $pet['img_adult'];
        default:
            return $pet['img_egg'];
    }
}

/**
 * Calculate EXP required for next level
 *
 * @param int $current_level Current level
 * @return int EXP needed to reach next level
 */
function getExpForNextLevel($current_level)
{
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
function addExpToPet($conn, $pet_id, $exp_amount)
{
    $stmt = mysqli_prepare($conn, "SELECT level, exp FROM user_pets WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $pet_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$pet)
        return ['success' => false, 'error' => 'Pet not found'];

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

/**
 * Manually evolve a pet by sacrificing 3 other pets of the same rarity
 * Stage-based evolution:
 * - Egg (Lv.10+) → Baby
 * - Baby (Lv.20+) → Adult
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $main_pet_id Pet to evolve
 * @param array $fodder_pet_ids Array of exactly 3 pet IDs to sacrifice
 * @param int $gold_cost Cost of evolution (default 500)
 * @return array Result with success status and details
 */
function evolvePetManual($conn, $user_id, $main_pet_id, $fodder_pet_ids, $gold_cost = 500)
{
    // STEP 1: VALIDATION
    if (count($fodder_pet_ids) !== 3) {
        return ['success' => false, 'error' => 'Exactly 3 fodder pets required'];
    }

    // Get main pet data with species info
    $main_stmt = mysqli_prepare(
        $conn,
        "SELECT up.*, ps.rarity 
         FROM user_pets up 
         JOIN pet_species ps ON up.species_id = ps.id 
         WHERE up.id = ? AND up.user_id = ?"
    );
    mysqli_stmt_bind_param($main_stmt, "ii", $main_pet_id, $user_id);
    mysqli_stmt_execute($main_stmt);
    $main_result = mysqli_stmt_get_result($main_stmt);
    $main_pet = mysqli_fetch_assoc($main_result);
    mysqli_stmt_close($main_stmt);

    if (!$main_pet) {
        return ['success' => false, 'error' => 'Main pet not found or not owned'];
    }

    // Determine current stage and check requirements
    $current_stage = getEvolutionStage($main_pet['level']);
    $level = $main_pet['level'];

    if ($current_stage === 'adult') {
        return ['success' => false, 'error' => 'Pet is already at Adult stage (max evolution)'];
    }

    if ($current_stage === 'egg' && $level < 10) {
        return ['success' => false, 'error' => "Pet must be Level 10+ to evolve from Egg to Baby (current: Lv.$level)"];
    }

    if ($current_stage === 'baby' && $level < 20) {
        return ['success' => false, 'error' => "Pet must be Level 20+ to evolve from Baby to Adult (current: Lv.$level)"];
    }

    // Determine next stage
    $next_stage = ($current_stage === 'egg') ? 'baby' : 'adult';
    $next_level = ($current_stage === 'egg') ? 10 : 20; // Ensure minimum level after evolution

    // Check user's gold
    $gold_check = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
    mysqli_stmt_bind_param($gold_check, "i", $user_id);
    mysqli_stmt_execute($gold_check);
    $gold_result = mysqli_stmt_get_result($gold_check);
    $gold_row = mysqli_fetch_assoc($gold_result);
    $user_gold = $gold_row ? $gold_row['gold'] : 0;
    mysqli_stmt_close($gold_check);

    if ($user_gold < $gold_cost) {
        return ['success' => false, 'error' => "Not enough gold! Need $gold_cost, have $user_gold"];
    }

    // STEP 2: VALIDATE FODDER PETS
    $placeholders = implode(',', array_fill(0, count($fodder_pet_ids), '?'));
    $fodder_query = "SELECT up.id, up.user_id, up.is_active, ps.rarity 
                     FROM user_pets up 
                     JOIN pet_species ps ON up.species_id = ps.id 
                     WHERE up.id IN ($placeholders)";

    $fodder_stmt = mysqli_prepare($conn, $fodder_query);
    $types = str_repeat('i', count($fodder_pet_ids));
    mysqli_stmt_bind_param($fodder_stmt, $types, ...$fodder_pet_ids);
    mysqli_stmt_execute($fodder_stmt);
    $fodder_result = mysqli_stmt_get_result($fodder_stmt);

    $fodder_pets = [];
    while ($row = mysqli_fetch_assoc($fodder_result)) {
        $fodder_pets[] = $row;
    }
    mysqli_stmt_close($fodder_stmt);

    if (count($fodder_pets) !== 3) {
        return ['success' => false, 'error' => 'One or more fodder pets not found'];
    }

    $main_rarity = $main_pet['rarity'];
    foreach ($fodder_pets as $fodder) {
        if ($fodder['user_id'] != $user_id) {
            return ['success' => false, 'error' => 'You do not own all selected fodder pets'];
        }
        if ($fodder['rarity'] !== $main_rarity) {
            return ['success' => false, 'error' => "All pets must be same rarity ($main_rarity)"];
        }
        if ($fodder['is_active']) {
            return ['success' => false, 'error' => 'Cannot sacrifice an active pet'];
        }
        if ($fodder['id'] == $main_pet_id) {
            return ['success' => false, 'error' => 'Cannot sacrifice the pet you are evolving'];
        }
    }

    // STEP 3: EXECUTE EVOLUTION (WITH TRANSACTION)
    mysqli_begin_transaction($conn, MYSQLI_TRANS_START_READ_WRITE);

    try {
        // Deduct gold
        $deduct_gold = mysqli_prepare($conn, "UPDATE nethera SET gold = gold - ? WHERE id_nethera = ?");
        mysqli_stmt_bind_param($deduct_gold, "ii", $gold_cost, $user_id);
        mysqli_stmt_execute($deduct_gold);
        mysqli_stmt_close($deduct_gold);

        // Delete fodder pets
        foreach ($fodder_pet_ids as $fodder_id) {
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM user_pets WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "ii", $fodder_id, $user_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
        }

        // Evolve main pet - set level to evolution threshold + 1
        // This ensures they're in the new stage
        $new_level = ($next_stage === 'baby') ? 5 : 15; // LEVEL_BABY or LEVEL_ADULT
        if ($level >= $new_level) {
            $new_level = $level + 1; // Just add 1 level if already above threshold
        }

        $evolve_stmt = mysqli_prepare(
            $conn,
            "UPDATE user_pets SET level = ?, exp = 0 WHERE id = ?"
        );
        mysqli_stmt_bind_param($evolve_stmt, "ii", $new_level, $main_pet_id);
        mysqli_stmt_execute($evolve_stmt);
        mysqli_stmt_close($evolve_stmt);

        // Record evolution history (if table exists)
        $history_json = json_encode($fodder_pet_ids);
        $history_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO pet_evolution_history (user_id, main_pet_id, fodder_pet_ids, gold_cost) 
             VALUES (?, ?, ?, ?)"
        );
        if ($history_stmt) {
            mysqli_stmt_bind_param($history_stmt, "iisi", $user_id, $main_pet_id, $history_json, $gold_cost);
            mysqli_stmt_execute($history_stmt);
            mysqli_stmt_close($history_stmt);
        }

        mysqli_commit($conn);

        $stage_emoji = ($next_stage === 'baby') ? '🐣' : '🦅';
        return [
            'success' => true,
            'message' => "Evolution successful! Your pet evolved to $next_stage stage! $stage_emoji",
            'sacrificed_count' => 3,
            'gold_spent' => $gold_cost,
            'new_stage' => $next_stage,
            'new_level' => $new_level
        ];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'error' => 'Evolution failed: ' . $e->getMessage()];
    }
}
