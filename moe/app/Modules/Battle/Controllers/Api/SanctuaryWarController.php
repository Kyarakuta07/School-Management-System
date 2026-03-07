<?php

namespace App\Modules\Battle\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use CodeIgniter\HTTP\ResponseInterface;
use App\Config\GameConfig;

/**
 * Sanctuary War API Controller
 *
 * Ported from legacy SanctuaryWarController.php
 *
 * Endpoints:
 * GET /api/war/status → status()
 * POST /api/war/battle → battle()
 * GET /api/war/results → results()
 */
class SanctuaryWarController extends BaseApiController
{
    use IdempotencyTrait;

    protected $warModel;
    protected $petModel;
    protected \App\Modules\Battle\Models\WarBattleModel $battleModel;
    protected \App\Modules\User\Services\GoldService $goldService;
    protected \App\Modules\Battle\Services\SanctuaryWarService $warService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->warModel = new \App\Modules\Battle\Models\WarModel();
        $this->petModel = new \App\Modules\Pet\Models\PetModel();
        $this->battleModel = new \App\Modules\Battle\Models\WarBattleModel();
        $this->goldService = service('goldService');
        $this->warService = service('sanctuaryWarService');
    }

    public function status(): ResponseInterface
    {
        $war = $this->warModel->getActiveWar();

        if (!$war) {
            return $this->success([
                'is_active' => false,
                'next_war' => $this->warService->getNextWarDate(),
                'has_recap' => true,
                'recap' => $this->warService->getLastWarRecap(),
            ]);
        }

        $scores = $this->warModel->getWarScores($war['id']);
        $contribution = $this->warService->getUserContribution($war['id'], $this->userId);
        $ticketsUsed = $this->warService->getTicketsUsed($war['id'], $this->userId);
        $userSanctuary = $this->warService->getUserSanctuary($this->userId);

        // Find current champion (top of the list)
        $champion = !empty($scores) ? $scores[0] : null;

        return $this->success([
            'is_active' => true,
            'war_id' => $war['id'],
            'ends_at' => date('Y-m-d H:i:s', strtotime($war['war_date'] . ' +8 hours')), // Assuming war lasts 8 hours
            'champion' => $champion,
            'standings' => $scores,
            'tickets_remaining' => GameConfig::WAR_MAX_TICKETS - $ticketsUsed,
            'your_contribution' => [
                'points' => (int) ($contribution['points_earned'] ?? 0),
                'wins' => (int) ($contribution['wins'] ?? 0),
                'total_battles' => (int) ($contribution['battles'] ?? 0),
            ],
            'your_sanctuary' => $userSanctuary,
        ]);
    }

    /**
     * Start a Sanctuary War battle: Match opponent and verify tickets.
     * POST /api/war/start
     */
    public function start(): ResponseInterface
    {
        // A10 fix: Prevent cherry-pick spamming — 3-second cooldown between matchmaking
        if (!$this->acquireIdempotencyLock('war_start', $this->userId)) {
            return $this->error('Please wait before searching for a new opponent.', 429, 'DUPLICATE_REQUEST');
        }

        $war = $this->warModel->getActiveWar();

        if (!$war)
            return $this->error('No active war', 400, 'NO_WAR');

        $warId = $war['id'];

        $ticketsUsed = $this->warService->getTicketsUsed($warId, $this->userId);
        if ($ticketsUsed >= GameConfig::WAR_MAX_TICKETS)
            return $this->error('No battle tickets remaining', 400, 'NO_TICKETS');

        $userSanctuary = $this->warService->getUserSanctuary($this->userId);
        if (!$userSanctuary)
            return $this->error('You must be in a sanctuary to participate', 400, 'NO_SANCTUARY');

        $userPet = $this->petModel->getActivePet($this->userId);
        if (!$userPet)
            return $this->error('No active pet', 400, 'NO_PET');

        // Find opponent
        $opponent = $this->warService->findOpponent($userSanctuary['id_sanctuary']);

        if (!$opponent)
            return $this->error('No opponent available', 400, 'NO_OPPONENT');

        return $this->success([
            'attacker_pet' => $userPet,
            'defender_pet' => $opponent,
            'war_id' => $warId
        ]);
    }

    /**
     * Finalize a Sanctuary War battle: Record results and give rewards.
     * POST /api/war/finalize
     */
    public function finalize(): ResponseInterface
    {
        if (!$this->acquireIdempotencyLock('war_finalize', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $opponentId = (int) ($input['opponent_pet_id'] ?? 0);
        $warId = (int) ($input['war_id'] ?? 0);

        if (!$warId || !$opponentId) {
            return $this->error('Missing battle conclusion data', 400, 'MISSING_DATA');
        }

        $war = $this->warModel->find($warId);
        if (!$war || $war['status'] !== 'active') {
            return $this->error('War session is no longer active', 400, 'INACTIVE_WAR');
        }

        $userPet = $this->petModel->getActivePet($this->userId);
        $userSanctuary = $this->warService->getUserSanctuary($this->userId);
        $opponent = $this->petModel->getPetWithDetails($opponentId);

        if (!$userPet || !$userSanctuary || !$opponent) {
            return $this->error('Battle entities missing during finalization', 400, 'ENTITY_MISSING');
        }

        // Server-side battle simulation (pure computation, no DB)
        $battleResult = $this->warService->simulateBattle($userPet, $opponent);
        $won = $battleResult['won'];

        // Determine result based on simulation
        $points = $won ? GameConfig::WAR_POINTS_WIN : GameConfig::WAR_POINTS_LOSS;
        $goldReward = $won ? GameConfig::WAR_GOLD_WIN : GameConfig::WAR_GOLD_LOSS;
        $winnerUserId = $won ? $this->userId : (int) ($opponent['user_id'] ?? 0);

        // Single atomic transaction: ticket check + record + reward
        $this->db->transBegin();

        try {
            // 1. Ticket check INSIDE TX with FOR UPDATE lock (prevents TOCTOU — F7 fix)
            $ticketsUsed = $this->battleModel->getTicketsUsedForUpdate($warId, $this->userId);
            if ($ticketsUsed >= GameConfig::WAR_MAX_TICKETS) {
                $this->db->transRollback();
                return $this->error('Quota already reached', 403, 'QUOTA_EXHAUSTED');
            }

            // 2. Record Battle
            $this->warService->recordBattle(
                $warId,
                $this->userId,
                $userPet['id'],
                $opponentId,
                $userSanctuary['id_sanctuary'],
                (int) ($opponent['id_sanctuary'] ?? 0),
                $winnerUserId,
                $points,
                $goldReward
            );

            // 3. Give Reward via raw method (no nested TX — F5)
            $this->goldService->addGoldRaw($this->userId, $goldReward, 'battle_reward', "Sanctuary War Battle Reward");

            // 4. Update sanctuary scores
            if ($won) {
                $this->warModel->updateScore($warId, $userSanctuary['id_sanctuary'], 1, 0, 0);
            } else {
                $this->warModel->updateScore($warId, $userSanctuary['id_sanctuary'], 0, 1, 0);
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->error('Failed to record war final result', 500, 'DATABASE_ERROR');
        }

        return $this->success([
            'success' => true,
            'points' => $points,
            'gold_earned' => $goldReward,
            'won' => $won,
            'user_power' => $battleResult['user_power'],
            'opp_power' => $battleResult['opp_power'],
        ]);
    }

    public function results(): ResponseInterface
    {
        // Get last completed war
        $war = $this->warModel->where('status', 'completed')
            ->orderBy('war_date', 'DESC')
            ->first();

        if (!$war)
            return $this->success(['has_results' => false]);

        $scores = $this->warModel->getWarScores($war['id']);

        return $this->success([
            'has_results' => true,
            'war' => $war,
            'scores' => $scores,
        ]);
    }

}