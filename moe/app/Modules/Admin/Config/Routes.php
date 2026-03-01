<?php
/**
 * Admin Module Routes
 */

/** @var \CodeIgniter\Router\RouteCollection $routes */

// Vasiki only — Dashboard + User Management
$routes->group('admin', ['filter' => 'auth:vasiki', 'namespace' => 'App\Modules\Admin\Controllers'], function ($routes) {
    $routes->get('/', 'AdminDashboardController::index');

    $routes->get('nethera', 'AdminNetheraController::index');
    $routes->get('nethera/edit/(:num)', 'AdminNetheraController::edit/$1');
    $routes->post('nethera/update', 'AdminNetheraController::update');
    $routes->post('nethera/delete', 'AdminNetheraController::delete');
    $routes->get('nethera/search', 'AdminNetheraController::search');
});

// Vasiki + Hakaes — Classes, Grades, Schedules
$routes->group('admin', ['filter' => 'auth:vasiki,hakaes', 'namespace' => 'App\Modules\Admin\Controllers'], function ($routes) {
    $routes->get('classes', 'AdminClassesController::index');
    $routes->get('grades/add', 'AdminClassesController::addGrade');
    $routes->post('grades/store', 'AdminClassesController::storeGrade');
    $routes->get('grades/edit/(:num)', 'AdminClassesController::editGrade/$1');
    $routes->post('grades/update', 'AdminClassesController::updateGrade');
    $routes->post('grades/delete', 'AdminClassesController::deleteGrade');
    $routes->get('grades/search', 'AdminClassesController::searchGrades');

    $routes->get('schedule/add', 'AdminClassesController::addSchedule');
    $routes->post('schedule/store', 'AdminClassesController::storeSchedule');
    $routes->get('schedule/edit/(:num)', 'AdminClassesController::editSchedule/$1');
    $routes->post('schedule/update', 'AdminClassesController::updateSchedule');
    $routes->post('schedule/delete', 'AdminClassesController::deleteSchedule');
});
