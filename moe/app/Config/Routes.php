<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * 
 * Main route file — delegates to per-module route files.
 * Each module defines its own routes in app/Modules/{Module}/Config/Routes.php
 * 
 * Phase 4: All routes use module namespaces directly — no stubs needed.
 */

// ── Root ──
$routes->get('/', 'Home::index');

// ── Public Pages (No auth required) ──
$routes->group('', ['namespace' => 'App\Modules\User\Controllers'], function ($routes) {
    $routes->get('staff', 'PublicPageController::staff');
    $routes->get('classes', 'PublicPageController::classes');
    $routes->get('world', 'PublicPageController::world');
});
$routes->group('', ['namespace' => 'App\Modules\Social\Controllers'], function ($routes) {
    $routes->get('guild', 'GuildController::index');
    $routes->get('guild/(:segment)', 'GuildController::index/$1');
});

// ── Auth Module Routes ──
require APPPATH . 'Modules/Auth/Config/Routes.php';

// ── Protected Page Routes ──
$routes->group('', ['filter' => 'auth:nethera,vasiki,hakaes,anubis'], function ($routes) {
    // User module
    $routes->group('', ['namespace' => 'App\Modules\User\Controllers'], function ($routes) {
        $routes->get('beranda', 'DashboardController::index');
    });

    // Pet module
    $routes->group('', ['namespace' => 'App\Modules\Pet\Controllers'], function ($routes) {
        $routes->get('pet', 'PetPageController::index');
    });

    // Academic module
    $routes->group('', ['namespace' => 'App\Modules\Academic\Controllers'], function ($routes) {
        $routes->get('class', 'ClassPageController::index');
        $routes->get('subject', 'SubjectPageController::index');
        $routes->get('quiz/manage', 'QuizPageController::manage');
        $routes->get('quiz/attempt', 'QuizPageController::attempt');
        $routes->match(['GET', 'POST'], 'punishment', 'PunishmentPageController::index');
    });

    // Battle module
    $routes->group('', ['namespace' => 'App\Modules\Battle\Controllers'], function ($routes) {
        $routes->get('battle', 'BattlePageController::arena');
        $routes->get('battle-3v3', 'BattlePageController::arena3v3');
        $routes->get('battle-war', 'BattlePageController::arenaWar');
    });

    // Sanctuary module
    $routes->group('', ['namespace' => 'App\Modules\Sanctuary\Controllers'], function ($routes) {
        $routes->match(['GET', 'POST'], 'sanctuary', 'SanctuaryPageController::index');
        $routes->match(['GET', 'POST'], 'my-sanctuary', 'SanctuaryPageController::index');
    });

    // Trapeza module
    $routes->group('', ['namespace' => 'App\Modules\Trapeza\Controllers'], function ($routes) {
        $routes->get('trapeza', 'TrapezaPageController::index');
    });

    // Social module
    $routes->group('', ['namespace' => 'App\Modules\Social\Controllers'], function ($routes) {
        $routes->get('rhythm-game', 'RhythmPageController::index');
        $routes->get('rhythm', 'RhythmPageController::index');
        $routes->get('rhythm/import', 'ImportBeatmapPageController::index');
    });
});

// ── Admin Module Routes ──
require APPPATH . 'Modules/Admin/Config/Routes.php';

// ── API Routes (protected) ──
$routes->group('api', ['filter' => ['auth:nethera,vasiki,hakaes,anubis', 'throttle']], function ($routes) {
    require APPPATH . 'Modules/Pet/Config/Routes.php';
    require APPPATH . 'Modules/Battle/Config/Routes.php';
    require APPPATH . 'Modules/Academic/Config/Routes.php';
    require APPPATH . 'Modules/Sanctuary/Config/Routes.php';
    require APPPATH . 'Modules/Trapeza/Config/Routes.php';
    require APPPATH . 'Modules/Social/Config/Routes.php';
});
