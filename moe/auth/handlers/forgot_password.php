<?php
require_once __DIR__ . '/../../core/security_config.php';
session_start();
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/rate_limiter.php';
require_once __DIR__ . '/../../config/connection.php';

// TEMPORARY DEBUG - HAPUS SETELAH FIX!
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "SMTP_USER: " . (getenv('SMTP_USER') ?: 'NOT SET') . "\n";
    echo "SMTP_PASS: " . (getenv('SMTP_PASS') ? 'SET (' . strlen(getenv('SMTP_PASS')) . ' chars)' : 'NOT SET') . "\n";
    echo ".env path: " . realpath(__DIR__ . '/../../.env') . "\n";
    echo ".env exists: " . (file_exists(__DIR__ . '/../../.env') ? 'YES' : 'NO') . "\n";
    echo "</pre>";
    exit();
}

// Include PHPMailer files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PASTIKAN PATH INI BENAR DARI ROOT
require __DIR__ . '/../../phpmailer/src/Exception.php';
require __DIR__ . '/../../phpmailer/src/PHPMailer.php';
require __DIR__ . '/../../phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/forgot_password.php");
    exit();
}

// CSRF validation
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    error_log("CSRF token validation failed for forgot password attempt");
    header("Location: ../views/forgot_password.php?status=csrf_failed");
    exit();
}

// Rate limiting - 3 attempts per 15 minutes per IP
$limiter = new RateLimiter($conn);
$check = $limiter->checkLimit($_SERVER['REMOTE_ADDR'], 'forgot_password', 3, 15);

if (!$check['allowed']) {
    header("Location: ../views/forgot_password.php?status=rate_limited");
    exit();
}

$user_email = trim($_POST['email']);
$token = bin2hex(random_bytes(32)); // Generate token 64 karakter (aman)
$expiry_time = date("Y-m-d H:i:s", time() + 1800); // Token berlaku 30 menit

// Auto-detect protocol and build reset link
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$reset_link = $protocol . $_SERVER['HTTP_HOST'] . "/moe/reset_password.php?token=" . $token;

// --- 1. CEK APAKAH EMAIL TERDAFTAR & AMBIL DATA USER ---
$sql_check = "SELECT id_nethera, username FROM nethera WHERE email = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);

if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "s", $user_email);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    $user_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_check);

    if (!$user_data) {
        // Email tidak ditemukan, redirect dengan pesan gagal
        header("Location: ../views/forgot_password.php?status=fail");
        exit();
    }

    $user_id = $user_data['id_nethera'];
    $username = $user_data['username'];


    // --- 2. UPDATE DATABASE DENGAN TOKEN RESET BARU ---
    $sql_update = "UPDATE nethera SET reset_token = ?, token_expires = ? WHERE id_nethera = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);

    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "ssi", $token, $expiry_time, $user_id);

        if (mysqli_stmt_execute($stmt_update)) {

            // --- 3. KIRIM EMAIL RESET ---
            $mail = new PHPMailer(true);
            try {
                // DEBUG: Log SMTP credentials (HAPUS SETELAH DEBUG!)
                $smtp_user = getenv('SMTP_USER');
                $smtp_pass = getenv('SMTP_PASS');
                error_log("DEBUG FORGOT PW - SMTP_USER: " . ($smtp_user ? substr($smtp_user, 0, 5) . '***' : 'NULL'));
                error_log("DEBUG FORGOT PW - SMTP_PASS: " . ($smtp_pass ? 'SET (length: ' . strlen($smtp_pass) . ')' : 'NULL'));

                if (empty($smtp_user) || empty($smtp_pass)) {
                    error_log("SMTP credentials are missing or empty!");
                    header("Location: ../views/forgot_password.php?status=email_fail&reason=config");
                    exit();
                }

                // Server settings
                $mail->isSMTP();
                $mail->SMTPDebug = 0; // Set to 2 for verbose debug output
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_user;
                $mail->Password = $smtp_pass;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                // Recipients
                $mail->setFrom($smtp_user, 'MOE Password Reset');
                $mail->addAddress($user_email, $username);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Permintaan Reset Password Akun MOE';
                $mail->Body = "
                    <html>
                    <body style='font-family: Lato, sans-serif; background-color: #0d0d0d; color: #fff; padding: 20px;'>
                        <div style='max-width: 500px; margin: auto; padding: 20px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; border: 1px solid #DAA520;'>
                            <h2 style='color: #DAA520; font-family: Cinzel;'>Reset Password</h2>
                            <p>Halo <strong>{$username}</strong>,</p>
                            <p>Anda telah meminta tautan reset password. Klik tombol di bawah ini:</p>
                            
                            <a href='{$reset_link}' style='display: inline-block; padding: 10px 20px; background-color: #DAA520; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px;'>
                                Reset Password
                            </a>

                            <p style='margin-top: 20px; font-size: 0.9em; color: #aaa;'>Tautan ini akan kedaluwarsa dalam 30 menit. Jika Anda tidak meminta reset ini, abaikan email ini.</p>
                        </div>
                    </body>
                    </html>
                ";

                $mail->send();
                error_log("DEBUG FORGOT PW - Email sent successfully to: " . $user_email);

                // 4. Sukses: Redirect ke halaman forgot password dengan status sukses
                header("Location: ../views/forgot_password.php?status=success");
                exit();

            } catch (Exception $e) {
                // Gagal Kirim Email - Log detail error
                error_log("PHPMailer Reset Error: " . $mail->ErrorInfo);
                error_log("PHPMailer Exception: " . $e->getMessage());
                header("Location: ../views/forgot_password.php?status=email_fail");
                exit();
            }

        } else {
            error_log("Password reset token update failed: " . mysqli_error($conn));
            header("Location: ../views/forgot_password.php?status=error");
            exit();
        }

    } else {
        error_log("Password reset statement prepare failed: " . mysqli_error($conn));
        header("Location: ../views/forgot_password.php?status=error");
        exit();
    }
}
?>