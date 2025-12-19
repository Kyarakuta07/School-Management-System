<?php
/**
 * Application Bootstrap
 * Mediterranean of Egypt - School Management System
 * 
 * Single entry point to load all required dependencies.
 * Include this file at the top of every page instead of
 * manually including multiple files.
 * 
 * Usage:
 *   require_once '../includes/bootstrap.php';
 * 
 * What this file does:
 *   1. Loads security configuration
 *   2. Starts session (if not already started)
 *   3. Loads database connection
 *   4. Initializes DB wrapper class
 *   5. Loads Auth and CSRF helpers
 *   6. Loads utility helpers
 * 
 * After including this file, you have access to:
 *   - $conn           : mysqli connection
 *   - DB class        : DB::query(), DB::insert(), etc.
 *   - Auth class      : Auth::requireNethera(), Auth::user(), etc.
 *   - CSRF functions  : generate_csrf_token(), validate_csrf_token()
 *   - Helper functions: e(), format_date_id(), json_response(), etc.
 */

// ==================================================
// 1. SECURITY CONFIGURATION
// ==================================================
require_once __DIR__ . '/security_config.php';

// ==================================================
// 2. START SESSION (if not already started)
// ==================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================================================
// 3. SET COMMON SECURITY HEADERS
// ==================================================
// Prevent clickjacking
header("X-Frame-Options: SAMEORIGIN");
// Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");
// Enable XSS filter
header("X-XSS-Protection: 1; mode=block");

// ==================================================
// 4. LOAD DATABASE CONNECTION
// ==================================================
require_once __DIR__ . '/../config/connection.php';

// ==================================================
// 5. LOAD CORE CLASSES
// ==================================================
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

// Initialize DB class with connection
DB::init($conn);

// ==================================================
// 6. LOAD UTILITIES
// ==================================================
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

// ==================================================
// 7. OPTIONAL: Load common libraries on demand
// ==================================================

/**
 * Load Rate Limiter (call when needed)
 */
function load_rate_limiter()
{
    require_once __DIR__ . '/rate_limiter.php';
    global $conn;
    return new RateLimiter($conn);
}

/**
 * Load Sanitization functions (call when needed)
 */
function load_sanitization()
{
    require_once __DIR__ . '/sanitization.php';
}

/**
 * Load API Response helpers (call when needed for API endpoints)
 */
function load_api_response()
{
    require_once __DIR__ . '/api_response.php';
}

/**
 * Load Activity Logger (call when needed)
 */
function load_activity_logger()
{
    require_once __DIR__ . '/activity_logger.php';
}

// ==================================================
// 8. APPLICATION CONSTANTS
// ==================================================
define('APP_NAME', 'Mediterranean of Egypt');
define('APP_SHORT_NAME', 'MOE');
define('APP_VERSION', '1.0.0');

// Roles
define('ROLE_NETHERA', 'Nethera');
define('ROLE_VASIKI', 'Vasiki');

// Account statuses
define('STATUS_AKTIF', 'Aktif');
define('STATUS_PENDING', 'Pending');
define('STATUS_HIATUS', 'Hiatus');
define('STATUS_OUT', 'Out');

// Default values
define('DEFAULT_GOLD', 500);
define('MAX_PROFILE_PHOTO_SIZE', 2 * 1024 * 1024); // 2MB

// ==================================================
// 9. ENVIRONMENT DETECTION
// ==================================================

// Load environment configuration
require_once __DIR__ . '/environment.php';

// Note: environment.php now handles:
// - APP_ENV detection (production/staging/development)
// - DEBUG_MODE, CACHE_ENABLED, SITE_URL constants
// - is_production(), is_staging(), is_development() helpers

// Legacy compatibility
$is_production = is_production();
define('IS_PRODUCTION', $is_production);

// In production, hide errors from users
if (IS_PRODUCTION) {
    ini_set('display_errors', '0');
    error_reporting(E_ERROR | E_PARSE);
}

// ==================================================
// 10. PERFORMANCE & CACHING (Optional)
// ==================================================

/**
 * Load Performance utilities (caching, compression)
 * @return void
 */
function load_performance()
{
    require_once __DIR__ . '/performance.php';
}

/**
 * Load Image Optimizer utilities
 * @return void
 */
function load_image_optimizer()
{
    require_once __DIR__ . '/image_optimizer.php';
}

// ==================================================
// 11. MONITORING & LOGGING (Optional)
// ==================================================

/**
 * Load Monitoring system (Logger, ErrorTracker, HealthCheck)
 * @param bool $register_handlers Whether to register global error handlers
 * @return void
 */
function load_monitoring($register_handlers = false)
{
    require_once __DIR__ . '/monitoring.php';

    if ($register_handlers) {
        register_error_handlers();
    }
}

// Auto-load monitoring in production for error tracking
if (IS_PRODUCTION && file_exists(__DIR__ . '/monitoring.php')) {
    load_monitoring(true);
}
