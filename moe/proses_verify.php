<?php
session_start();
// Pastikan file koneksi sudah menggunakan versi .env yang aman tadi
include 'connection.php'; 

// 1. Set Timezone (Sangat Penting agar sinkron dengan Database)
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$username = trim($_POST['username']);
$otp_code = trim($_POST['otp_code']);
$current_time = date("Y-m-d H:i:s");

// 2. Ambil data OTP dari database
// Kita hanya cek user yang statusnya masih 'Pending'
$sql_fetch = "SELECT otp_code, otp_expires FROM nethera WHERE username = ? AND status_akun = 'Pending'";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);

if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "s", $username);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    $user_otp_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_fetch);

    // 3. Cek 1: Apakah user ditemukan? Apakah kode OTP cocok?
    if (!$user_otp_data || $user_otp_data['otp_code'] !== $otp_code) {
        // Jika salah, kembalikan dengan error
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=invalid");
        exit();
    }

    // 4. Cek 2: Apakah kode sudah kadaluarsa? (Logic_001 Fix)
    // Jika Waktu Expired di DB < Waktu Sekarang, maka sudah basi.
    if ($user_otp_data['otp_expires'] < $current_time) {
        // GAGAL KARENA EXPIRED
        // Jangan auto-resend jika tidak ada fungsi mailer di sini.
        // Lebih aman suruh user request ulang manual.
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=expired");
        exit();
    }

    // 5. Verifikasi SUKSES!
    // Hapus kode OTP agar tidak bisa dipakai lagi (Replay Attack Prevention)
    // Tapi JANGAN ubah status jadi 'Aktif' dulu jika ada approval admin.
    // Sesuai flow kamu: hapus OTP -> masuk halaman success -> tunggu admin.
    
    $sql_update = "UPDATE nethera SET otp_code = NULL, otp_expires = NULL WHERE username = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "s", $username);
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Redirect ke halaman sukses menunggu approval
        header("Location: success_page.php?username=" . urlencode($username));
        exit();
    } else {
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=db_error");
        exit();
    }

} else {
    die("System Error: Unable to prepare verification.");
}
?>