<?php
/**
 * Trapeza Module Routes (API)
 */

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'App\Modules\Trapeza\Controllers\Api'], function ($routes) {
    $routes->get('bank/balance', 'TrapezaController::balance');
    $routes->get('bank/transactions', 'TrapezaController::transactions');
    $routes->post('bank/transfer', 'TrapezaController::transfer');
    $routes->get('bank/search', 'TrapezaController::search');
});
