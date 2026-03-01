<?php
/**
 * Academic Module Routes (API)
 */

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'App\Modules\Academic\Controllers\Api'], function ($routes) {
    $routes->get('class/grades', 'ClassController::grades');
    $routes->post('class/grades', 'ClassController::updateGrades');
    $routes->get('class/students', 'ClassController::students');

    $routes->get('materials', 'MaterialController::index');
    $routes->group('materials', ['filter' => 'auth:vasiki,hakaes'], function ($routes) {
        $routes->post('add', 'MaterialController::add');
        $routes->post('update', 'MaterialController::update');
        $routes->post('delete', 'MaterialController::delete');
        $routes->post('upload', 'MaterialController::upload');
    });
    $routes->get('materials/download', 'MaterialController::download');

    $routes->get('quiz', 'QuizController::index');
    $routes->get('quiz/details', 'QuizController::details');
    $routes->get('quiz/history', 'QuizController::history');
    $routes->post('quiz/submit', 'QuizController::submit');

    $routes->group('quiz', ['filter' => 'auth:vasiki,hakaes'], function ($routes) {
        $routes->post('create', 'QuizController::create');
        $routes->post('add-question', 'QuizController::addQuestion');
        $routes->post('update-status', 'QuizController::updateStatus');
        $routes->post('delete-question', 'QuizController::deleteQuestion');
    });
});
