<?php
/**
 * Environment Configuration
 * Mediterranean of Egypt - School Management System
 * 
 * Detects and configures environment-specific settings.
 * Supports: production, staging, development
 * 
 * @author MOE Development Team
 * @version 1.0.0
 */

// ================================================
// ENVIRONMENT DETECTION
// ================================================

/**
 * Detect current environment based on hostname
 * @return string Environment name
 */
function detect_environment()
{
    // Check for explicit environment variable
    if (getenv('APP_ENV')) {
        return getenv('APP_ENV');
    }

    // Detect from hostname
    $host = $_SERVER['HTTP_HOST'] ?? gethostname();

    if (strpos($host, 'staging') !== false || strpos($host, 'stage') !== false) {
        return 'staging';
    }

    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return 'development';
    }

    if (strpos($host, 'moegypt.com') !== false || strpos($host, 'medofegypt') !== false) {
        return 'production';
    }

    // Default to production for safety
    return 'production';
}

// ================================================
// ENVIRONMENT CONSTANTS
// ================================================

// Define current environment
if (!defined('APP_ENV')) {
    define('APP_ENV', detect_environment());
}

// Environment-specific settings
$env_config = [
    'production' => [
        'debug' => false,
        'display_errors' => false,
        'error_reporting' => E_ALL & ~E_DEPRECATED,
        'log_errors' => true,
        'cache_enabled' => true,
        'minify_assets' => true,
        'db_host' => 'localhost',
        'site_url' => 'https://www.moegypt.com',
        'api_url' => 'https://www.moegypt.com/user/api',
    ],
    'staging' => [
        'debug' => true,
        'display_errors' => false,
        'error_reporting' => E_ALL,
        'log_errors' => true,
        'cache_enabled' => true,
        'minify_assets' => true,
        'db_host' => 'localhost',
        'site_url' => 'https://staging.moegypt.com',
        'api_url' => 'https://staging.moegypt.com/user/api',
    ],
    'development' => [
        'debug' => true,
        'display_errors' => true,
        'error_reporting' => E_ALL,
        'log_errors' => true,
        'cache_enabled' => false,
        'minify_assets' => false,
        'db_host' => 'localhost',
        'site_url' => 'http://localhost/School-Management-System/moe',
        'api_url' => 'http://localhost/School-Management-System/moe/user/api',
    ],
];

// Get config for current environment
$current_config = $env_config[APP_ENV] ?? $env_config['production'];

// Apply PHP settings
ini_set('display_errors', $current_config['display_errors'] ? '1' : '0');
error_reporting($current_config['error_reporting']);
ini_set('log_errors', '1');

// Define config constants
define('DEBUG_MODE', $current_config['debug']);
define('CACHE_ENABLED', $current_config['cache_enabled']);
define('MINIFY_ASSETS', $current_config['minify_assets']);
define('SITE_URL', $current_config['site_url']);
define('API_URL', $current_config['api_url']);

// ================================================
// HELPER FUNCTIONS
// ================================================

/**
 * Check if running in production
 * @return bool
 */
function is_production()
{
    return APP_ENV === 'production';
}

/**
 * Check if running in staging
 * @return bool
 */
function is_staging()
{
    return APP_ENV === 'staging';
}

/**
 * Check if running in development
 * @return bool
 */
function is_development()
{
    return APP_ENV === 'development';
}

/**
 * Check if debug mode is enabled
 * @return bool
 */
function is_debug()
{
    return defined('DEBUG_MODE') && DEBUG_MODE === true;
}

/**
 * Get environment-specific value
 * @param string $key Config key
 * @param mixed $default Default value
 * @return mixed
 */
function env_config($key, $default = null)
{
    global $current_config;
    return $current_config[$key] ?? $default;
}

/**
 * Display debug information (only in non-production)
 * @param mixed $data Data to display
 * @param bool $die Whether to stop execution
 */
function debug($data, $die = false)
{
    if (!is_production()) {
        echo '<pre style="background:#1a1a25;color:#fff;padding:1rem;border-radius:8px;overflow:auto;">';
        var_dump($data);
        echo '</pre>';
    }

    if ($die) {
        die();
    }
}

// ================================================
// ENVIRONMENT BANNER (for non-production)
// ================================================

/**
 * Get environment indicator banner HTML
 * @return string HTML for environment banner
 */
function get_environment_banner()
{
    if (is_production()) {
        return '';
    }

    $env = strtoupper(APP_ENV);
    $color = is_staging() ? '#f39c12' : '#3498db';

    return <<<HTML
<div id="env-banner" style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: {$color};
    color: white;
    text-align: center;
    padding: 4px 10px;
    font-family: monospace;
    font-size: 12px;
    font-weight: bold;
    z-index: 99999;
">
    ⚠️ {$env} ENVIRONMENT - Not For Production Use
</div>
<style>body { padding-top: 28px !important; }</style>
HTML;
}
