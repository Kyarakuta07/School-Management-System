<?php

namespace App\Modules\Battle\Controllers\Api;

use App\Kernel\BaseApiController;
use App\Modules\Battle\Repositories\BattleRepository;
use App\Config\GameConfig;

/**
 * BattleHistoryController
 *
 * Handles battle history, stats, streak, and leaderboard API endpoints.
 * Refactored: all raw DB queries moved to BattleRepository.
 * Controller now only orchestrates, validates, and returns JSON.
 */
class BattleHistoryController extends BaseApiController
{
    private BattleRepository $repo;

    public function __construct()
    {
        $this->repo = new BattleRepository(\Config\Database::connect());
    }

    public function history()
    {
        $petId = $this->request->getGet('pet_id');
        $limit = (int) ($this->request->getGet('limit') ?? 20);
        $offset = (int) ($this->request->getGet('offset') ?? 0);

        // A4 fix: Verify pet ownership before returning history
        if ($petId) {
            $pet = $this->verifyPetOwnership((int) $petId);
            if (!$pet) {
                return $this->error('Pet not found or not owned by you', 403, 'FORBIDDEN');
            }
        }

        // 1. Query via BattleRepository
        $history = $this->repo->getHistoryWithDetails(
            $petId ? (int) $petId : null,
            $this->userId,
            $limit,
            $offset
        );

        // 2. Map for Frontend via BattleRepository
        $history = $this->repo->mapForFrontend($history, $this->userId);

        // 3. Arena Statistics via BattleRepository
        $userStats = $this->repo->getUserArenaStats($this->userId);

        // 4. Quota
        try {
            $limiter = new \App\Kernel\Libraries\RateLimiter();
            $quota = $limiter->getDailyStatus((string) $this->userId, 'battle', GameConfig::BATTLE_DAILY_QUOTA, 0);
        } catch (\Exception $e) {
            $quota = ['remaining' => 0, 'limit' => GameConfig::BATTLE_DAILY_QUOTA, 'resets_at' => 'Error'];
        }

        $stats = [
            'wins' => (int) ($userStats['arena_wins'] ?? 0),
            'losses' => (int) ($userStats['arena_losses'] ?? 0),
            'current_streak' => (int) ($userStats['current_win_streak'] ?? 0),
            'battles_remaining' => $quota['remaining'],
            'limit' => $quota['limit'],
            'resets_at' => $quota['resets_at']
        ];

        return $this->success([
            'history' => $history,
            'stats' => $stats
        ], null, true);
    }

    public function wins()
    {
        $petId = $this->request->getGet('pet_id');
        if (!$petId) {
            return $this->error('Pet ID required');
        }

        $petService = service('petService');
        $pet = $petService->findPet($petId);
        if (!$pet || $pet['user_id'] != $this->userId) {
            return $this->error('Unauthorized', 403, 'UNAUTHORIZED');
        }

        return $this->success([
            'battles_won' => (int) $pet['total_wins'],
            'battles_lost' => (int) $pet['total_losses'],
            'rank_points' => (int) $pet['rank_points']
        ], null, true);
    }

    public function streak()
    {
        $userData = $this->repo->getUserArenaStats($this->userId);

        if (!$userData) {
            return $this->error('User not found', 404, 'NOT_FOUND');
        }

        return $this->success($userData, null, true);
    }

    public function leaderboard()
    {
        $type = $this->request->getGet('type') ?? 'rank';
        $limit = (int) ($this->request->getGet('limit') ?? 50);

        $results = $this->repo->getLeaderboard($type, $limit);

        return $this->success($results, null, true);
    }
}
