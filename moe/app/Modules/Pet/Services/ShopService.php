<?php

namespace App\Modules\Pet\Services;

use App\Modules\Pet\Models\ShopModel;

/**
 * ShopService — Handles item purchase logic.
 * 
 * Single TX with FOR UPDATE lock via subtractGoldRaw.
 * Idempotency handled at controller level.
 */
class ShopService
{
    protected $db;
    protected ShopModel $shopModel;
    protected \App\Modules\User\Services\GoldService $goldService;
    protected \App\Modules\User\Services\ActivityLogService $activityLog;

    public function __construct($db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $this->shopModel = new ShopModel();
        $this->goldService = service('goldService');
        $this->activityLog = service('activityLog');
    }

    /**
     * Buy an item from the shop.
     * Single TX: gold deduction + inventory update atomic.
     * Uses subtractGoldRaw to avoid nested transactions.
     */
    public function buyItem(int $userId, int $itemId, int $quantity): array
    {
        $quantity = max(1, $quantity);
        $item = $this->shopModel->find($itemId);

        if (!$item) {
            return ['success' => false, 'message' => 'Item not found in shop catalog.', 'code' => 404];
        }

        $totalCost = $item['price'] * $quantity;

        $this->db->transBegin();

        try {
            // 1. Deduct gold with FOR UPDATE lock (raw, no nested TX)
            $success = $this->goldService->subtractGoldRaw($userId, $totalCost, 'shop', "Bought {$quantity}x {$item['name']}");

            if (!$success) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Not enough gold!', 'code' => 400];
            }

            // 2. Add to inventory
            $this->shopModel->addToInventory($userId, $itemId, $quantity);

            // 3. Log activity
            $this->activityLog->log('SHOP_PURCHASE', 'SHOP', "Purchased {$quantity}x {$item['name']} for {$totalCost} gold", $userId);

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return ['success' => false, 'message' => 'Purchase failed. Database error.', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => "Purchased {$quantity}x {$item['name']}!",
            'data' => [
                'item_name' => $item['name'],
                'total_cost' => $totalCost,
            ]
        ];
    }
}
