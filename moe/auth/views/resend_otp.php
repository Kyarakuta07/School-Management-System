<?php
session_start();
require_once __DIR__ . '/../../config/connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// 1. Ambil username dan Cek Otentikasi
$username = isset($_GET['user']) ? $_GET['user'] : '';

if (empty($username)) {
    header("Location: register.php");
    exit();
}

$default_status = 'Pending';
$otp_code = rand(100000, 999999);
$otp_expires = date("Y-m-d H:i:s", time() + 300); // 5 menit

// 2. Update Database (Generate OTP dan Expiry Baru)
$sql_update = "UPDATE nethera SET otp_code = ?, otp_expires = ? WHERE username = ? AND status_akun = ?";
$stmt_update = mysqli_prepare($conn, $sql_update);

if ($stmt_update) {
    mysqli_stmt_bind_param($stmt_update, "ssss", $otp_code, $otp_expires, $username, $default_status);

    if (mysqli_stmt_execute($stmt_update)) {

        // 3. Ambil Email User untuk Pengiriman
        $sql_fetch_email = "SELECT email FROM nethera WHERE username = ?";
        $stmt_email = mysqli_prepare($conn, $sql_fetch_email);
        mysqli_stmt_bind_param($stmt_email, "s", $username);
        mysqli_stmt_execute($stmt_email);
        $result_email = mysqli_stmt_get_result($stmt_email);
        $user_data = mysqli_fetch_assoc($result_email);
        $user_email = $user_data['email'];

        // 4. Kirim Email Baru (Menggunakan PHPMailer)
        $mail = new PHPMailer(true);
        try {
            // Konfigurasi SMTP (Gunakan kredensial yang sama)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER');
            $mail->Password = getenv('SMTP_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom(getenv('SMTP_USER'), 'MOE Registration');
            $mail->addAddress($user_email, $username);

            // Content - FIX: Menggunakan Heredoc untuk mencegah korupsi HTML
            $mail->isHTML(true);
            $mail->Subject = 'Kode Verifikasi BARU Akun Mediterranean of Egypt (OTP)';
            $mail->Body = <<<EMAIL_BODY
                <html>
                <body>... (Sertakan kembali template email OTP HTML di sini dengan kode \$otp_code) ...</body>
                </html>
EMAIL_BODY;

            $mail->send();

            // 5. Berhasil: Redirect kembali ke halaman verifikasi dengan pesan sukses resend
            header("Location: verify_otp.php?user=" . urlencode($username) . "&status=resend_success");
            exit();

        } catch (Exception $e) {
            header("Location: verify_otp.php?user=" . urlencode($username) . "&status=email_fail");
            exit();
        }
    } else {
        error_log("OTP update failed for user $username: " . mysqli_error($conn));
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=error");
        exit();
    }

} else {
    header("Location: register.php");
    exit();
}
?>