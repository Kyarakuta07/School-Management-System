<?php
/**
 * Battle Module Routes (API)
 */

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'App\Modules\Battle\Controllers\Api'], function ($routes) {
    // 1v1
    $routes->get('battle/opponents', 'Arena1v1Controller::opponents');
    $routes->post('battle/start', 'Arena1v1Controller::start', ['filter' => ['auth:nethera,vasiki,hakaes,anubis', 'arena_quota:battle,10']]);
    $routes->post('battle/result', 'Arena1v1Controller::result');
    $routes->post('battle/play-finish', 'Arena1v1Controller::playFinish');
    $routes->post('battle/petting', 'Arena1v1Controller::petting');
    $routes->post('battle/attack-1v1', 'Arena1v1Controller::attack1v1');
    $routes->post('battle/enemy-turn-1v1', 'Arena1v1Controller::enemyTurn1v1');

    // History
    $routes->get('battle/history', 'BattleHistoryController::history');
    $routes->get('battle/wins', 'BattleHistoryController::wins');
    $routes->get('battle/streak', 'BattleHistoryController::streak');
    $routes->get('battle/leaderboard', 'BattleHistoryController::leaderboard');

    // 3v3
    $routes->post('battle/start-3v3', 'Arena3v3Controller::start3v3', ['filter' => ['auth:nethera,vasiki,hakaes,anubis', 'arena_quota:battle,10']]);
    $routes->post('battle/attack', 'Arena3v3Controller::attack');
    $routes->post('battle/enemy-turn', 'Arena3v3Controller::enemyTurn');
    $routes->get('battle/state', 'Arena3v3Controller::battleState');
    $routes->post('battle/switch-pet', 'Arena3v3Controller::switchPet');
    $routes->get('battle/opponents-3v3', 'Arena3v3Controller::opponents3v3');
    $routes->post('battle/finish-3v3', 'Arena3v3Controller::finish3v3');

    // War
    $routes->get('war/status', 'SanctuaryWarController::status');
    $routes->post('war/start', 'SanctuaryWarController::start');
    $routes->post('war/finalize', 'SanctuaryWarController::finalize');
    $routes->get('war/results', 'SanctuaryWarController::results');
});
