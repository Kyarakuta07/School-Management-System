<?php
require_once __DIR__ . '/../../core/security_config.php';

// Wajib ada di awal
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hapus cookie session jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hancurkan sesi di server
session_destroy();

// --- PENAMBAHAN DI SINI ---
// Redirect ke halaman login dengan parameter pesan logout
header("Location: ../../index.php?pesan=logout");

// PENTING: Menghentikan eksekusi script untuk memastikan redirect berjalan
exit();
?>