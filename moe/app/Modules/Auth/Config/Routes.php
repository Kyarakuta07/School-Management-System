<?php
/**
 * Auth Module Routes
 */

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'App\Modules\Auth\Controllers'], function ($routes) {
    $routes->get('login', 'AuthController::showLogin');
    $routes->post('login', 'AuthController::attemptLogin');
    $routes->get('register', 'AuthController::showRegister');
    $routes->post('register', 'AuthController::attemptRegister');
    $routes->get('forgot-password', 'AuthController::showForgotPassword');
    $routes->post('forgot-password', 'AuthController::attemptForgotPassword');
    $routes->get('reset-password', 'AuthController::showResetPassword');
    $routes->post('reset-password', 'AuthController::attemptResetPassword');
    $routes->get('verify-otp', 'AuthController::showVerifyOtp');
    $routes->post('verify-otp', 'AuthController::attemptVerifyOtp');
    $routes->post('resend-otp', 'AuthController::resendOtp');
    $routes->get('register-success', 'AuthController::showSuccess');
    $routes->post('logout', 'AuthController::logout');
});
$routes->post('admin/unlock-account', 'App\Modules\Auth\Controllers\AuthController::unlockAccount', ['filter' => 'auth:vasiki']);
