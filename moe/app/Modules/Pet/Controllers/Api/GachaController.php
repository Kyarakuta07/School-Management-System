<?php

namespace App\Modules\Pet\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gacha API Controller
 * 
 * Ported from legacy GachaController.php
 * 
 * Endpoints:
 *   POST /api/gacha → roll()
 */
class GachaController extends BaseApiController
{
    use IdempotencyTrait;

    const STANDARD_COST = 100;
    const PREMIUM_COST = 500;
    protected \App\Modules\User\Services\ActivityLogService $activityLog;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->activityLog = service('activityLog');
    }

    public function roll(): ResponseInterface
    {
        // Idempotency: prevent double-click
        if (!$this->acquireIdempotencyLock('gacha_roll', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $gachaType = strtolower($input['type'] ?? 'standard');

        if (!in_array($gachaType, ['standard', 'premium'])) {
            return $this->error('Invalid gacha type. Use "standard" or "premium".', 400, 'VALIDATION_ERROR');
        }

        $cost = ($gachaType === 'premium') ? self::PREMIUM_COST : self::STANDARD_COST;
        $gachaName = ucfirst($gachaType) . ' Gacha';

        // Atomic: gold deduction + pet creation in one transaction
        $this->db->transBegin();

        try {
            // 1. Lock gold and verify balance
            $goldService = service('goldService');
            $success = $goldService->subtractGoldRaw($this->userId, $cost, 'gacha_roll', "Rolled {$gachaName}");
            if (!$success) {
                $this->db->transRollback();
                return $this->error("Not enough gold for {$gachaName}! Need {$cost}.", 400, 'INSUFFICIENT_FUNDS');
            }

            // 2. Perform gacha roll (without own TX)
            $gachaService = service('gachaService');
            $gachaTypeInt = ($gachaType === 'premium') ? 3 : 1;
            $result = $gachaService->performGacha($this->userId, $gachaTypeInt, false);

            if (!$result['success']) {
                $this->db->transRollback();
                return $this->error($result['error'], 400, 'GACHA_FAILED');
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->error('Gacha failed. Please try again.', 500, 'SERVER_ERROR');
        }

        // Return pet info
        $species = $result['species'];
        $rarityEmoji = [
            'Common' => '⚪',
            'Rare' => '🔵',
            'Epic' => '🟣',
            'Legendary' => '🟡',
            'Mythical' => '🌈'
        ];

        // Log the gacha pull
        $logMessage = "Rolled {$gachaName}. Obtained: " . ($result['is_shiny'] ? "✨ SHINY " : "") . "{$result['rarity']} {$species['name']}";
        $this->activityLog->log('GACHA_ROLL', 'GACHA', $logMessage, $this->userId);

        $userGold = $this->getUserGold();

        return $this->success([
            'pet_id' => $result['pet_id'],
            'species' => [
                'name' => $species['name'],
                'img_egg' => $species['img_egg'] ?? $species['sprite_url'] ?? null,
                'element' => $species['element'] ?? null,
            ],
            'rarity' => $result['rarity'],
            'is_shiny' => $result['is_shiny'],
            'shiny_hue' => $result['shiny_hue'],
            'cost' => $cost,
            'remaining_gold' => $userGold,
        ], ($rarityEmoji[$result['rarity']] ?? '') . " You got a " . ($result['is_shiny'] ? "✨ SHINY " : "") . $result['rarity'] . " {$species['name']}!");
    }
}
