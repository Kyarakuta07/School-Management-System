<?php
/**
 * PHPUnit Test Bootstrap
 * 
 * Sets up the testing environment for all tests.
 */

// Define testing environment
define('TESTING', true);
define('PROJECT_ROOT', dirname(__DIR__));

// Autoload composer dependencies
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load environment variables for testing
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_USER'] = 'test_user';
$_ENV['DB_PASS'] = 'test_password';
$_ENV['DB_NAME'] = 'moe_test';

// Mock session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "PHPUnit Test Environment Initialized\n";
