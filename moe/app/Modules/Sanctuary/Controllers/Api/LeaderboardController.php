<?php

namespace App\Modules\Sanctuary\Controllers\Api;
use App\Kernel\BaseApiController;

use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Sanctuary\Models\LeaderboardModel;
use App\Modules\Sanctuary\Services\LeaderboardService;

/**
 * Leaderboard API Controller
 * 
 * Ported from legacy LeaderboardController.php
 * 
 * Endpoints:
 *   GET /api/leaderboard         â†’ index()    (pet leaderboard)
 *   GET /api/leaderboard/war     â†’ war()      (war leaderboard)
 *   GET /api/leaderboard/fame    â†’ hallOfFame()
 */
class LeaderboardController extends BaseApiController
{
    protected $leaderboardModel;
    protected LeaderboardService $leaderboardService;

    public function __construct()
    {
        $this->leaderboardModel = new LeaderboardModel();
        $this->leaderboardService = new LeaderboardService();
    }

    public function index(): ResponseInterface
    {
        $sort = $this->request->getGet('sort') ?? 'rank';
        $element = $this->request->getGet('element') ?? '';
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));

        $cache = \Config\Services::cache();
        $cacheKey = "lb_pet_{$sort}_{$element}_{$limit}_{$offset}";

        $cached = $cache->get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $this->success($cached);
        }

        // Delegate data queries to service
        $pets = $this->leaderboardService->getPetRankings($sort, $element, $limit, $offset);
        $totalCount = $this->leaderboardService->countPetRankings($element);

        // Map data and add tiers (presentation logic)
        foreach ($pets as &$pet) {
            if ($sort === 'level') {
                $pet['tier'] = $this->calculateTierByLevel((int) ($pet['level'] ?? 1));
            } elseif ($sort === 'wins') {
                $pet['tier'] = $this->calculateTierByWins((int) ($pet['wins'] ?? 0));
            } else {
                $pet['tier'] = $this->calculateTierByRp((int) ($pet['rank_points'] ?? 1000));
            }

            $stage = $pet['evolution_stage'] ?? 'egg';
            if ($stage === 'adult') {
                $pet['pet_image'] = $pet['img_adult'] ?? $pet['img_baby'] ?? $pet['img_egg'] ?? 'placeholder.png';
            } elseif ($stage === 'baby') {
                $pet['pet_image'] = $pet['img_baby'] ?? $pet['img_egg'] ?? $pet['img_adult'] ?? 'placeholder.png';
            } else {
                $pet['pet_image'] = $pet['img_egg'] ?? $pet['img_baby'] ?? $pet['img_adult'] ?? 'placeholder.png';
            }
            $pet['pet_id'] = $pet['id'];
            $pet['owner_name'] = $pet['username'];
            $pet['current_image'] = $pet['pet_image'];
        }

        // Get available elements — cached separately
        $elemCache = $cache->get('lb_elements');
        if ($elemCache === null) {
            $elements = $this->leaderboardService->getAvailableElements();
            $elemCache = array_column($elements, 'element');
            $cache->save('lb_elements', $elemCache, 3600);
        }

        $responseData = [
            'leaderboard' => $pets,
            'total_count' => $totalCount,
            'elements' => $elemCache,
            'sort' => $sort,
            'limit' => $limit,
            'offset' => $offset
        ];

        $cache->save($cacheKey, $responseData, 600);
        return $this->success($responseData);
    }

    public function war(): ResponseInterface
    {
        $warId = (int) ($this->request->getGet('war_id') ?? 0);
        $contributors = $this->leaderboardService->getWarContributors($warId);
        return $this->success(['leaderboard' => $contributors]);
    }

    public function hallOfFame(): ResponseInterface
    {
        $month = $this->request->getGet('month');
        $winners = $this->leaderboardModel->getWinners($month);

        // Map current_image based on evolution_stage
        foreach ($winners as &$w) {
            $stage = $w['evolution_stage'] ?? 'egg';
            if ($stage === 'adult') {
                $w['current_image'] = $w['img_adult'] ?? $w['img_baby'] ?? $w['img_egg'];
            } elseif ($stage === 'baby') {
                $w['current_image'] = $w['img_baby'] ?? $w['img_egg'] ?? $w['img_adult'];
            } else {
                $w['current_image'] = $w['img_egg'] ?? $w['img_baby'] ?? $w['img_adult'];
            }
        }

        return $this->success([
            'hall_of_fame' => $winners,
            'current_month' => date('F Y'),
            'selected_month' => $month ?: date('Y-m', strtotime('last month'))
        ]);
    }

    /**
     * Admin only: Manually trigger monthly archival
     */
    public function archive(): ResponseInterface
    {
        if (\Config\Services::session()->get('role') !== ROLE_VASIKI) {
            return $this->error('Unauthorized', 403);
        }

        $success = $this->leaderboardModel->archiveCurrentMonth();
        if ($success) {
            return $this->success([], 'Month archived successfully!');
        }

        return $this->error('Month already archived or archival failed', 400);
    }

    private function calculateTierByRp(int $rp): array
    {
        if ($rp >= 2000)
            return ['name' => 'Master', 'color' => '#FF00FF', 'icon' => 'ðŸ‘‘'];
        if ($rp >= 1800)
            return ['name' => 'Diamond', 'color' => '#B9F2FF', 'icon' => 'ðŸ’Ž'];
        if ($rp >= 1600)
            return ['name' => 'Platinum', 'color' => '#E5E4E2', 'icon' => 'ðŸ†'];
        if ($rp >= 1400)
            return ['name' => 'Gold', 'color' => '#FFD700', 'icon' => 'ðŸ¥‡'];
        if ($rp >= 1200)
            return ['name' => 'Silver', 'color' => '#C0C0C0', 'icon' => 'ðŸ¥ˆ'];
        return ['name' => 'Bronze', 'color' => '#CD7F32', 'icon' => 'ðŸ¥‰'];
    }

    private function calculateTierByLevel(int $level): array
    {
        if ($level >= 100)
            return ['name' => 'Master', 'color' => '#FF00FF', 'icon' => 'ðŸ‘‘'];
        if ($level >= 80)
            return ['name' => 'Diamond', 'color' => '#B9F2FF', 'icon' => 'ðŸ’Ž'];
        if ($level >= 60)
            return ['name' => 'Platinum', 'color' => '#E5E4E2', 'icon' => 'ðŸ†'];
        if ($level >= 40)
            return ['name' => 'Gold', 'color' => '#FFD700', 'icon' => 'ðŸ¥‡'];
        if ($level >= 20)
            return ['name' => 'Silver', 'color' => '#C0C0C0', 'icon' => 'ðŸ¥ˆ'];
        return ['name' => 'Bronze', 'color' => '#CD7F32', 'icon' => 'ðŸ¥‰'];
    }

    private function calculateTierByWins(int $wins): array
    {
        if ($wins >= 500)
            return ['name' => 'Master', 'color' => '#FF00FF', 'icon' => 'ðŸ‘‘'];
        if ($wins >= 350)
            return ['name' => 'Diamond', 'color' => '#B9F2FF', 'icon' => 'ðŸ’Ž'];
        if ($wins >= 200)
            return ['name' => 'Platinum', 'color' => '#E5E4E2', 'icon' => 'ðŸ†'];
        if ($wins >= 100)
            return ['name' => 'Gold', 'color' => '#FFD700', 'icon' => 'ðŸ¥‡'];
        if ($wins >= 50)
            return ['name' => 'Silver', 'color' => '#C0C0C0', 'icon' => 'ðŸ¥ˆ'];
        return ['name' => 'Bronze', 'color' => '#CD7F32', 'icon' => 'ðŸ¥‰'];
    }
}
