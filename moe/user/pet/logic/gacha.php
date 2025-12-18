<?php
/**
 * MOE Pet System - Gacha Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles gacha rolls, rarity determination, and rhythm game scoring.
 */

// Ensure constants are loaded
if (!defined('GACHA_RARITY_WEIGHTS')) {
    require_once __DIR__ . '/constants.php';
}

/**
 * Convert rhythm game score to pet rewards (mood + EXP)
 * 
 * @param int $score Score achieved in rhythm game (0-1000)
 * @return array Mood and EXP rewards
 */
function convertRhythmScore($score)
{
    $mood_gain = min(30, floor($score / 10));
    $exp_gain = min(50, floor($score / 6));

    return [
        'mood' => $mood_gain,
        'exp' => $exp_gain,
        'score' => $score
    ];
}

/**
 * Perform a gacha roll and give user a new pet
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $gacha_type 1=Normal, 2=Rare+, 3=Epic+
 * @return array Result containing new pet info or error
 */
function performGacha($conn, $user_id, $gacha_type = 1)
{
    // Check pet collection limit (25 max)
    $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as pet_count FROM user_pets WHERE user_id = ?");
    mysqli_stmt_bind_param($count_stmt, "i", $user_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $pet_count = mysqli_fetch_assoc($count_result)['pet_count'];
    mysqli_stmt_close($count_stmt);

    if ($pet_count >= 25) {
        return [
            'success' => false,
            'error' => 'Pet collection full! You can only have 25 pets. Sell some pets first.'
        ];
    }

    $rarity = rollRarity($gacha_type);

    // Get random species of that rarity
    $stmt = mysqli_prepare(
        $conn,
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
    $insert_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO user_pets (user_id, species_id, nickname, level, exp, health, hunger, mood, status, is_shiny, shiny_hue, last_update_timestamp, is_active)
         VALUES (?, ?, NULL, 1, 0, 100, 100, 100, 'ALIVE', ?, ?, ?, 0)"
    );
    mysqli_stmt_bind_param(
        $insert_stmt,
        "iiiii",
        $user_id,
        $species['id'],
        $is_shiny,
        $shiny_hue,
        $current_time
    );
    mysqli_stmt_execute($insert_stmt);
    $new_pet_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_stmt);

    // If user has no active pet, make this one active
    $check_active = mysqli_prepare(
        $conn,
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
function rollRarity($gacha_type)
{
    $weights = GACHA_RARITY_WEIGHTS;

    if ($gacha_type == 2) {
        // Rare or better guaranteed
        $weights['Common'] = 0;
        $weights['Rare'] = 85;       // Adjusted from 80 -> 85
        $weights['Epic'] = 13;       // Adjusted from 17 -> 13
        $weights['Legendary'] = 2;   // Adjusted from 3 -> 2
    } else if ($gacha_type == 3) {
        // Epic or better guaranteed (Premium Gacha)
        $weights['Common'] = 0;
        $weights['Rare'] = 0;
        $weights['Epic'] = 75;       // Adjusted from 92 -> 75 (harder!)
        $weights['Legendary'] = 25;  // Adjusted from 8 -> 25 (better legendary chance!)
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
