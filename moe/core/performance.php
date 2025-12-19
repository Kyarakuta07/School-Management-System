<?php
/**
 * Performance Utilities
 * Mediterranean of Egypt - School Management System
 * 
 * Provides caching, compression, and optimization utilities.
 * 
 * @author MOE Development Team
 * @version 1.0.0
 */

// ================================================
// SIMPLE FILE-BASED CACHE
// ================================================

/**
 * Simple file-based cache for storing data
 */
class Cache
{
    private static $cache_dir = null;
    private static $enabled = true;

    /**
     * Initialize cache directory
     */
    public static function init($dir = null)
    {
        self::$cache_dir = $dir ?: sys_get_temp_dir() . '/moe_cache';

        if (!file_exists(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
    }

    /**
     * Enable or disable cache
     * @param bool $enabled
     */
    public static function setEnabled($enabled)
    {
        self::$enabled = $enabled;
    }

    /**
     * Get cache file path
     * @param string $key
     * @return string
     */
    private static function getPath($key)
    {
        if (self::$cache_dir === null) {
            self::init();
        }
        return self::$cache_dir . '/' . md5($key) . '.cache';
    }

    /**
     * Get cached value
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (!self::$enabled)
            return $default;

        $path = self::getPath($key);

        if (!file_exists($path)) {
            return $default;
        }

        $data = unserialize(file_get_contents($path));

        // Check expiration
        if ($data['expires'] !== null && time() > $data['expires']) {
            unlink($path);
            return $default;
        }

        return $data['value'];
    }

    /**
     * Set cached value
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (null = forever)
     * @return bool
     */
    public static function set($key, $value, $ttl = 3600)
    {
        if (!self::$enabled)
            return false;

        $path = self::getPath($key);

        $data = [
            'value' => $value,
            'expires' => $ttl !== null ? time() + $ttl : null,
            'created' => time()
        ];

        return file_put_contents($path, serialize($data)) !== false;
    }

    /**
     * Delete cached value
     * @param string $key
     * @return bool
     */
    public static function delete($key)
    {
        $path = self::getPath($key);

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Clear all cache
     * @return int Number of files deleted
     */
    public static function clear()
    {
        if (self::$cache_dir === null) {
            self::init();
        }

        $count = 0;
        $files = glob(self::$cache_dir . '/*.cache');

        foreach ($files as $file) {
            if (unlink($file)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get or set cache (remember pattern)
     * @param string $key Cache key
     * @param callable $callback Function to generate value if not cached
     * @param int $ttl Time to live in seconds
     * @return mixed
     */
    public static function remember($key, $callback, $ttl = 3600)
    {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }
}

// ================================================
// OUTPUT COMPRESSION
// ================================================

/**
 * Enable Gzip compression for responses
 */
function enable_gzip_compression()
{
    // Only enable if not already compressed and client accepts gzip
    if (
        !headers_sent() &&
        !ob_get_level() &&
        extension_loaded('zlib') &&
        isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
        strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false
    ) {
        ob_start('ob_gzhandler');
        return true;
    }
    return false;
}

// ================================================
// ASSET OPTIMIZATION
// ================================================

/**
 * Generate cache-busting URL for assets
 * @param string $path Asset path
 * @param string $base_path Base directory path
 * @return string URL with version query string
 */
function asset_url($path, $base_path = '')
{
    $full_path = $base_path . $path;

    if (file_exists($full_path)) {
        $version = filemtime($full_path);
        return $path . '?v=' . $version;
    }

    return $path;
}

/**
 * Inline critical CSS
 * @param string $css_path Path to CSS file
 * @return string Style tag with CSS content
 */
function inline_critical_css($css_path)
{
    if (file_exists($css_path)) {
        $css = file_get_contents($css_path);
        // Minify CSS
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace([' {', '{ ', ' }', '} ', ': ', ' :', '; ', ' ;'], ['{', '{', '}', '}', ':', ':', ';', ';'], $css);
        return '<style>' . $css . '</style>';
    }
    return '';
}

// ================================================
// DATABASE QUERY OPTIMIZATION
// ================================================

/**
 * Query result caching wrapper
 * @param string $key Cache key
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @param int $ttl Cache TTL
 * @return array
 */
function cached_query($key, $sql, $params = [], $ttl = 300)
{
    return Cache::remember($key, function () use ($sql, $params) {
        return DB::query($sql, $params);
    }, $ttl);
}

/**
 * Clear cache for specific entity
 * @param string $entity Entity name (e.g., 'user', 'pet')
 * @param int|null $id Optional specific ID
 */
function invalidate_cache($entity, $id = null)
{
    $pattern = $id !== null ? "{$entity}_{$id}_*" : "{$entity}_*";

    // For now, just clear all cache
    // In production, you'd implement pattern-based deletion
    Cache::delete("{$entity}_{$id}");
}

// ================================================
// LAZY LOADING
// ================================================

/**
 * Deferred loading of resources
 */
class LazyLoader
{
    private static $deferred = [];

    /**
     * Register a deferred load
     * @param string $key Unique key
     * @param callable $loader Function to load resource
     */
    public static function defer($key, $loader)
    {
        self::$deferred[$key] = [
            'loader' => $loader,
            'loaded' => false,
            'value' => null
        ];
    }

    /**
     * Get deferred resource (loads on first access)
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        if (!isset(self::$deferred[$key])) {
            return null;
        }

        if (!self::$deferred[$key]['loaded']) {
            self::$deferred[$key]['value'] = call_user_func(self::$deferred[$key]['loader']);
            self::$deferred[$key]['loaded'] = true;
        }

        return self::$deferred[$key]['value'];
    }
}

// ================================================
// HTTP CACHING HEADERS
// ================================================

/**
 * Set caching headers for browser caching
 * @param int $seconds Cache duration in seconds
 * @param bool $public Whether cache is public or private
 */
function set_cache_headers($seconds = 3600, $public = true)
{
    if (headers_sent())
        return;

    $type = $public ? 'public' : 'private';

    header("Cache-Control: {$type}, max-age={$seconds}");
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
    header('Pragma: cache');
}

/**
 * Set no-cache headers
 */
function set_no_cache_headers()
{
    if (headers_sent())
        return;

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

/**
 * Check if content is modified (ETag support)
 * @param string $content Content to check
 * @return bool True if content was modified
 */
function check_not_modified($content)
{
    $etag = '"' . md5($content) . '"';

    header("ETag: {$etag}");

    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        header('HTTP/1.1 304 Not Modified');
        return false;
    }

    return true;
}

// ================================================
// PERFORMANCE MONITORING
// ================================================

/**
 * Simple performance timer
 */
class PerfTimer
{
    private static $timers = [];

    /**
     * Start a timer
     * @param string $name Timer name
     */
    public static function start($name)
    {
        self::$timers[$name] = [
            'start' => microtime(true),
            'end' => null,
            'duration' => null
        ];
    }

    /**
     * Stop a timer
     * @param string $name Timer name
     * @return float Duration in milliseconds
     */
    public static function stop($name)
    {
        if (!isset(self::$timers[$name])) {
            return 0;
        }

        self::$timers[$name]['end'] = microtime(true);
        self::$timers[$name]['duration'] = (self::$timers[$name]['end'] - self::$timers[$name]['start']) * 1000;

        return self::$timers[$name]['duration'];
    }

    /**
     * Get timer duration
     * @param string $name Timer name
     * @return float|null Duration in milliseconds
     */
    public static function get($name)
    {
        return self::$timers[$name]['duration'] ?? null;
    }

    /**
     * Get all timers
     * @return array
     */
    public static function getAll()
    {
        return self::$timers;
    }

    /**
     * Log all timers
     */
    public static function logAll()
    {
        foreach (self::$timers as $name => $timer) {
            error_log(sprintf('[PERF] %s: %.2fms', $name, $timer['duration'] ?? 0));
        }
    }
}

/**
 * Log slow queries
 * @param string $sql SQL query
 * @param float $duration Duration in milliseconds
 * @param float $threshold Threshold for logging (default 100ms)
 */
function log_slow_query($sql, $duration, $threshold = 100)
{
    if ($duration > $threshold) {
        error_log(sprintf('[SLOW QUERY %.2fms] %s', $duration, $sql));
    }
}
