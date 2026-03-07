<?php

namespace App\Modules\Pet\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use App\Modules\Pet\Models\ShopModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Shop API Controller
 * 
 * Ported from legacy moe/user/api/controllers/ShopController.php
 * 
 * Endpoints:
 *   GET  /api/shop           → index()     (get shop items)
 *   GET  /api/shop/inventory → inventory() (get user inventory)
 *   POST /api/shop/buy       → buy()       (buy item)
 *   POST /api/shop/use       → useItem()   (use item on pet)
 */
class ShopController extends BaseApiController
{
    use IdempotencyTrait;

    protected ShopModel $shopModel;
    protected \App\Modules\Pet\Services\ItemService $itemService;
    protected \App\Modules\Pet\Services\ShopService $shopService;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->shopModel = new ShopModel();
        $this->itemService = service('itemService');
        $this->shopService = service('shopService');
    }

    public function index(): ResponseInterface
    {

        $items = $this->shopModel->getAvailableItems();
        return $this->success([
            'items' => $items,
            'user_gold' => $this->getUserGold(),
        ]);
    }

    public function inventory(): ResponseInterface
    {
        $inventory = $this->shopModel->getUserInventory($this->userId);
        return $this->success(['inventory' => $inventory]);
    }

    public function buy(): ResponseInterface
    {
        if (!$this->acquireIdempotencyLock('shop_purchase', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $itemId = (int) ($input['item_id'] ?? 0);
        $quantity = max(1, (int) ($input['quantity'] ?? 1));

        if (!$itemId) {
            return $this->error('Item ID required', 400, 'VALIDATION_ERROR');
        }

        $result = $this->shopService->buyItem($this->userId, $itemId, $quantity);

        if (!$result['success']) {
            return $this->error($result['message'], $result['code'] ?? 400);
        }

        return $this->success([
            'remaining_gold' => $this->getUserGold(),
        ], $result['message']);
    }

    public function useItem(): ResponseInterface
    {
        $input = $this->getInput();
        $itemId = (int) ($input['item_id'] ?? 0);
        $petId = (int) ($input['pet_id'] ?? 0);
        $quantity = max(1, (int) ($input['quantity'] ?? 1));

        if (!$itemId) {
            return $this->error('Item ID required', 400, 'VALIDATION_ERROR');
        }

        $result = $this->itemService->useItem($this->userId, $petId, $itemId, $quantity);

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result['data'], $result['message']);
    }
}
