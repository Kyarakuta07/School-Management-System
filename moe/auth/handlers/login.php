<?php
require_once __DIR__ . '/../../core/security_config.php';
session_start();
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/rate_limiter.php';
require_once __DIR__ . '/../../core/security_logger.php';
require_once __DIR__ . '/../../core/account_lockout.php';
require_once __DIR__ . '/../../config/connection.php';

// CSRF validation
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    error_log("CSRF token validation failed for login attempt");
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

$username_input = mysqli_real_escape_string($conn, trim($_POST['username']));
$password_input = $_POST['password'];

// Rate limiting - 5 attempts per 15 minutes per username + IP
$limiter = new RateLimiter($conn);
$identifier = $username_input . '_' . $_SERVER['REMOTE_ADDR'];
$check = $limiter->checkLimit($identifier, 'login', 5, 15);

if (!$check['allowed']) {
    header("Location: ../../index.php?pesan=rate_limited");
    exit();
}

// Check account lockout BEFORE attempting login
$lockout_status = check_account_lockout($conn, $username_input);

if ($lockout_status['locked']) {
    $locked_until = date('H:i', strtotime($lockout_status['locked_until']));
    log_security_event(
        $conn,
        'locked_account_attempt',
        null,
        "Locked account '$username_input' attempted login. Locked until {$lockout_status['locked_until']}",
        'warning'
    );
    header("Location: ../../index.php?pesan=account_locked&until=$locked_until");
    exit();
}

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
        // 3. CEK STATUS AKTIF (BUSINESS RULE)
        if ($data['status_akun'] !== 'Aktif') {
            // Log failed login - inactive account
            log_security_event(
                $conn,
                'login_inactive_account',
                $data['id_nethera'],
                "User '$username_input' attempted login with inactive account",
                'warning'
            );
            // Arahkan ke halaman login dengan pesan penolakan
            header("Location: ../../index.php?pesan=pending_approval");
            exit();
        }

        // 4. LOGIN SUKSES - Reset rate limit AND failed attempts
        $limiter->resetLimit($identifier, 'login');
        reset_login_attempts($conn, $username_input);

        // Regenerate session ID for security
        session_regenerate_id(true);

        // BUAT SESSION
        $_SESSION['id_nethera'] = $data['id_nethera'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['status_login'] = "berhasil";
        $_SESSION['last_activity'] = time();

        // Update last login time - use prepared statement for security
        $stmt_update = mysqli_prepare($conn, "UPDATE nethera SET last_login = NOW() WHERE id_nethera = ?");
        mysqli_stmt_bind_param($stmt_update, "i", $data['id_nethera']);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);

        // Regenerate CSRF token
        regenerate_csrf_token();

        // Log successful login
        log_security_event(
            $conn,
            'successful_login',
            $data['id_nethera'],
            "User '{$data['username']}' ({$data['role']}) logged in successfully",
            'info'
        );

        // Redirect pengguna berdasarkan rolenya
        if ($data['role'] == 'Vasiki') {
            header("Location: ../../admin/index.php");
            exit();
        } elseif ($data['role'] == 'Hakaes') {
            // Hakaes goes to admin manage classes page
            header("Location: ../../admin/pages/manage_classes.php");
            exit();
        } else {
            // Nethera and Anubis go to user dashboard
            header("Location: ../../user/beranda.php");
            exit();
        }

    } else {
        // LOGIN GAGAL - Increment failed attempts and check for lockout
        $lockout_result = increment_failed_attempts($conn, $username_input, 10, 30);

        // Log failed login attempt
        log_security_event(
            $conn,
            'failed_login',
            null,
            "Failed login for '$username_input' from {$_SERVER['REMOTE_ADDR']}. Attempts: {$lockout_result['attempts']}/10",
            $lockout_result['locked'] ? 'critical' : 'warning'
        );

        mysqli_stmt_close($stmt);

        if ($lockout_result['locked']) {
            $locked_until = date('H:i', strtotime($lockout_result['locked_until']));
            header("Location: ../../index.php?pesan=account_locked&until=$locked_until");
        } else {
            header("Location: ../../index.php?pesan=gagal");
        }
        exit();
    }
} else {
    // Error saat prepare statement
    error_log("Login query error: " . mysqli_error($conn));
    header("Location: ../../index.php?pesan=gagal");
    exit();
}
?>