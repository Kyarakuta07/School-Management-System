<?php
session_start();
include 'connection.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PASTIKAN PATH PHPMailer BENAR
require 'phpmailer/src/Exception.php'; 
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

// 1. Ambil dan bersihkan data
$nama_lengkap = trim($_POST['nama_lengkap']);
$username = trim($_POST['username']);
$email = trim($_POST['email']); 
$noHP = trim($_POST['noHP']);
$tanggal_lahir = $_POST['tanggal_lahir'];
$password_input = $_POST['password'];
$id_sanctuary = null;
$periode_masuk = (int)$_POST['periode_masuk'];

// --- 2. CHECK DUPLIKASI (Jika duplikat, redirect) ---
$sql_check_exist = "SELECT id_nethera FROM nethera WHERE username = ? OR email = ? OR noHP = ?";
$stmt_check_exist = mysqli_prepare($conn, $sql_check_exist);

if ($stmt_check_exist) {
    mysqli_stmt_bind_param($stmt_check_exist, "sss", $username, $email, $noHP);
    mysqli_stmt_execute($stmt_check_exist);
    mysqli_stmt_store_result($stmt_check_exist);

    if (mysqli_stmt_num_rows($stmt_check_exist) > 0) {
        header("Location: register.php?error=duplicate_entry");
        exit();
    }
    mysqli_stmt_close($stmt_check_exist);
}


// --- 3. SECURITY: HASHING PASSWORD ---
$hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

// --- 4. STATUS & OTP GENERATION ---
$default_role = 'Nethera';
$default_status = 'Pending'; 
$otp_code = rand(100000, 999999); 
$otp_expires = date("Y-m-d H:i:s", time() + 300);

// 5. SQL INSERT (11 kolom)
$sql = "INSERT INTO nethera 
        (nama_lengkap, username, email, noHP, id_sanctuary, periode_masuk, password, role, status_akun, otp_code, otp_expires, tanggal_lahir) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; 

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    // Bind Parameter: s, s, s, s, i, i, s, s, s, s, s (Total 11)
mysqli_stmt_bind_param($stmt, "ssssiissssss",
        $nama_lengkap,        // s (1)
        $username,            // s (2)
        $email,               // s (3)
        $noHP,                // s (4)
        $id_sanctuary,        // i (5 - INT)
        $periode_masuk,       // i (6 - INT)
        $hashed_password,     // s (7 - KRITIS: Password)
        $default_role,        // s (8 - KRITIS: Role)
        $default_status,      // s (9)
        $otp_code,            // s (10)
        $otp_expires,          // s (11)
        $tanggal_lahir
    );

    if (mysqli_stmt_execute($stmt)) {
        
        // --- 6. LOGIKA PENGIRIMAN EMAIL OTP MENGGUNAKAN PHPMailer ---
        $mail = new PHPMailer(true);
        try {
            // Server settings (KREDENSIAL ASLI)
            $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER');
            $mail->Password = getenv('SMTP_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;

            // Recipients
            $mail->setFrom('mediterraneanofegypt@gmail.com', 'MOE Registration');
            $mail->addAddress($email, $username);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Kode Verifikasi Akun Mediterranean of Egypt (OTP)';
            $mail->Body = <<<EMAIL_BODY
                <html>
                <body style='font-family: Lato, sans-serif; background-color: #0d0d0d; color: #fff; padding: 20px; text-align: center;'>
                    <div style='max-width: 500px; margin: auto; padding: 20px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; border: 1px solid #DAA520;'>
                        <h2 style='color: #DAA520; font-family: Cinzel;'>Verifikasi Akun Nethera Anda</h2>
                        <p>Halo <strong>{$username}</strong>,</p>
                        <p>Berikut adalah kode 6 digit Anda:</p>
                        <h1 style='color: #DAA520; font-size: 3rem; letter-spacing: 5px; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 5px;'>
                            {$otp_code}
                        </h1>
                        <p>Kode ini berlaku selama 5 menit. Jangan bagikan kode ini kepada siapapun.</p>
                        <hr style='border-color: rgba(255, 255, 255, 0.2);'>
                    </div>
                </body>
                </html>
EMAIL_BODY;

            $mail->send();
            
            // Redirect ke halaman verifikasi OTP
            header("Location: verify_otp.php?user=" . urlencode($username));
            exit();

        } catch (Exception $e) {
            // Gagal Kirim Email: Hapus data yang baru dimasukkan (Fail-Safe)
            $last_id = mysqli_insert_id($conn);
            // 1. Siapkan statement DELETE
$stmt_del = mysqli_prepare($conn, "DELETE FROM nethera WHERE id_nethera = ?");

// 2. Bind parameter (id biasanya integer -> "i")
mysqli_stmt_bind_param($stmt_del, "i", $last_id);

// 3. Eksekusi
mysqli_stmt_execute($stmt_del);

// 4. Tutup
mysqli_stmt_close($stmt_del);
            error_log("PHPMailer Error: " . $mail->ErrorInfo); 
            
            header("Location: register.php?error=email_fail");
            exit();
        }

    } else {
        // Gagal Eksekusi INSERT SQL
        header("Location: register.php?error=db_error"); // Ganti die() dengan redirect yang benar
        exit();
    }
} else {
    // Error saat prepare query
    die("Error SQL: " . mysqli_error($conn));
}
?>