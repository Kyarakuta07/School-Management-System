<?php
session_start();
include 'connection.php';

// --- 1. IMPORT PHPMAILER (WAJIB ADA) ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pastikan path folder phpmailer kamu benar
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

date_default_timezone_set('Asia/Jakarta');

// --- 2. VALIDASI INPUT ---
if (!isset($_GET['username']) || empty($_GET['username'])) {
    header("Location: index.php");
    exit();
}

$username = trim($_GET['username']);
$current_time = date("Y-m-d H:i:s");

// --- 3. AMBIL DATA USER (EMAIL) DARI DATABASE ---
$stmt = mysqli_prepare($conn, "SELECT email, nama_lengkap FROM nethera WHERE username = ? AND status_akun = 'Pending'");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Jika user tidak ditemukan
if (!$user) {
    header("Location: verify_otp.php?user=" . urlencode($username) . "&status=invalid");
    exit();
}

// --- 4. GENERATE OTP BARU ---
$new_otp = rand(100000, 999999); // Variabel OTP Baru
$new_expires = date("Y-m-d H:i:s", time() + (5 * 60)); // 5 menit

// --- 5. UPDATE DATABASE ---
$stmt_update = mysqli_prepare($conn, "UPDATE nethera SET otp_code = ?, otp_expires = ? WHERE username = ?");
mysqli_stmt_bind_param($stmt_update, "sss", $new_otp, $new_expires, $username);

if (mysqli_stmt_execute($stmt_update)) {
    
    // --- 6. KIRIM EMAIL ---
    $mail = new PHPMailer(true);
    
    try {
        // Konfigurasi SMTP (Mengambil dari .env)
        $mail->isSMTP(); 
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER'); // AMAN
        $mail->Password = getenv('SMTP_PASS'); // AMAN
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port = 465;

        // Penerima
        $mail->setFrom('mediterraneanofegypt@gmail.com', 'MOE Registration');
        // PERBAIKAN PENTING: Gunakan $user['email'], bukan $email
        $mail->addAddress($user['email'], $username); 

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = 'KODE BARU: Verifikasi Akun Mediterranean of Egypt';
        
        // PERBAIKAN PENTING: Di bawah ini saya ganti {$otp_code} menjadi {$new_otp}
        $mail->Body = <<<EMAIL_BODY
            <html>
            <body style='font-family: Lato, sans-serif; background-color: #0d0d0d; color: #fff; padding: 20px; text-align: center;'>
                <div style='max-width: 500px; margin: auto; padding: 20px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; border: 1px solid #DAA520;'>
                    <h2 style='color: #DAA520; font-family: Cinzel;'>Kode OTP Baru Anda</h2>
                    <p>Halo <strong>{$username}</strong>,</p>
                    <p>Anda telah meminta pengiriman ulang kode verifikasi:</p>
                    <h1 style='color: #DAA520; font-size: 3rem; letter-spacing: 5px; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 5px;'>
                        {$new_otp}
                    </h1>
                    <p>Kode ini berlaku selama 5 menit.</p>
                    <hr style='border-color: rgba(255, 255, 255, 0.2);'>
                </div>
            </body>
            </html>
EMAIL_BODY;

        $mail->send();
        
        // Berhasil Kirim -> Redirect dengan pesan sukses
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=resent_success");
        exit();

    } catch (Exception $e) {
        // Gagal Kirim Email
        error_log("Mailer Error: " . $mail->ErrorInfo);
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=email_fail");
        exit();
    }

} else {
    // Gagal Update Database
    header("Location: verify_otp.php?user=" . urlencode($username) . "&status=db_error");
    exit();
}
?>