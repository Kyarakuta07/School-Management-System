<?php
/**
 * Monitoring & Alerting System
 * Mediterranean of Egypt - School Management System
 * 
 * Provides logging, error tracking, health checks, and alerting.
 * 
 * @author MOE Development Team
 * @version 1.0.0
 */

// ================================================
// CONFIGURATION
// ================================================

// Log directory (create if not exists)
define('LOG_DIR', __DIR__ . '/../logs');

// Alert thresholds
define('ALERT_ERROR_THRESHOLD', 10);      // Errors per hour
define('ALERT_SLOW_QUERY_MS', 500);       // Slow query threshold
define('ALERT_MEMORY_PERCENT', 80);       // Memory usage percent
define('ALERT_RESPONSE_TIME_MS', 3000);   // Response time threshold

// ================================================
// LOGGER CLASS
// ================================================

/**
 * Application logger with different log levels
 */
class Logger
{
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';

    private static $log_file = null;
    private static $min_level = 'DEBUG';

    private static $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];

    /**
     * Initialize logger
     * @param string $log_file Log file path
     * @param string $min_level Minimum log level
     */
    public static function init($log_file = null, $min_level = 'DEBUG')
    {
        // Ensure log directory exists
        if (!file_exists(LOG_DIR)) {
            mkdir(LOG_DIR, 0755, true);
        }

        self::$log_file = $log_file ?: LOG_DIR . '/app_' . date('Y-m-d') . '.log';
        self::$min_level = $min_level;
    }

    /**
     * Log a message
     * @param string $level Log level
     * @param string $message Message to log
     * @param array $context Additional context data
     */
    public static function log($level, $message, $context = [])
    {
        if (self::$log_file === null) {
            self::init();
        }

        // Check minimum level
        if (self::$levels[$level] < self::$levels[self::$min_level]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' ' . json_encode($context) : '';

        $log_entry = sprintf(
            "[%s] [%s] %s%s\n",
            $timestamp,
            $level,
            $message,
            $context_str
        );

        file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);

        // Trigger alert for errors
        if (in_array($level, ['ERROR', 'CRITICAL'])) {
            self::checkErrorThreshold();
        }
    }

    // Convenience methods
    public static function debug($message, $context = [])
    {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }

    public static function info($message, $context = [])
    {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    public static function warning($message, $context = [])
    {
        self::log(self::LEVEL_WARNING, $message, $context);
    }

    public static function error($message, $context = [])
    {
        self::log(self::LEVEL_ERROR, $message, $context);
    }

    public static function critical($message, $context = [])
    {
        self::log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Check if error threshold exceeded
     */
    private static function checkErrorThreshold()
    {
        $error_log = LOG_DIR . '/error_count.json';
        $current_hour = date('Y-m-d-H');

        $data = [];
        if (file_exists($error_log)) {
            $data = json_decode(file_get_contents($error_log), true) ?: [];
        }

        if (!isset($data[$current_hour])) {
            $data[$current_hour] = 0;
        }

        $data[$current_hour]++;

        // Clean old hours
        $data = array_filter($data, function ($key) {
            return strtotime($key . ':00:00') > strtotime('-24 hours');
        }, ARRAY_FILTER_USE_KEY);

        file_put_contents($error_log, json_encode($data));

        // Trigger alert if threshold exceeded
        if ($data[$current_hour] >= ALERT_ERROR_THRESHOLD) {
            AlertManager::trigger('high_error_rate', [
                'count' => $data[$current_hour],
                'threshold' => ALERT_ERROR_THRESHOLD,
                'hour' => $current_hour
            ]);
        }
    }
}

// ================================================
// ERROR TRACKER
// ================================================

/**
 * Track and store errors for analysis
 */
class ErrorTracker
{
    private static $errors = [];

    /**
     * Track an error
     * @param Exception|Throwable $e The exception
     * @param array $context Additional context
     */
    public static function track($e, $context = [])
    {
        $error = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $context,
            'user_id' => isset($_SESSION['id_nethera']) ? $_SESSION['id_nethera'] : null,
            'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI',
            'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI',
            'ip' => self::getClientIp(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'timestamp' => date('Y-m-d H:i:s'),
            'memory' => memory_get_usage(true)
        ];

        self::$errors[] = $error;

        // Log the error
        Logger::error($e->getMessage(), [
            'file' => $e->getFile() . ':' . $e->getLine(),
            'type' => get_class($e)
        ]);

        // Store in error log file
        self::storeError($error);

        return $error;
    }

    /**
     * Store error to file
     * @param array $error Error data
     */
    private static function storeError($error)
    {
        $file = LOG_DIR . '/errors_' . date('Y-m-d') . '.json';

        $errors = [];
        if (file_exists($file)) {
            $errors = json_decode(file_get_contents($file), true) ?: [];
        }

        $errors[] = $error;

        // Keep only last 1000 errors per day
        if (count($errors) > 1000) {
            $errors = array_slice($errors, -1000);
        }

        file_put_contents($file, json_encode($errors, JSON_PRETTY_PRINT));
    }

    /**
     * Get client IP address
     * @return string
     */
    private static function getClientIp()
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return 'unknown';
    }

    /**
     * Get all tracked errors
     * @return array
     */
    public static function getErrors()
    {
        return self::$errors;
    }
}

