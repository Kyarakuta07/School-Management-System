<?php
/**
 * MOE Pet System - Pet Controller
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles pet-related endpoints:
 * - get_pets: Fetch all user's pets
 * - get_active_pet: Get active pet with stats
 * - set_active: Set a pet as active
 * - rename: Rename a pet
 * - shelter: Toggle shelter mode
 * - sell_pet: Sell a pet for gold
 */

require_once __DIR__ . '/../BaseController.php';

class PetController extends BaseController
{
    /**
     * GET: Fetch all user's pets with stats
     */
    public function getPets()
    {
        $this->requireGet();

        $pets = getUserPetsWithStats($this->conn, $this->user_id);

        $this->success([
            'pets' => $pets,
            'count' => count($pets)
        ]);
    }

    /**
     * GET: Get active pet with updated stats
     */
    public function getActivePet()
    {
        $this->requireGet();

        $pets = getUserPetsWithStats($this->conn, $this->user_id);
        $active_pet = null;

        foreach ($pets as $pet) {
            if ($pet['is_active']) {
                $active_pet = $pet;
                break;
            }
        }

        $this->success(['pet' => $active_pet]);
    }

    /**
     * POST: Set active pet
     */
    public function setActive()
    {
        $this->requirePost();

        $input = $this->getInput();
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;

        if (!$pet_id) {
            $this->error('Pet ID required');
            return;
        }

        $result = setActivePet($this->conn, $this->user_id, $pet_id);
        echo json_encode($result);
    }

    /**
     * POST: Rename pet
     */
    public function rename()
    {
        $this->requirePost();

        $input = $this->getInput();
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;
        $nickname = isset($input['nickname']) ? trim($input['nickname']) : '';

        if (!$pet_id) {
            $this->error('Pet ID required');
            return;
        }

        // Sanitize nickname
        $nickname = htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8');
        if (strlen($nickname) > 50) {
            $nickname = substr($nickname, 0, 50);
        }

        // Verify ownership and update
        $stmt = mysqli_prepare($this->conn, "UPDATE user_pets SET nickname = ? WHERE id = ? AND user_id = ?");
        $nickname_value = empty($nickname) ? null : $nickname;
        mysqli_stmt_bind_param($stmt, "sii", $nickname_value, $pet_id, $this->user_id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($this->conn);
        mysqli_stmt_close($stmt);

        if ($affected > 0) {
            $this->success([], 'Pet renamed successfully!');
        } else {
            $this->error('Pet not found or not yours');
        }
    }

    /**
     * POST: Toggle shelter mode
     */
    public function shelter()
    {
        $this->requirePost();

        $input = $this->getInput();
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;

        if (!$pet_id) {
            $this->error('Pet ID required');
            return;
        }

        $result = toggleShelter($this->conn, $this->user_id, $pet_id);
        echo json_encode($result);
    }

    /**
     * POST: Sell pet for gold
     */
    public function sellPet()
    {
        $this->requirePost();

        $input = $this->getInput();
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;

        if (!$pet_id) {
            $this->error('Pet ID required');
            return;
        }

        // Verify ownership and get pet data
        $pet = $this->verifyPetOwnership($pet_id);
        if (!$pet) {
            $this->error('Pet not found or not yours');
            return;
        }

        if ($pet['is_active']) {
            $this->error('Cannot sell your active pet!');
            return;
        }

        // Get species data for sell price
        $species_stmt = mysqli_prepare($this->conn, "SELECT rarity FROM pet_species WHERE id = ?");
        mysqli_stmt_bind_param($species_stmt, "i", $pet['species_id']);
        mysqli_stmt_execute($species_stmt);
        $species_result = mysqli_stmt_get_result($species_stmt);
        $species = mysqli_fetch_assoc($species_result);
        mysqli_stmt_close($species_stmt);

        // Calculate sell price based on rarity and level
        $rarity_multiplier = [
            'common' => 10,
            'uncommon' => 25,
            'rare' => 50,
            'epic' => 100,
            'legendary' => 250
        ];
        $base_price = $rarity_multiplier[strtolower($species['rarity'])] ?? 10;
        $sell_price = $base_price * $pet['level'];

        // Delete pet and add gold
        mysqli_begin_transaction($this->conn);
        try {
            $delete_stmt = mysqli_prepare($this->conn, "DELETE FROM user_pets WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "ii", $pet_id, $this->user_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);

            $this->addGold($sell_price);
            $this->logGoldTransaction(0, $this->user_id, $sell_price, 'pet_sale', 'Sold pet for gold');

            mysqli_commit($this->conn);

            $this->success([
                'gold_earned' => $sell_price,
                'new_balance' => $this->getUserGold()
            ], "Pet sold for {$sell_price} gold!");

        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            $this->error('Failed to sell pet: ' . $e->getMessage());
        }
    }
}
