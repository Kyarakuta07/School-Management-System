<?php
/**
 * MOE Pet System - Stats Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles lazy stat calculation and pet data retrieval.
 */

// Ensure constants are loaded
if (!defined('HUNGER_DECAY_PER_HOUR')) {
    require_once __DIR__ . '/constants.php';
}

/**
 * Updates pet stats based on elapsed time since last update
 * This is the "lazy calculation" - stats are computed on-demand
 *
 * @param mysqli $conn Database connection
 * @param int $pet_id The pet's ID
 * @return array Updated pet data or false on failure
 */
function updatePetStats($conn, $pet_id)
{
    // Get current pet data
    $stmt = mysqli_prepare($conn, "SELECT * FROM user_pets WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $pet_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$pet)
        return false;

    // Skip calculation if pet is in shelter or dead
    if ($pet['status'] === 'SHELTER' || $pet['status'] === 'DEAD') {
        return $pet;
    }

    // Calculate time elapsed since last update
    $current_time = time();
    $last_update = (int) $pet['last_update_timestamp'];
    $hours_elapsed = ($current_time - $last_update) / 3600;

    // Only update if at least 1 minute has passed
    if ($hours_elapsed < (1 / 60)) {
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
    $update_stmt = mysqli_prepare(
        $conn,
        "UPDATE user_pets SET hunger = ?, mood = ?, health = ?, status = ?, last_update_timestamp = ? WHERE id = ?"
    );
    mysqli_stmt_bind_param(
        $update_stmt,
        "ddssii",
        $new_hunger,
        $new_mood,
        $new_health,
        $new_status,
        $current_time,
        $pet_id
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
function getUserPetsWithStats($conn, $user_id)
{
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

    // IMPORTANT: Fetch ALL pets first and close statement BEFORE calling updatePetStats
    // This prevents "Prepared statement needs to be re-prepared" error
    $raw_pets = [];
    while ($pet = mysqli_fetch_assoc($result)) {
        $raw_pets[] = $pet;
    }
    mysqli_stmt_close($stmt);

    // Now iterate and call updatePetStats (which uses its own prepared statements)
    $pets = [];
    foreach ($raw_pets as $pet) {
        // Update stats lazily for each pet
        $updated_pet = updatePetStats($conn, $pet['id']);
        if ($updated_pet) {
            // Merge updated stats with species info
            $pet['hunger'] = $updated_pet['hunger'];
            $pet['mood'] = $updated_pet['mood'];
            $pet['health'] = $updated_pet['health'];
            $pet['status'] = $updated_pet['status'];
            $pet['last_update_timestamp'] = $updated_pet['last_update_timestamp'];

            // evolution_stage is already in $pet from database (up.*)
            // Just set current_image based on stored stage
            $pet['current_image'] = getEvolutionImage($pet);
        }
        $pets[] = $pet;
    }

    return $pets;
}

/**
 * Get active sanctuary upgrades for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array List of active upgrade types (e.g. ['training_dummy', 'beastiary'])
 */
function getUserSanctuaryUpgrades($conn, $user_id)
{
    $query = "SELECT su.upgrade_type 
              FROM sanctuary_upgrades su
              JOIN nethera n ON su.sanctuary_id = n.id_sanctuary
              WHERE n.id_nethera = ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $upgrades = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $upgrades[] = $row['upgrade_type'];
    }
    mysqli_stmt_close($stmt);

    return $upgrades;
}
