<?php
/**
 * MOE Pet System - Play Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles play sessions and mood updates from Rhythm Game.
 */

// Ensure constants and evolution functions are loaded
if (!defined('BASE_EXP_PER_LEVEL')) {
    require_once __DIR__ . '/constants.php';
}
require_once __DIR__ . '/evolution.php';

/**
 * Finish a play session and award mood/exp to pet
 * Called by Rhythm Game when game ends
 * 
 * FIXED: Now uses centralized addExpToPet() to ensure:
 * - Consistent EXP formula (exponential, not linear)
 * - Respects level caps (Egg=10, Baby=20, Adult=99)
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $pet_id Pet's ID
 * @param string $play_type Type of play (rhythm, ball, etc.)
 * @param int $duration Duration in seconds (or score for rhythm game)
 * @return array Result with rewards
 */
function finishPlaySession($conn, $user_id, $pet_id, $play_type = 'rhythm', $duration = 30)
{
    // Verify pet ownership
    $stmt = mysqli_prepare(
        $conn,
        "SELECT up.*, ps.name as species_name 
         FROM user_pets up 
         JOIN pet_species ps ON up.species_id = ps.id 
         WHERE up.id = ? AND up.user_id = ? AND up.status = 'ALIVE'"
    );
    mysqli_stmt_bind_param($stmt, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$pet) {
        return [
            'success' => false,
            'error' => 'Pet not found or not owned by user'
        ];
    }

    // Calculate rewards based on play type
    $mood_gain = 0;
    $exp_gain = 0;

    if ($play_type === 'rhythm') {
        // For rhythm game, duration is actually the score
        $score = $duration;
        $mood_gain = min(30, floor($score / 10));  // Max 30 mood
        $exp_gain = min(80, floor($score / 5));    // Max 80 exp (buffed from 50)
    } else {
        // Default play session (ball, petting, etc.)
        $mood_gain = min(20, floor($duration / 5));  // 1 mood per 5 seconds, max 20
        $exp_gain = min(15, floor($duration / 10));  // 1 exp per 10 seconds, max 15
    }

    // Get current pet stats
    $current_mood = (int) $pet['mood'];

    // Calculate new mood (cap at 100)
    $new_mood = min(100, $current_mood + $mood_gain);

    // Update mood in database
    $update_stmt = mysqli_prepare(
        $conn,
        "UPDATE user_pets SET mood = ?, last_update_timestamp = ? WHERE id = ?"
    );
    $current_time = time();
    mysqli_stmt_bind_param($update_stmt, "iii", $new_mood, $current_time, $pet_id);
    $update_result = mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);

    if (!$update_result) {
        return [
            'success' => false,
            'error' => 'Failed to update pet stats'
        ];
    }

    // Use centralized EXP function (respects level caps and uses correct formula)
    $exp_result = addExpToPet($conn, $pet_id, $exp_gain);

    $leveled_up = ($exp_result['level_ups'] ?? 0) > 0;
    $new_level = $exp_result['new_level'] ?? $pet['level'];
    $new_exp = $exp_result['new_exp'] ?? 0;
    $at_cap = $exp_result['at_cap'] ?? false;

    return [
        'success' => true,
        'pet_id' => $pet_id,
        'pet_name' => $pet['nickname'] ?? $pet['species_name'],
        'play_type' => $play_type,
        'rewards' => [
            'mood' => $mood_gain,
            'exp' => $exp_gain
        ],
        'new_stats' => [
            'mood' => $new_mood,
            'exp' => $new_exp,
            'level' => $new_level
        ],
        'leveled_up' => $leveled_up,
        'at_cap' => $at_cap,
        'level_cap' => $exp_result['level_cap'] ?? null
    ];
}

/**
 * Handle petting action (quick mood boost)
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $pet_id Pet's ID
 * @return array Result with mood gain
 */
function petPetting($conn, $user_id, $pet_id)
{
    // Verify pet ownership
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, mood, nickname FROM user_pets 
         WHERE id = ? AND user_id = ? AND status = 'ALIVE'"
    );
    mysqli_stmt_bind_param($stmt, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$pet) {
        return [
            'success' => false,
            'error' => 'Pet not found'
        ];
    }

    // Petting gives small mood boost (5-10)
    $mood_gain = rand(5, 10);
    $current_mood = (int) $pet['mood'];
    $new_mood = min(100, $current_mood + $mood_gain);

    // Update mood
    $update_stmt = mysqli_prepare(
        $conn,
        "UPDATE user_pets SET mood = ? WHERE id = ?"
    );
    mysqli_stmt_bind_param($update_stmt, "ii", $new_mood, $pet_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);

    return [
        'success' => true,
        'mood_gain' => $mood_gain,
        'new_mood' => $new_mood
    ];
}
