<?php
session_start();
include 'connection.php'; // Pastikan path koneksi Anda benar

$username_input = $_POST['username'];
$password_input = $_POST['password'];

// 1. Ambil data user, termasuk HASHED PASSWORD dan STATUS
// Kunci keamanan: Kita ambil hash dari DB dan memverifikasinya di PHP
$sql = "SELECT id_nethera, nama_lengkap, username, role, status_akun, password
        FROM nethera
        WHERE username = ?";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username_input);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    // 2. CEK PENGGUNA DITEMUKAN DAN VERIFIKASI PASSWORD
    // Gunakan password_verify() untuk membandingkan input (plaintext) dengan hash (DB)
    if ($data && password_verify($password_input, $data['password'])) {
        session_regenerate_id(true);
        
        // 3. CEK STATUS AKTIF (BUSINESS RULE)
        if ($data['status_akun'] !== 'Aktif') {
            // Arahkan ke halaman login dengan pesan penolakan
            header("Location: index.php?pesan=pending_approval");
            exit();
        }

        // 4. LOGIN SUKSES, BUAT SESSION
        $_SESSION['id_nethera'] = $data['id_nethera'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['status_login'] = "berhasil";

        // Redirect pengguna berdasarkan rolenya
        if ($data['role'] == 'Vasiki') {
            header("Location: admin/index.php");
            exit();
        } else {
            header("Location: pengguna/beranda.php");
            exit();
        }

    } else {
        // Login Gagal (Username tidak ditemukan atau password tidak cocok)
        header("Location: index.php?pesan=gagal");
        exit();
    }
} else {
    // Error saat prepare statement
    die("Query error: " . mysqli_error($conn));
}
?>