<?php

namespace App\Modules\Sanctuary\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use App\Modules\Trapeza\Models\TransactionModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Reward API Controller
 * 
 * Ported from legacy RewardController.php
 * 
 * Endpoints:
 *   GET  /api/rewards/daily        → dailyStatus()
 *   POST /api/rewards/claim-daily  → claimDaily()
 *   GET  /api/rewards/achievements → achievements()
 *   POST /api/rewards/claim        → claimAchievement()
 */
class RewardController extends BaseApiController
{
    use IdempotencyTrait;

    protected $achievementModel;
    protected $petService;
    protected $txModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->achievementModel = new \App\Modules\Pet\Models\AchievementModel();
        $this->petService = service('petService');
        $this->txModel = new TransactionModel();
    }

    public function dailyStatus(): ResponseInterface
    {
        $rewardService = service('rewardService');
        $status = $rewardService->getDailyStatus($this->userId);

        return $this->success($status);
    }

    public function claimDaily(): ResponseInterface
    {
        if (!$this->acquireIdempotencyLock('daily_reward_claim', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $rewardService = service('rewardService');
        $result = $rewardService->claimDaily($this->userId);

        if (!$result['success']) {
            return $this->error($result['error'], 400, 'CLAIM_FAILED');
        }

        // Log transaction
        if ($result['gold_received'] > 0) {
            $this->txModel->logTransaction(0, $this->userId, $result['gold_received'], 'daily_reward', 'Daily login reward');
        }

        $message = "Claimed Day {$result['claimed_day']} reward! +{$result['gold_received']} Gold";
        if ($result['item_received']) {
            $message .= " + {$result['item_received']}";
        }

        return $this->success($result, $message);
    }

    public function achievements(): ResponseInterface
    {
        $achievements = $this->achievementModel->getUserAchievements($this->userId);
        $progress = $this->getAchievementProgress();
        $unlockedCount = 0;
        $toUnlock = [];

        foreach ($achievements as &$a) {
            $a['unlocked'] = !is_null($a['unlocked_at']);
            $a['claimed'] = (bool) ($a['claimed'] ?? false);
            $a['current_progress'] = $progress[$a['requirement_type']] ?? 0;

            if (!$a['unlocked'] && $a['current_progress'] >= $a['requirement_value']) {
                $toUnlock[] = (int) $a['id'];
                $a['unlocked'] = true;
                $a['unlocked_at'] = date('Y-m-d H:i:s');
            }

            if ($a['unlocked'])
                $unlockedCount++;
        }

        // Batch unlock in a single transaction
        if (!empty($toUnlock)) {
            $this->achievementModel->batchUnlock($this->userId, $toUnlock);
        }

        return $this->success([
            'achievements' => $achievements,
            'progress' => $progress,
            'unlocked' => $unlockedCount,
            'total' => count($achievements),
        ]);
    }

    public function claimAchievement(): ResponseInterface
    {
        $input = $this->getInput();
        $achievementId = (int) ($input['achievement_id'] ?? 0);

        if (!$achievementId) {
            return $this->error('Achievement ID required', 400, 'VALIDATION_ERROR');
        }

        if (!$this->acquireIdempotencyLock('achievement_reward_claim', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $achievementService = service('achievementService');
        $result = $achievementService->claimReward($this->userId, $achievementId);

        if (!$result['success']) {
            return $this->error($result['error'] ?? 'Failed to claim reward', 400, 'CLAIM_FAILED');
        }

        return $this->success([
            'gold_earned' => $result['gold_earned'],
            'new_balance' => $this->getUserGold(),
        ], "Claimed {$result['gold_earned']} gold from achievement: {$result['achievement_name']}!");
    }

    // ==================================================
    // PRIVATE HELPERS
    // ==================================================

    private function getAchievementProgress(): array
    {
        $achievementService = service('achievementService');
        return $achievementService->getAllProgress($this->userId);
    }
}
