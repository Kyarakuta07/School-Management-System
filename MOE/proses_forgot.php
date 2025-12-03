<?php
session_start();
include 'connection.php'; 

// Include PHPMailer files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PASTIKAN PATH INI BENAR DARI ROOT
require 'phpmailer/src/Exception.php'; 
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot_password.php");
    exit();
}

$user_email = trim($_POST['email']);
$token = bin2hex(random_bytes(32)); // Generate token 64 karakter (aman)
$expiry_time = date("Y-m-d H:i:s", time() + 1800); // Token berlaku 30 menit
$reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token; // GANTI /reset_password.php JIKA PATH BEDA!

// --- 1. CEK APAKAH EMAIL TERDAFTAR & AMBIL DATA USER ---
$sql_check = "SELECT id_nethera, nickname FROM nethera WHERE email = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);

if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "s", $user_email);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    $user_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_check);

    if (!$user_data) {
        // Email tidak ditemukan, redirect dengan pesan gagal
        header("Location: forgot_password.php?status=fail");
        exit();
    }
    
    $user_id = $user_data['id_nethera'];
    $nickname = $user_data['nickname'];


    // --- 2. UPDATE DATABASE DENGAN TOKEN RESET BARU ---
    $sql_update = "UPDATE nethera SET reset_token = ?, token_expires = ? WHERE id_nethera = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);

    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "ssi", $token, $expiry_time, $user_id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            
            // --- 3. KIRIM EMAIL RESET ---
            $mail = new PHPMailer(true);
            try {
                // Server settings (Gunakan kredensial Gmail yang sudah Anda setup)
                $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;                                   
                $mail->Username = 'mediterraneanofegypt@gmail.com'; $mail->Password = 'pdyn gyem ljzk odcc';   
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;                                    

                // Recipients
                $mail->setFrom('mediterraneanofegypt@gmail.com', 'MOE Password Reset');
                $mail->addAddress($user_email, $nickname);     

                // Content
                $mail->isHTML(true);                                  
                $mail->Subject = 'Permintaan Reset Password Akun MOE';
                $mail->Body    = "
                    <html>
                    <body style='font-family: Lato, sans-serif; background-color: #0d0d0d; color: #fff; padding: 20px;'>
                        <div style='max-width: 500px; margin: auto; padding: 20px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; border: 1px solid #DAA520;'>
                            <h2 style='color: #DAA520; font-family: Cinzel;'>Reset Password</h2>
                            <p>Halo <strong>{$nickname}</strong>,</p>
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
                
                // 4. Sukses: Redirect ke halaman forgot password dengan status sukses
                header("Location: forgot_password.php?status=success");
                exit();

            } catch (Exception $e) {
                // Gagal Kirim Email
                error_log("PHPMailer Reset Error: " . $mail->ErrorInfo); 
                header("Location: forgot_password.php?status=email_fail");
                exit();
            }

        } else {
            die("Error updating token: " . mysqli_error($conn));
        }

    } else {
        die("Error preparing statement: " . mysqli_error($conn));
    }
}
?>