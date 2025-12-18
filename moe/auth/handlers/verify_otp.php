<?php
require_once __DIR__ . '/../../core/security_config.php';
session_start();
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/sanitization.php';
require_once __DIR__ . '/../../core/rate_limiter.php';
// Pastikan file koneksi sudah menggunakan versi .env yang aman tadi
require_once __DIR__ . '/../../config/connection.php';

// 1. Set Timezone (Sangat Penting agar sinkron dengan Database)
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// CSRF validation
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    error_log("CSRF token validation failed for OTP verification");
    header("Location: ../views/verify_otp.php?status=csrf_failed");
    exit();
}

$username = sanitize_input($_POST['username']);
$otp_code = trim($_POST['otp_code']);
$current_time = date("Y-m-d H:i:s");

// Validate OTP format
if (!validate_otp_format($otp_code)) {
    header("Location: ../views/verify_otp.php?user=" . urlencode($username) . "&status=invalid_format");
    exit();
}

// Rate limiting - 5 attempts per OTP session
$limiter = new RateLimiter($conn);
$check = $limiter->checkLimit($username, 'otp_verify', 5, 60);

if (!$check['allowed']) {
    header("Location: ../views/verify_otp.php?user=" . urlencode($username) . "&status=rate_limited");
    exit();
}

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
        header("Location: ../views/verify_otp.php?user=" . urlencode($username) . "&status=invalid");
        exit();
    }

    // 4. Cek 2: Apakah kode sudah kadaluarsa? (Logic_001 Fix)
    // Jika Waktu Expired di DB < Waktu Sekarang, maka sudah basi.
    if ($user_otp_data['otp_expires'] < $current_time) {
        // GAGAL KARENA EXPIRED
        // Jangan auto-resend jika tidak ada fungsi mailer di sini.
        // Lebih aman suruh user request ulang manual.
        header("Location: ../views/verify_otp.php?user=" . urlencode($username) . "&status=expired");
        exit();
    }

    // 5. Verifikasi SUKSES!
    // Reset rate limit
    $limiter->resetLimit($username, 'otp_verify');

    // Hapus kode OTP agar tidak bisa dipakai lagi (Replay Attack Prevention)
    // Tapi JANGAN ubah status jadi 'Aktif' dulu jika ada approval admin.
    // Sesuai flow kamu: hapus OTP -> masuk halaman success -> tunggu admin.

    // Regenerate session ID after successful verification
    session_regenerate_id(true);
    $_SESSION['email_verified'] = true;
    $_SESSION['verified_at'] = time();
    $_SESSION['verified_user'] = $username;

    $sql_update = "UPDATE nethera SET otp_code = NULL, otp_expires = NULL, email_verified_at = NOW() WHERE username = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "s", $username);

    if (mysqli_stmt_execute($stmt_update)) {
        mysqli_stmt_close($stmt_update);

        // Regenerate CSRF token
        regenerate_csrf_token();

        // Redirect ke halaman sukses menunggu approval
        header("Location: ../views/success.php?username=" . urlencode($username));
        exit();
    } else {
        mysqli_stmt_close($stmt_update);
        header("Location: ../views/verify_otp.php?user=" . urlencode($username) . "&status=db_error");
        exit();
    }

} else {
    die("System Error: Unable to prepare verification.");
}
?>