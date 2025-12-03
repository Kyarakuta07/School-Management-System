<?php

// Wajib ada di awal
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi di server
session_destroy();

// --- PENAMBAHAN DI SINI ---
// Redirect ke halaman login dengan parameter pesan logout
header("Location: index.php?pesan=logout");

// PENTING: Menghentikan eksekusi script untuk memastikan redirect berjalan
exit(); 
?>