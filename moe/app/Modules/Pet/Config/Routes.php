<?php
/**
 * Pet Module Routes (API)
 */

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'App\Modules\Pet\Controllers\Api'], function ($routes) {
    $routes->get('pets', 'PetController::index');
    $routes->get('pets/active', 'PetController::active');
    $routes->post('pets/activate', 'PetController::activate');
    $routes->post('pets/rename', 'PetController::rename');
    $routes->post('pets/shelter', 'PetController::shelter');
    $routes->post('pets/sell', 'PetController::sell');

    $routes->get('shop', 'ShopController::index');
    $routes->get('shop/inventory', 'ShopController::inventory');
    $routes->post('shop/buy', 'ShopController::buy');
    $routes->post('shop/use', 'ShopController::useItem');

    $routes->post('gacha', 'GachaController::roll');

    $routes->get('evolution/candidates', 'EvolutionController::candidates');
    $routes->post('evolution/evolve', 'EvolutionController::evolve');
});
