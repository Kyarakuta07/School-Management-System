<?php
/**
 * MOE Pet System - Buffs Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles pet buff calculations and school activity integration.
 */

/**
 * Get active pet buff for a school activity
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param string $activity_type Type of school activity (sports, study, art, music)
 * @return array Buff info or empty if no buff
 */
function getActivePetBuff($conn, $user_id, $activity_type = 'all')
{
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