// ================================================
// HEALTH CHECK
// ================================================

/**
 * Application health monitoring
 */
class HealthCheck
{
    private static $checks = [];
    private static $results = [];

    /**
     * Register a health check
     * @param string $name Check name
     * @param callable $check Check function (returns bool or array with status)
     */
    public static function register($name, $check)
    {
        self::$checks[$name] = $check;
    }

    /**
     * Run all health checks
     * @return array Results
     */
    public static function runAll()
    {
        self::$results = [];
        $overall_healthy = true;

        foreach (self::$checks as $name => $check) {
            try {
                $result = call_user_func($check);

                if (is_bool($result)) {
                    $result = ['healthy' => $result, 'message' => $result ? 'OK' : 'Failed'];
                }

                self::$results[$name] = $result;

                if (!$result['healthy']) {
                    $overall_healthy = false;
                }
            } catch (Exception $e) {
                self::$results[$name] = [
                    'healthy' => false,
                    'message' => 'Check failed: ' . $e->getMessage()
                ];
                $overall_healthy = false;
            }
        }

        self::$results['_overall'] = $overall_healthy;
        self::$results['_timestamp'] = date('Y-m-d H:i:s');

        // Log unhealthy status
        if (!$overall_healthy) {
            Logger::warning('Health check failed', self::$results);
        }

        return self::$results;
    }

    /**
     * Get results as JSON (for API endpoint)
     * @return string
     */
    public static function getJson()
    {
        if (empty(self::$results)) {
            self::runAll();
        }

        return json_encode(self::$results, JSON_PRETTY_PRINT);
    }

    /**
     * Register default health checks
     */
    public static function registerDefaults()
    {
        // Database check
        self::register('database', function () {
            try {
                $conn = DB::getConnection();
                $result = mysqli_query($conn, 'SELECT 1');
                return [
                    'healthy' => $result !== false,
                    'message' => $result !== false ? 'Connected' : 'Query failed'
                ];
            } catch (Exception $e) {
                return ['healthy' => false, 'message' => $e->getMessage()];
            }
        });

        // Memory check
        self::register('memory', function () {
            $limit = ini_get('memory_limit');
            $limit_bytes = self::parseBytes($limit);
            $used = memory_get_usage(true);
            $percent = ($used / $limit_bytes) * 100;

            return [
                'healthy' => $percent < ALERT_MEMORY_PERCENT,
                'message' => sprintf('%.1f%% used (%s / %s)', $percent, self::formatBytes($used), $limit),
                'used' => $used,
                'limit' => $limit_bytes,
                'percent' => round($percent, 1)
            ];
        });

        // Disk space check
        self::register('disk', function () {
            $free = disk_free_space('/');
            $total = disk_total_space('/');
            $percent_free = ($free / $total) * 100;

            return [
                'healthy' => $percent_free > 10,
                'message' => sprintf('%.1f%% free (%s available)', $percent_free, self::formatBytes($free)),
                'free' => $free,
                'total' => $total
            ];
        });

        // Session check
        self::register('session', function () {
            return [
                'healthy' => session_status() === PHP_SESSION_ACTIVE,
                'message' => session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not active'
            ];
        });

        // Log directory writable
        self::register('logs', function () {
            $writable = is_writable(LOG_DIR);
            return [
                'healthy' => $writable,
                'message' => $writable ? 'Writable' : 'Not writable'
            ];
        });
    }

    /**
     * Parse bytes from php.ini format
     */
    private static function parseBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;

        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Format bytes to human readable
     */
    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// ================================================
// ALERT MANAGER
// ================================================

/**
 * Manage and send alerts
 */
class AlertManager
{
    private static $alerts = [];
    private static $handlers = [];

    /**
     * Register an alert handler
     * @param string $type Alert type
     * @param callable $handler Handler function
     */
    public static function registerHandler($type, $handler)
    {
        if (!isset(self::$handlers[$type])) {
            self::$handlers[$type] = [];
        }
        self::$handlers[$type][] = $handler;
    }

