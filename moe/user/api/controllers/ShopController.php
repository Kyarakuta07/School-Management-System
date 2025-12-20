<?php
/**
 * MOE Pet System - Shop Controller
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles shop and inventory endpoints:
 * - get_shop: Get shop items
 * - get_inventory: Get user's inventory
 * - buy_item: Purchase from shop
 * - use_item: Use item on pet
 */

require_once __DIR__ . '/../BaseController.php';

class ShopController extends BaseController
{
    /**
     * GET: Get shop items
     */
    public function getShop()
    {
        $this->requireGet();

        try {
            // Try with is_available column
            $result = mysqli_query($this->conn, "SELECT * FROM shop_items WHERE is_available = 1 ORDER BY effect_type, price");

            // If query fails, try without is_available
            if (!$result) {
                $result = mysqli_query($this->conn, "SELECT * FROM shop_items ORDER BY effect_type, price");
            }

            $items = [];
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $items[] = $row;
                }
            }

            $this->success([
                'items' => $items,
                'user_gold' => $this->getUserGold()
            ]);
        } catch (Exception $e) {
            $this->error('Failed to load shop: ' . $e->getMessage());
        }
    }

    /**
     * GET: Get user's inventory
     */
    public function getInventory()
    {
        $this->requireGet();

        $query = "SELECT ui.*, si.name, si.description, si.effect_type, si.effect_value, si.img_path
                  FROM user_inventory ui
                  JOIN shop_items si ON ui.item_id = si.id
                  WHERE ui.user_id = ?
                  ORDER BY si.effect_type";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $inventory = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $inventory[] = $row;
        }
        mysqli_stmt_close($stmt);

        $this->success(['inventory' => $inventory]);
    }

    /**
     * POST: Buy item from shop
     */
    public function buyItem()
    {
        $this->requirePost();

        $input = $this->getInput();
        $item_id = isset($input['item_id']) ? (int) $input['item_id'] : 0;
        $quantity = isset($input['quantity']) ? max(1, (int) $input['quantity']) : 1;

        if (!$item_id) {
            $this->error('Item ID required');
            return;
        }

        // Get item details
        $item_stmt = mysqli_prepare($this->conn, "SELECT * FROM shop_items WHERE id = ?");
        mysqli_stmt_bind_param($item_stmt, "i", $item_id);
        mysqli_stmt_execute($item_stmt);
        $item_result = mysqli_stmt_get_result($item_stmt);
        $item = mysqli_fetch_assoc($item_result);
        mysqli_stmt_close($item_stmt);

        if (!$item) {
            $this->error('Item not found or unavailable');
            return;
        }

        $total_cost = $item['price'] * $quantity;
        $user_gold = $this->getUserGold();

        if ($user_gold < $total_cost) {
            $this->error("Not enough gold! Need {$total_cost}, have {$user_gold}.");
            return;
        }

        // Deduct gold and add to inventory
        $this->deductGold($total_cost);
        $this->logGoldTransaction($this->user_id, 0, $total_cost, 'shop', "Bought {$quantity}x {$item['name']}");

        // Add/update inventory
        $inv_stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO user_inventory (user_id, item_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)"
        );
        mysqli_stmt_bind_param($inv_stmt, "iii", $this->user_id, $item_id, $quantity);
        mysqli_stmt_execute($inv_stmt);
        mysqli_stmt_close($inv_stmt);

        $this->success([
            'message' => "Purchased {$quantity}x {$item['name']}!",
            'remaining_gold' => $user_gold - $total_cost
        ]);
    }

    /**
     * POST: Use item on pet
     */
    public function useItem()
    {
        $this->requirePost();

        $input = $this->getInput();
        $item_id = isset($input['item_id']) ? (int) $input['item_id'] : 0;
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;
        $quantity = isset($input['quantity']) ? max(1, (int) $input['quantity']) : 1;

        if (!$item_id || !$pet_id) {
            $this->error('Item ID and Pet ID required');
            return;
        }

        // Use the existing useItemOnPet function from items.php logic
        $result = useItemOnPet($this->conn, $this->user_id, $pet_id, $item_id, $quantity);
        echo json_encode($result);
    }
}
