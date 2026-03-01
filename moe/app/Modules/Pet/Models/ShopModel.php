<?php

namespace App\Modules\Pet\Models;

use CodeIgniter\Model;

/**
 * Shop Item Model
 * Handles `shop_items` and `user_inventory` tables.
 */
class ShopModel extends Model
{
    protected $table = 'shop_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'description', 'price', 'effect_type', 'effect_value', 'img_path', 'is_available'];

    /**
     * Get all available shop items (Cached for 1 hour)
     */
    public function getAvailableItems(): array
    {
        $cache = \Config\Services::cache();
        $cacheKey = 'shop_available_items';

        if ($cached = $cache->get($cacheKey)) {
            return $cached;
        }

        $result = $this->db->table('shop_items')
            ->where('is_available', 1)
            ->orderBy('effect_type')
            ->orderBy('price')
            ->get()
            ->getResultArray();

        // Fallback: if is_available column doesn't exist
        if ($result === false) {
            $result = $this->db->table('shop_items')
                ->orderBy('effect_type')
                ->orderBy('price')
                ->get()
                ->getResultArray();
        }

        $cache->save($cacheKey, $result, 3600);

        return $result ?: [];
    }

    /**
     * Get user's inventory with item details
     */
    public function getUserInventory(int $userId): array
    {
        return $this->db->table('user_inventory AS ui')
            ->select('ui.*, si.name, si.description, si.effect_type, si.effect_value, si.img_path')
            ->join('shop_items AS si', 'si.id = ui.item_id')
            ->where('ui.user_id', $userId)
            ->orderBy('si.effect_type')
            ->get()
            ->getResultArray();
    }

    /**
     * Add item to user inventory (or increase quantity)
     */
    public function addToInventory(int $userId, int $itemId, int $quantity): bool
    {
        $sql = "INSERT INTO user_inventory (user_id, item_id, quantity) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
        return $this->db->query($sql, [$userId, $itemId, $quantity]);
    }

    // ==================================================
    // ITEM USAGE HELPERS (ported from legacy items.php)
    // ==================================================

    /**
     * Get a specific item from user's inventory with shop_items details.
     * Returns null if not found or quantity <= 0.
     */
    public function getInventoryItem(int $userId, int $itemId): ?array
    {
        return $this->db->table('user_inventory AS ui')
            ->select('ui.*, si.effect_type, si.effect_value, si.name AS item_name')
            ->join('shop_items AS si', 'si.id = ui.item_id')
            ->where('ui.user_id', $userId)
            ->where('ui.item_id', $itemId)
            ->where('ui.quantity >', 0)
            ->get()
            ->getRowArray();
    }

    /**
     * Deduct item quantity from inventory.
     * Deletes row if quantity drops to 0 or below.
     */
    public function deductInventory(int $userId, int $itemId, int $quantity): bool
    {
        // Atomic deduction with WHERE guard to prevent negative quantity (F11 fix)
        $this->db->query(
            "UPDATE user_inventory SET quantity = quantity - ? WHERE user_id = ? AND item_id = ? AND quantity >= ?",
            [(int) $quantity, $userId, $itemId, (int) $quantity]
        );

        if ($this->db->affectedRows() === 0) {
            return false; // Concurrent depletion — quantity was already too low
        }

        // Clean up zero-quantity rows
        $this->db->table('user_inventory')
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->where('quantity <=', 0)
            ->delete();

        return true;
    }

    // ==================================================
    // STAGE CONSTANTS & HELPERS
    // ==================================================

    /** Get UI-friendly stage name (egg→Baby, baby→Adult, adult→King) */
    public static function getStageName(string $dbStage): string
    {
        switch ($dbStage) {
            case 'egg':
                return 'Baby';
            case 'baby':
                return 'Adult';
            case 'adult':
                return 'King';
            default:
                return ucfirst($dbStage);
        }
    }
}
