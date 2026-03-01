<?php

namespace App\Modules\Pet\Services;

use App\Modules\Pet\Models\ShopModel;
use App\Modules\Pet\Interfaces\PetServiceInterface;

/**
 * ItemService — Handles pet-related item usage logic.
 * Centralizes validations and stat updates for food, potions, revives, and scrolls.
 */
class ItemService
{
    protected $db;
    protected ShopModel $shopModel;
    protected PetServiceInterface $petService;
    protected \App\Modules\User\Services\ActivityLogService $activityLog;

    public function __construct($db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $this->shopModel = new ShopModel();
        $this->petService = service('petService');
        $this->activityLog = service('activityLog');
    }

    /**
     * Use an item from a user's inventory on a pet.
     * 
     * @param int $userId
     * @param int $petId
     * @param int $itemId
     * @param int $quantity
     * @return array{success:bool, message:string, data:array}
     */
    public function useItem(int $userId, int $petId, int $itemId, int $quantity = 1): array
    {
        $quantity = max(1, $quantity);

        // All validation now happens INSIDE the transaction to prevent TOCTOU (F6 fix)
        $this->db->transBegin();

        try {
            // 1. Fetch item from inventory WITH LOCK to prevent concurrent depletion
            $inventory = $this->db->query(
                "SELECT ui.*, si.effect_type, si.effect_value, si.name AS item_name
                 FROM user_inventory AS ui
                 JOIN shop_items AS si ON si.id = ui.item_id
                 WHERE ui.user_id = ? AND ui.item_id = ? AND ui.quantity > 0
                 FOR UPDATE",
                [$userId, $itemId]
            )->getRowArray();

            if (!$inventory) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Item not found in your inventory.',
                    'data' => []
                ];
            }

