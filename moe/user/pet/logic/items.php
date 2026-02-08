<?php
/**
 * MOE Pet System - Items Module
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles item usage on pets including food, potions, revive, and special items.
 */

/**
 * Use an item on a pet or perform gacha
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User's ID
 * @param int $pet_id Pet's ID (Can be 0 for Gacha Tickets)
 * @param int $item_id Shop item ID
 * @param int $quantity Amount to use (Default 1)
 * @return array Result of item usage
 */
function useItemOnPet($conn, $user_id, $pet_id, $item_id, $quantity = 1)
{
    // 1. Check User has Item in Inventory
    $inv_check = mysqli_prepare(
        $conn,
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

    if ($inventory['quantity'] < $quantity) {
        return ['success' => false, 'error' => 'Not enough items!'];
    }

    $effect_type = $inventory['effect_type'];
    $effect_value = $inventory['effect_value'];
    $total_effect = $effect_value * $quantity;

    $result_message = '';
    $extra_data = [];

    // 2. Branch Logic: Gacha vs Normal Items
    if ($effect_type === 'gacha_ticket') {
        if ($quantity > 1) {
            return ['success' => false, 'error' => 'Gacha tickets can only be used one at a time for now!'];
        }

        $gacha_result = performGacha($conn, $user_id, $effect_value);

        if ($gacha_result['success']) {
            $species_name = $gacha_result['species']['name'];
            $result_message = "Egg hatched! You got a $species_name!";
            $extra_data = [
                'is_gacha' => true,
                'gacha_data' => $gacha_result
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to hatch egg: ' . ($gacha_result['error'] ?? 'Unknown error')];
        }

    } else {
        // Normal consumable items (require target pet)
        if (empty($pet_id)) {
            return ['success' => false, 'error' => 'Pet ID required for this item'];
        }

        // Check pet ownership
        $pet_check = mysqli_prepare($conn, "SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($pet_check, "ii", $pet_id, $user_id);
        mysqli_stmt_execute($pet_check);
        $pet_result = mysqli_stmt_get_result($pet_check);
        $pet = mysqli_fetch_assoc($pet_result);
        mysqli_stmt_close($pet_check);

        if (!$pet) {
            return ['success' => false, 'error' => 'Pet not found or not owned by user'];
        }

        // Apply item effect
        switch ($effect_type) {
            case 'food':
                if ($pet['status'] === 'DEAD') {
                    return ['success' => false, 'error' => 'Cannot feed a dead pet. Revive first!'];
                }
                $new_hunger = min(100, $pet['hunger'] + $total_effect);
                $stmt = mysqli_prepare($conn, "UPDATE user_pets SET hunger = ?, last_update_timestamp = ? WHERE id = ?");
                $current_time = time();
                mysqli_stmt_bind_param($stmt, "iii", $new_hunger, $current_time, $pet_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $result_message = "Fed $quantity item(s)! Hunger +$total_effect.";
                break;

            case 'potion':
                if ($pet['status'] === 'DEAD') {
                    return ['success' => false, 'error' => 'Cannot heal a dead pet. Revive first!'];
                }
                $new_health = min(100, $pet['health'] + $total_effect);
                $stmt = mysqli_prepare($conn, "UPDATE user_pets SET health = ?, last_update_timestamp = ? WHERE id = ?");
                $current_time = time();
                mysqli_stmt_bind_param($stmt, "iii", $new_health, $current_time, $pet_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $result_message = "Used $quantity potion(s)! Health +$total_effect.";
                break;

            case 'revive':
                if ($pet['status'] !== 'DEAD') {
                    return ['success' => false, 'error' => 'Pet is not dead!'];
                }
                $quantity = 1; // Force quantity 1 for revive
                $new_health = $effect_value;
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

                // Check if pet is at level cap before using
                require_once __DIR__ . '/evolution.php';
                $current_stage = $pet['evolution_stage'] ?? 'egg';
                $level_cap = getLevelCapForStage($current_stage);
                $current_stage_name = getStageName($current_stage);

                if ($pet['level'] >= $level_cap) {
                    $next_stage_name = ($current_stage === 'egg') ? 'Adult' : 'King';
                    return ['success' => false, 'error' => "Pet is at level cap ($level_cap)! Evolve to $next_stage_name first to continue leveling."];
                }

                $exp_result = addExpToPet($conn, $pet_id, $total_effect);
                $result_message = "Used $quantity scroll(s)! Gained $total_effect EXP!";

                if ($exp_result['level_ups'] > 0) {
                    $result_message .= " Level Up! (Lv.{$exp_result['new_level']})";
                }

                // Warn if now at cap
                if (isset($exp_result['at_cap']) && $exp_result['at_cap']) {
                    $next_stage_name = ($current_stage === 'egg') ? 'Adult' : 'King';
                    $result_message .= " ⚠️ MAX LEVEL for $current_stage_name stage! Evolve to $next_stage_name to continue.";
                }
                break;

            case 'shield':
                if ($pet['status'] === 'DEAD') {
                    return ['success' => false, 'error' => 'Cannot shield a dead pet!'];
                }
                if (isset($pet['has_shield']) && $pet['has_shield']) {
                    return ['success' => false, 'error' => 'Pet already has an active shield!'];
                }
                $quantity = 1; // Force 1 shield at a time
                $stmt = mysqli_prepare($conn, "UPDATE user_pets SET has_shield = 1, last_update_timestamp = ? WHERE id = ?");
                $current_time = time();
                mysqli_stmt_bind_param($stmt, "ii", $current_time, $pet_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $result_message = "Divine Shield activated! Your pet will block 1 attack in battle.";
                break;

            default:
                return ['success' => false, 'error' => 'Unknown item type or logic not implemented'];
        }
    }

    // 3. Deduct item from inventory
    $deduct_stmt = mysqli_prepare($conn, "UPDATE user_inventory SET quantity = quantity - ? WHERE user_id = ? AND item_id = ?");
    mysqli_stmt_bind_param($deduct_stmt, "iii", $quantity, $user_id, $item_id);
    mysqli_stmt_execute($deduct_stmt);
    mysqli_stmt_close($deduct_stmt);

    // Remove inventory entry if quantity is 0
    mysqli_query($conn, "DELETE FROM user_inventory WHERE quantity <= 0");

    // 4. Return result
    return array_merge([
        'success' => true,
        'message' => $result_message,
        'item_used' => $inventory['item_name']
    ], $extra_data);
}
