<?php

// Fungsi sederhana untuk memuat environment variables dari file .env
// Ini mencegah kita perlu menginstall library berat di cPanel
function loadEnv($path)
{
    if (!file_exists($path)) {
        // Jika file .env tidak ada, kita asumsikan environment variables sudah diset di server
        // atau kita biarkan error nanti di koneksi
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), "\"'");

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Muat .env dari direktori saat ini
loadEnv(__DIR__ . '/.env');

// Ambil kredensial dari environment variable
// Gunakan getenv() agar aman
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// Cek apakah variabel berhasil dimuat
if (!$servername || !$username || !$password || !$dbname) {
    // Gunakan error_log untuk mencatat error di server, bukan menampilkannya ke user
    error_log("Database configuration missing from .env file.");
    die("System Error: Configuration missing. Please contact administrator.");
}

// Buat koneksi
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Cek koneksi
if (!$conn) {
    // JANGAN tampilkan mysqli_connect_error() ke user di production!
    // Itu membocorkan path direktori server kamu.
    error_log("Connection failed: " . mysqli_connect_error());
    die("Connection failed. Please try again later.");
}

?>