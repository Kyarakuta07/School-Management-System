<?php

// Fungsi sederhana untuk memuat environment variables dari file .env
// Ini mencegah kita perlu menginstall library berat di cPanel
function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Lewati komentar
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), "\"'");

        // [PERBAIKAN] Hapus pengecekan if exists.
        // Langsung paksa timpa variabel environment dengan isi file .env
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Muat .env dari direktori saat ini
loadEnv(__DIR__ . '/.env');

// Ambil kredensial dari environment variable
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// Cek apakah variabel berhasil dimuat
if (!$servername || !$username || !$password || !$dbname) {
    error_log("Database configuration missing from .env file.");
    // Tampilkan sedikit info debug jika masih error (bisa dihapus nanti)
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

?>