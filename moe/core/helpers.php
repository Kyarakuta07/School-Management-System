<?php
/**
 * Common Helper Functions
 * Mediterranean of Egypt - School Management System
 * 
 * Utility functions used across the application.
 */

// ==================================================
// STRING HELPERS
// ==================================================

/**
 * Safely output HTML-escaped string
 * @param string $string
 * @return string
 */
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random string
 * @param int $length
 * @return string
 */
function random_string($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate a random OTP code
 * @param int $length
 * @return string
 */
function generate_otp($length = 6)
{
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// ==================================================
// DATE/TIME HELPERS
// ==================================================

/**
 * Format date to Indonesian format
 * @param string $date Date string
 * @param bool $withTime Include time
 * @return string
 */
function format_date_id($date, $withTime = false)
{
    if (empty($date))
        return '-';

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $months[(int) date('n', $timestamp)];
    $year = date('Y', $timestamp);

    $formatted = "$day $month $year";

    if ($withTime) {
        $formatted .= ' ' . date('H:i', $timestamp);
    }

    return $formatted;
}

/**
 * Get human-readable time ago
 * @param string|int $datetime DateTime string or timestamp
 * @return string
 */
function time_ago($datetime)
{
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "$mins menit lalu";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "$hours jam lalu";
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "$days hari lalu";
    } else {
        return format_date_id($datetime);
    }
}

// ==================================================
// ARRAY HELPERS
// ==================================================

/**
 * Get value from array with default
 * @param array $array
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function arr_get($array, $key, $default = null)
{
    return $array[$key] ?? $default;
}

/**
 * Pluck a key from an array of arrays
 * @param array $array
 * @param string $key
 * @return array
 */
function arr_pluck($array, $key)
{
    return array_map(function ($item) use ($key) {
        return $item[$key] ?? null;
    }, $array);
}

// ==================================================
// URL HELPERS
// ==================================================

/**
 * Redirect to URL
 * @param string $url
 */
function redirect($url)
{
    header("Location: $url");
    exit();
}

/**
 * Get current URL path
 * @return string
 */
function current_path()
{
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}

/**
 * Check if current page matches path
 * @param string $path
 * @return bool
 */
function is_current_page($path)
{
    return strpos(current_path(), $path) !== false;
}

// ==================================================
// NUMBER HELPERS
// ==================================================

/**
 * Format number with thousand separator
 * @param int|float $number
 * @return string
 */
function format_number($number)
{
    return number_format($number, 0, ',', '.');
}

/**
 * Format gold with icon
 * @param int $gold
 * @return string HTML string
 */
function format_gold($gold)
{
    return '<span class="gold-amount">🪙 ' . format_number($gold) . '</span>';
}

// ==================================================
// JSON HELPERS
// ==================================================

/**
 * Return JSON response and exit
 * @param array $data
 * @param int $statusCode
 */
function json_response($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Return success JSON response
 * @param mixed $data
 * @param string $message
 */
function json_success($data = null, $message = null)
{
    $response = ['success' => true];
    if ($message !== null)
        $response['message'] = $message;
    if ($data !== null)
        $response['data'] = $data;
    json_response($response);
}

/**
 * Return error JSON response
 * @param string $error
 * @param int $statusCode
 */
function json_error($error, $statusCode = 400)
{
    json_response(['success' => false, 'error' => $error], $statusCode);
}

// ==================================================
// VALIDATION HELPERS
// ==================================================

/**
 * Check if request is POST
 * @return bool
 */
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 * @return bool
 */
function is_get()
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Check if request is AJAX
 * @return bool
 */
function is_ajax()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get POST value with default
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function post($key, $default = null)
{
    return $_POST[$key] ?? $default;
}

/**
 * Get GET value with default
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get($key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/**
 * Get JSON input body (for API endpoints)
 * @return array
 */
function json_input()
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ==================================================
// FILE HELPERS  
// ==================================================

/**
 * Get file extension
 * @param string $filename
 * @return string
 */
function get_extension($filename)
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Generate unique filename
 * @param string $originalName
 * @return string
 */
function unique_filename($originalName)
{
    $ext = get_extension($originalName);
    return uniqid() . '_' . time() . '.' . $ext;
}

// ==================================================
// DEBUG HELPERS (Use only in development)
// ==================================================

/**
 * Dump variable and die
 * @param mixed $var
 */
function dd($var)
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    exit();
}

/**
 * Dump variable without dying
 * @param mixed $var
 */
function dump($var)
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

/**
 * Log to error log with timestamp
 * @param string $message
 * @param string $level
 */
function app_log($message, $level = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] [$level] $message");
}

// ==================================================
// PUNISHMENT HELPERS
// ==================================================

/**
 * Check if user has active punishment
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check
 * @return array|false Returns punishment data if active, false otherwise
 */
function has_active_punishment($conn, $user_id)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM punishment_log 
         WHERE id_nethera = ? AND status_hukuman = 'active' 
         ORDER BY tanggal_pelanggaran DESC LIMIT 1"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $punishment = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $punishment ?: false;
    }

    return false;
}

/**
 * Check if a specific feature is locked for user
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $feature Feature name (trapeza, pet, class)
 * @return bool True if locked
 */
function is_feature_locked($conn, $user_id, $feature)
{
    $punishment = has_active_punishment($conn, $user_id);

    if (!$punishment) {
        return false;
    }

    $locked = $punishment['locked_features'] ?? 'trapeza,pet,class';
    $locked_arr = explode(',', $locked);

    return in_array($feature, $locked_arr);
}

