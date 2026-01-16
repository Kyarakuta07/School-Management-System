<?php
/**
 * MOE Pet System - Evolution Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles pet evolution stages, level ups, and manual evolution system.
 * Evolution is SACRIFICE-ONLY - stages are stored in database, not calculated from level.
 */

// Ensure constants are loaded
if (!defined('LEVEL_BABY')) {
    require_once __DIR__ . '/constants.php';
}

/**
 * Get evolution stage from pet data (stored in database)
 * This is used when pet data already includes evolution_stage
 *
 * @param array $pet Pet data with evolution_stage field
 * @return string 'egg', 'baby', or 'adult'
 */
function getStoredEvolutionStage($pet)
{
    return $pet['evolution_stage'] ?? 'egg';
}

/**
 * Legacy function - kept for compatibility but now just returns 'egg'
 * Actual stage is stored in database, not calculated from level
 *
 * @param int $level Pet's current level (ignored in new system)
 * @return string Always returns 'egg' - use getStoredEvolutionStage() instead
 * @deprecated Use getStoredEvolutionStage($pet) instead
 */
function getEvolutionStage($level)
{
    // Legacy fallback - new system uses stored stage
    return 'egg';
}

/**
 * Get the correct image path based on stored evolution stage
 *
 * @param array $pet Pet data including species image paths and evolution_stage
 * @return string Image path relative to assets/pets/
 */
function getEvolutionImage($pet)
{
    $stage = $pet['evolution_stage'] ?? 'egg';
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
 * Get level cap based on evolution stage
 * Pets cannot level beyond these caps until they evolve
 * 
 * @param string $stage Evolution stage (egg, baby, adult)
 * @return int Maximum level for this stage
 */
function getLevelCapForStage($stage)
{
    switch ($stage) {
        case 'egg':
            return 10;  // Egg caps at level 10
        case 'baby':
            return 20;  // Baby caps at level 20
        case 'adult':
        default:
            return 99;  // Adult has no practical cap
    }
}

/**
 * Add EXP to a pet and handle level ups
 * NOTE: Level ups do NOT trigger evolution - evolution is sacrifice-only
 * LEVEL CAPS: Egg caps at 10, Baby caps at 20, Adult caps at 99
 *
 * @param mysqli $conn Database connection
 * @param int $pet_id Pet ID
 * @param int $exp_amount Amount of EXP to add
 * @return array Result with level_up info
 */
function addExpToPet($conn, $pet_id, $exp_amount)
{
    $stmt = mysqli_prepare($conn, "SELECT level, exp, evolution_stage FROM user_pets WHERE id = ?");
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
    $current_stage = $pet['evolution_stage'] ?? 'egg';

    // LEVEL CAPS based on evolution stage
    $level_cap = getLevelCapForStage($current_stage);
    $at_cap = false;

    // Process level ups (but NOT evolution - that's sacrifice-only)
    while ($current_exp >= getExpForNextLevel($current_level) && $current_level < $level_cap) {
        $current_exp -= getExpForNextLevel($current_level);
        $current_level++;
        $level_ups++;
    }

    // If at level cap, keep EXP but mark as capped
    if ($current_level >= $level_cap) {
        $current_level = $level_cap;
        $at_cap = true;
        // Cap EXP at 0 so it doesn't accumulate endlessly
        $current_exp = 0;
    }

    // Update database - evolution_stage is NOT changed here
    $update_stmt = mysqli_prepare($conn, "UPDATE user_pets SET level = ?, exp = ? WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "iii", $current_level, $current_exp, $pet_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);

    return [
        'success' => true,
        'new_level' => $current_level,
        'new_exp' => $current_exp,
        'level_ups' => $level_ups,
        'evolved' => false, // Never auto-evolve
        'new_stage' => $current_stage, // Stage unchanged
        'at_cap' => $at_cap,
        'level_cap' => $level_cap
    ];
}

/**
 * Get eligible fodder pets for evolution
 * Returns pets of the same rarity that are not active and owned by user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $main_pet_id Pet to evolve (to get its rarity)
 * @return array Result with candidates and required rarity
 */
function getEvolutionCandidates($conn, $user_id, $main_pet_id)
{
    // First, get the main pet's rarity and stage
    $main_stmt = mysqli_prepare(
        $conn,
        "SELECT up.id, up.level, up.evolution_stage, ps.rarity 
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
        return ['success' => false, 'error' => 'Main pet not found'];
    }

    $required_rarity = $main_pet['rarity'];
    $current_stage = $main_pet['evolution_stage'] ?? 'egg';

    // Check if already at max stage
    if ($current_stage === 'adult') {
        return ['success' => false, 'error' => 'Pet is already at Adult stage (max)'];
    }

    // Get potential fodder pets (same rarity, not active, not the main pet, alive)
    $fodder_stmt = mysqli_prepare(
        $conn,
        "SELECT up.id, up.level, up.nickname, up.evolution_stage, up.species_id,
                ps.name as species_name, ps.rarity, ps.img_egg, ps.img_baby, ps.img_adult
         FROM user_pets up 
         JOIN pet_species ps ON up.species_id = ps.id 
         WHERE up.user_id = ? 
           AND up.id != ? 
           AND up.is_active = 0 
           AND up.status = 'ALIVE'
           AND ps.rarity = ?
         ORDER BY up.level ASC"
    );
    mysqli_stmt_bind_param($fodder_stmt, "iis", $user_id, $main_pet_id, $required_rarity);
    mysqli_stmt_execute($fodder_stmt);
    $fodder_result = mysqli_stmt_get_result($fodder_stmt);

    $candidates = [];
    while ($row = mysqli_fetch_assoc($fodder_result)) {
        $candidates[] = [
            'id' => (int) $row['id'],
            'level' => (int) $row['level'],
            'nickname' => $row['nickname'],
            'species_name' => $row['species_name'],
            'rarity' => $row['rarity'],
            'evolution_stage' => $row['evolution_stage'],
            'img_egg' => $row['img_egg'],
            'img_baby' => $row['img_baby'],
            'img_adult' => $row['img_adult']
        ];
    }
    mysqli_stmt_close($fodder_stmt);

    return [
        'success' => true,
        'required_rarity' => $required_rarity,
        'candidates' => $candidates
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

    // Get current stage from database (not calculated from level)
    $current_stage = $main_pet['evolution_stage'] ?? 'egg';
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

        // Evolve main pet - update evolution_stage and add bonus level
        $new_level = $level + 1; // Add 1 level as bonus for evolving

        $evolve_stmt = mysqli_prepare(
            $conn,
            "UPDATE user_pets SET evolution_stage = ?, level = ?, exp = 0 WHERE id = ?"
        );
        mysqli_stmt_bind_param($evolve_stmt, "sii", $next_stage, $new_level, $main_pet_id);
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