    /**
     * Trigger an alert
     * @param string $type Alert type
     * @param array $data Alert data
     */
    public static function trigger($type, $data = [])
    {
        $alert = [
            'type' => $type,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        self::$alerts[] = $alert;

        // Log the alert
        Logger::warning("Alert triggered: {$type}", $data);

        // Store alert
        self::storeAlert($alert);

        // Call handlers
        if (isset(self::$handlers[$type])) {
            foreach (self::$handlers[$type] as $handler) {
                try {
                    call_user_func($handler, $alert);
                } catch (Exception $e) {
                    Logger::error('Alert handler failed', ['error' => $e->getMessage()]);
                }
            }
        }

        // Call wildcard handlers
        if (isset(self::$handlers['*'])) {
            foreach (self::$handlers['*'] as $handler) {
                try {
                    call_user_func($handler, $alert);
                } catch (Exception $e) {
                    Logger::error('Alert handler failed', ['error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Store alert to file
     */
    private static function storeAlert($alert)
    {
        $file = LOG_DIR . '/alerts_' . date('Y-m-d') . '.json';

        $alerts = [];
        if (file_exists($file)) {
            $alerts = json_decode(file_get_contents($file), true) ?: [];
        }

        $alerts[] = $alert;

        file_put_contents($file, json_encode($alerts, JSON_PRETTY_PRINT));
    }

    /**
     * Get recent alerts
     * @param int $limit Number of alerts to return
     * @return array
     */
    public static function getRecent($limit = 50)
    {
        $file = LOG_DIR . '/alerts_' . date('Y-m-d') . '.json';

        if (!file_exists($file)) {
            return [];
        }

        $alerts = json_decode(file_get_contents($file), true) ?: [];

        return array_slice(array_reverse($alerts), 0, $limit);
    }
}

// ================================================
// REQUEST METRICS
// ================================================

/**
 * Track request metrics
 */
class RequestMetrics
{
    private static $start_time;
    private static $metrics = [];

    /**
     * Start tracking a request
     */
    public static function start()
    {
        self::$start_time = microtime(true);
        self::$metrics = [
            'start_memory' => memory_get_usage(true),
            'queries' => 0,
            'query_time' => 0
        ];
    }

    /**
     * Track a database query
     * @param float $duration Query duration in ms
     */
    public static function trackQuery($duration)
    {
        self::$metrics['queries']++;
        self::$metrics['query_time'] += $duration;

        // Log slow queries
        if ($duration > ALERT_SLOW_QUERY_MS) {
            Logger::warning('Slow query detected', ['duration_ms' => $duration]);
        }
    }

    /**
     * End tracking and log metrics
     */
    public static function end()
    {
        $duration = (microtime(true) - self::$start_time) * 1000;

        self::$metrics['duration_ms'] = round($duration, 2);
        self::$metrics['end_memory'] = memory_get_usage(true);
        self::$metrics['memory_used'] = self::$metrics['end_memory'] - self::$metrics['start_memory'];
        self::$metrics['url'] = $_SERVER['REQUEST_URI'] ?? 'CLI';
        self::$metrics['method'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';

        // Log slow requests
        if ($duration > ALERT_RESPONSE_TIME_MS) {
            Logger::warning('Slow request', self::$metrics);
            AlertManager::trigger('slow_request', self::$metrics);
        }

        // Log metrics for analysis
        self::storeMetrics(self::$metrics);

        return self::$metrics;
    }

    /**
     * Store metrics for later analysis
     */
    private static function storeMetrics($metrics)
    {
        $file = LOG_DIR . '/metrics_' . date('Y-m-d') . '.jsonl';

        $line = json_encode($metrics) . "\n";
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get current metrics
     * @return array
     */
    public static function getMetrics()
    {
        return self::$metrics;
    }
}

// ================================================
// GLOBAL ERROR HANDLER
// ================================================

/**
 * Register global error and exception handlers
 */
function register_error_handlers()
{
    // Exception handler
    set_exception_handler(function ($e) {
        ErrorTracker::track($e);

        // Show error page or JSON based on request type
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($is_ajax || strpos($_SERVER['REQUEST_URI'] ?? '', 'api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'An error occurred. Please try again later.',
                'error_id' => uniqid('err_')
            ]);
        } else {
            // Redirect to error page or show generic error
            echo '<h1>Oops! Something went wrong.</h1>';
            echo '<p>Please try again later or contact support.</p>';
        }
    });

    // Error handler (convert to exceptions)
    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    // Shutdown handler (catch fatal errors)
    register_shutdown_function(function () {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $e = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            ErrorTracker::track($e);
        }

        // End request metrics
        if (class_exists('RequestMetrics')) {
            RequestMetrics::end();
        }
    });
}

// ================================================
// AUTO-INITIALIZE
// ================================================

// Initialize logger
Logger::init();

// Register default health checks
HealthCheck::registerDefaults();
