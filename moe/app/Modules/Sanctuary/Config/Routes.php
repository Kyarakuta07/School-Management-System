<?php
/**
 * Sanctuary Module Routes (API)
 */

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'App\Modules\Sanctuary\Controllers\Api'], function ($routes) {
    $routes->get('rewards/daily', 'RewardController::dailyStatus');
    $routes->post('rewards/claim-daily', 'RewardController::claimDaily');
    $routes->get('rewards/achievements', 'RewardController::achievements');
    $routes->post('rewards/claim', 'RewardController::claimAchievement');

    $routes->get('leaderboard', 'LeaderboardController::index');
    $routes->get('leaderboard/war', 'LeaderboardController::war');
    $routes->get('leaderboard/fame', 'LeaderboardController::hallOfFame');
    $routes->post('leaderboard/archive', 'LeaderboardController::archive', ['filter' => 'auth:vasiki']);
});
