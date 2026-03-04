<?php
/**
 * Social Module Routes (API)
 */

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'App\Modules\Social\Controllers\Api'], function ($routes) {
    $routes->post('profile/update', 'ProfileController::update');

    $routes->get('rhythm/songs', 'RhythmController::songs');
    $routes->get('rhythm/beatmap', 'RhythmController::beatmap');
    $routes->post('rhythm/score', 'RhythmController::submitScore');
    $routes->get('rhythm/highscore', 'RhythmController::highscore');
    $routes->post('rhythm/import', 'ImportController::importOsz', ['filter' => 'auth:vasiki']);
    $routes->post('rhythm/delete-song', 'ImportController::deleteSong', ['filter' => 'auth:vasiki']);
});
