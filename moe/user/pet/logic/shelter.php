<?php
/**
 * MOE Pet System - Shelter Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles shelter mode toggle and active pet selection.
 */

// Shelter configuration
define('SHELTER_MAX_PETS', 3); // Maximum pets allowed in shelter per user

/**
 * Toggle shelter mode for a pet
 * In shelter: stats don't decay, but pet can't gain EXP or battle
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $pet_id Pet's ID
 * @return array Result
 */
function toggleShelter($conn, $user_id, $pet_id)
{
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

    $new_status = ($pet['status'] === 'SHELTER') ? 'ALIVE' : 'SHELTER';

    // Check shelter limit when trying to shelter a pet
    if ($new_status === 'SHELTER') {
        $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM user_pets WHERE user_id = ? AND status = 'SHELTER'");
        mysqli_stmt_bind_param($count_stmt, "i", $user_id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_row = mysqli_fetch_assoc($count_result);
        mysqli_stmt_close($count_stmt);

        if ($count_row['count'] >= SHELTER_MAX_PETS) {
            return [
                'success' => false,
                'error' => "Shelter is full! Maximum " . SHELTER_MAX_PETS . " pets allowed.",
                'shelter_count' => $count_row['count'],
                'shelter_limit' => SHELTER_MAX_PETS
            ];
        }
    }

    $is_active = 0; // Sheltered pets can't be active

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
function setActivePet($conn, $user_id, $pet_id)
{
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