            if ((int) $inventory['quantity'] < $quantity) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Not enough items!',
                    'data' => []
                ];
            }

            // 2. Fetch pet and verify ownership
            $pet = $this->petService->getPetWithSpecies($petId);
            if (!$pet || $pet['user_id'] != $userId) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Pet not found or not owned by you.',
                    'data' => []
                ];
            }
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return ['success' => false, 'message' => 'Validation failed.', 'data' => []];
        }

        $effectType = $inventory['effect_type'];
        $effectValue = (int) $inventory['effect_value'];
        $totalEffect = $effectValue * $quantity;
        $currentTime = time();
        $resultMessage = '';
        $updateData = [];

        try {
            switch ($effectType) {
                case 'food':
                    if (($pet['status'] ?? 'ALIVE') === 'DEAD') {
                        $this->db->transRollback();
                        return ['success' => false, 'message' => 'Cannot feed a dead pet. Revive first!', 'data' => []];
                    }
                    $newHunger = min(100, (int) $pet['hunger'] + $totalEffect);
                    $updateData = ['hunger' => $newHunger, 'last_update_timestamp' => $currentTime];
                    $resultMessage = "Fed {$quantity} item(s)! Hunger +{$totalEffect}.";
                    break;

                case 'potion':
                    if (($pet['status'] ?? 'ALIVE') === 'DEAD') {
                        $this->db->transRollback();
                        return ['success' => false, 'message' => 'Cannot heal a dead pet. Revive first!', 'data' => []];
                    }
                    $maxHp = (int) ($pet['health'] ?? 100);
                    $currentHp = (int) ($pet['hp'] ?? 100);
                    // If effect_value >= maxHp, treat as full heal (e.g. Phoenix Tears)
                    $newHealth = ($effectValue >= $maxHp) ? $maxHp : min($maxHp, $currentHp + $totalEffect);
                    $healed = $newHealth - $currentHp;
                    $updateData = ['hp' => $newHealth, 'last_update_timestamp' => $currentTime];
                    $resultMessage = ($effectValue >= $maxHp)
                        ? "Used {$quantity} potion(s)! HP fully restored to {$maxHp}."
                        : "Used {$quantity} potion(s)! HP +{$healed}.";
                    break;

                case 'revive':
                    if (($pet['status'] ?? 'ALIVE') !== 'DEAD') {
                        $this->db->transRollback();
                        return ['success' => false, 'message' => 'Pet is not dead!', 'data' => []];
                    }
                    $quantity = 1; // Force 1
                    $maxHp = (int) ($pet['health'] ?? 100);
                    $reviveHp = (int) round($maxHp * ($effectValue / 100));
                    // Full revive (100%) restores all stats; partial revive sets hunger/mood to 50
                    $reviveHunger = ($effectValue >= 100) ? 100 : 50;
                    $reviveMood = ($effectValue >= 100) ? 100 : 50;
                    $updateData = [
                        'hp' => max(1, $reviveHp),
                        'hunger' => $reviveHunger,
                        'mood' => $reviveMood,
                        'status' => 'ALIVE',
                        'last_update_timestamp' => $currentTime,
                    ];
                    $resultMessage = ($effectValue >= 100)
                        ? "Pet revived with full stats! HP: {$maxHp}, Hunger: 100, Mood: 100."
                        : "Pet revived with {$effectValue}% HP, 50 Hunger & Mood.";
                    break;

                case 'exp_boost':
                    if (($pet['status'] ?? 'ALIVE') === 'DEAD') {
                        $this->db->transRollback();
                        return ['success' => false, 'message' => 'Cannot give EXP to a dead pet!', 'data' => []];
                    }

                    // Use addExpRaw (no nested TX — already inside our TX)
                    $expResult = $this->petService->addExpRaw($petId, $totalEffect);

                    if ($expResult['at_cap'] ?? false) {
                        $resultMessage = "Pet is at its level cap (Lv.{$expResult['level_cap']})! Evolve to unlock higher levels.";
                    } else {
                        $resultMessage = "Used {$quantity} scroll(s)! Gained {$totalEffect} EXP!";
                        if ($expResult['leveled_up']) {
                            $resultMessage .= " Level Up! (Lv.{$expResult['new_level']})";
                        }
                    }

                    // Logging and achievements will be handled by the caller or specialized method
                    break;

                case 'shield':
                    if (($pet['status'] ?? 'ALIVE') === 'DEAD') {
                        $this->db->transRollback();
                        return ['success' => false, 'message' => 'Cannot shield a dead pet!', 'data' => []];
                    }
                    if (!empty($pet['has_shield'])) {
                        $this->db->transRollback();
                        return ['success' => false, 'message' => 'Pet already has an active shield!', 'data' => []];
                    }
                    $quantity = 1;
                    $updateData = ['has_shield' => 1, 'last_update_timestamp' => $currentTime];
                    $resultMessage = "Divine Shield activated! Your pet will block 1 attack in battle.";
                    break;

                case 'gacha_ticket':
                    $this->db->transRollback();
                    return ['success' => false, 'message' => 'Gacha tickets must be used on the Gacha page.', 'data' => []];

                case 'arena_reset':
                    $limiter = new \App\Kernel\Libraries\RateLimiter();
                    $limiter->resetLimit((string) $userId, 'battle');
                    $resultMessage = "Used an Arena Ticket! Your daily battle quota has been reset.";
                    break;

                default:
                    $this->db->transRollback();
                    return ['success' => false, 'message' => 'Unknown item type or logic not implemented.', 'data' => []];
            }

            // Apply updates if any (non-EXP items)
            if (!empty($updateData)) {
                $this->db->table('user_pets')
                    ->where('id', $petId)
                    ->update($updateData);
            }

            // Deduct inventory
            $this->shopModel->deductInventory($userId, $itemId, $quantity);

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return ['success' => false, 'message' => 'Item usage failed. Database error.', 'data' => []];
        }

        // Log the action
        $this->activityLog->log('ITEM_USAGE', 'PET', "Used {$quantity}x {$inventory['item_name']} on pet ID: {$petId}", $userId);

        return [
            'success' => true,
            'message' => $resultMessage,
            'data' => [
                'item_name' => $inventory['item_name'],
                'quantity_used' => $quantity,
                'effect_type' => $effectType
            ]
        ];
    }
}
