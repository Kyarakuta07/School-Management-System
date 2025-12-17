<?php
/**
 * MOE Pet System - Gacha Controller
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles gacha endpoints:
 * - gacha: Perform gacha roll to get new pets
 */

require_once __DIR__ . '/../BaseController.php';

class GachaController extends BaseController
{
    // Gacha costs
    const STANDARD_GACHA_COST = 100;
    const PREMIUM_GACHA_COST = 300;

    /**
     * POST: Perform gacha roll
     */
    public function gacha()
    {
        $this->requirePost();

        // Rate limiting - 100 gacha rolls per hour
        $this->checkRateLimit('gacha', 100, 60);

        $input = $this->getInput();
        $gacha_type = isset($input['type']) ? strtolower($input['type']) : 'standard';

        // Validate gacha type
        if (!in_array($gacha_type, ['standard', 'premium'])) {
            $this->error('Invalid gacha type. Use "standard" or "premium".');
            return;
        }

        // Determine cost
        $cost = ($gacha_type === 'premium') ? self::PREMIUM_GACHA_COST : self::STANDARD_GACHA_COST;
        $gacha_name = ucfirst($gacha_type) . ' Gacha';

        // Check user gold
        $user_gold = $this->getUserGold();
        if ($user_gold < $cost) {
            $this->error("Not enough gold for {$gacha_name}! Need {$cost}, have {$user_gold}.");
            return;
        }

        // Perform gacha FIRST (before deducting gold)
        $result = performGacha($this->conn, $this->user_id, $gacha_type);

        // If gacha failed (e.g. pet limit), return error without deducting gold
        if (!$result['success']) {
            echo json_encode($result);
            return;
        }

        // Gacha succeeded - now deduct gold
        $this->deductGold($cost);
        $this->logGoldTransaction($this->user_id, 0, $cost, 'gacha', $gacha_name);

        $result['cost'] = $cost;
        $result['remaining_gold'] = $user_gold - $cost;

        echo json_encode($result);
    }
}
