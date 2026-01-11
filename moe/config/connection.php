<?php

// Fungsi sederhana untuk memuat environment variables dari file .env
// Ini mencegah kita perlu menginstall library berat di cPanel
function loadEnv($path)
{
    if (!file_exists($path)) {
        error_log("loadEnv: File not found at: " . $path);
        return false;
    }

    $content = file_get_contents($path);

    // Remove BOM if present (UTF-8 BOM)
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    // Normalize line endings
    $content = str_replace(["\r\n", "\r"], "\n", $content);

    $lines = explode("\n", $content);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Must contain =
        if (strpos($line, '=') === false) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove surrounding quotes (both single and double)
        if (
            (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
        ) {
            $value = substr($value, 1, -1);
        }

        // Set environment variable
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    return true;
}

// Muat .env dari direktori parent (moe/)
$env_path = __DIR__ . '/../.env';
$env_loaded = loadEnv($env_path);

// Debug mode - add ?env_debug=1 to any URL to check
if (isset($_GET['env_debug'])) {
    echo "<pre style='background:#333;color:#fff;padding:20px;'>";
    echo "ENV PATH: " . realpath($env_path) . "\n";
    echo "ENV EXISTS: " . (file_exists($env_path) ? 'YES' : 'NO') . "\n";
    echo "ENV LOADED: " . ($env_loaded ? 'YES' : 'NO') . "\n";
    echo "---\n";
    echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
    echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "\n";
    echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "\n";
    echo "SMTP_USER: " . (getenv('SMTP_USER') ?: 'NOT SET') . "\n";
    echo "SMTP_PASS: " . (getenv('SMTP_PASS') ? 'SET (' . strlen(getenv('SMTP_PASS')) . ' chars)' : 'NOT SET') . "\n";
    echo "</pre>";
    exit();
}

// Ambil kredensial dari environment variable
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// Cek apakah variabel berhasil dimuat
if (!$servername || !$username || !$dbname) {
    error_log("Database configuration missing. Path checked: " . realpath($env_path));
    error_log("DB_HOST=" . ($servername ?: 'empty') . ", DB_USER=" . ($username ?: 'empty') . ", DB_NAME=" . ($dbname ?: 'empty'));
    die("System Error: Configuration missing. Please contact administrator.");
}

// Buat koneksi
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Cek koneksi
if (!$conn) {
    error_log("Connection failed: " . mysqli_connect_error());
    // Pesan error generik untuk user
    die("Connection failed. Please try again later.");
}

// NO CLOSING PHP TAG - Prevents accidental whitespace/newlines in JSON API responses