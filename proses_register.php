<?php
session_start();
include 'connection.php'; 

// --- GANTI PATH INI AGAR SESUAI DENGAN STRUKTUR BARU ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php'; // PATH BARU
require 'phpmailer/src/PHPMailer.php'; // PATH BARU
require 'phpmailer/src/SMTP.php'; // PATH BARU

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

// 1. Ambil dan bersihkan data
$nama_lengkap = trim($_POST['nama_lengkap']);
$nickname = trim($_POST['nickname']);
$email = trim($_POST['email']); 
$noHP = trim($_POST['noHP']);
$password_input = $_POST['password'];
$id_sanctuary = (int)$_POST['id_sanctuary'];
$periode_masuk = (int)$_POST['periode_masuk'];

// --- 2. SECURITY: HASHING PASSWORD ---
$hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

// --- 3. STATUS & OTP GENERATION ---
$default_role = 'Nethera';
$default_status = 'Pending'; 
$otp_code = rand(100000, 999999); 
$otp_expires = date("Y-m-d H:i:s", time() + 300); // 5 menit

// 4. SQL INSERT (TAMBAHKAN KOLOM EMAIL)
// Perhatian: Jumlah 's' harus disesuaikan dengan jumlah kolom string (s,s,s,i,i,s,s,s,s,s)
$sql = "INSERT INTO nethera 
        (nama_lengkap, nickname, email, noHP, id_sanctuary, periode_masuk, password, role, status_akun, otp_code, otp_expires) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
// Bind Parameter: s (nama), s (nick), s (email), s (noHP), i (sanct), i (periode), s (pass), s (role), s (status), s (otp), s (expires)
    mysqli_stmt_bind_param($stmt, "sssiississs", // <-- Diperbarui!
        $nama_lengkap,
        $nickname,
        $email,
        $noHP, // <-- VARIABEL BARU
        $id_sanctuary,
        $periode_masuk,
        $hashed_password, 
        $default_role,
        $default_status,
        $otp_code, 
        $otp_expires
    );

    if (mysqli_stmt_execute($stmt)) {
        
        // --- 5. LOGIKA PENGIRIMAN EMAIL OTP MENGGUNAKAN PHPMailer ---
        $mail = new PHPMailer(true);
        try {
            // GANTI DENGAN SETTING SERVER EMAIL ANDA
            $mail->isSMTP();                                            
            $mail->Host       = 'smtp.gmail.com';    // CONTOH: Untuk Gmail
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = 'mediterraneanofegypt@gmail.com';  // GANTI: Alamat Email Pengirim
            $mail->Password   = 'pdyn gyem ljzk odcc';   // GANTI: Password Aplikasi/Email
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
            $mail->Port       = 465;                                    

            // Recipients
            $mail->setFrom('mediterraneanofegypt@gmail.com', 'MOE Registration');
            $mail->addAddress($email, $nickname);     

            // Content
            $mail->isHTML(true);                                  
            $mail->Subject = 'Kode Verifikasi Akun Mediterranean of Egypt (OTP)';
            $mail->Body    = "
                <html>
                <body style='font-family: Lato, sans-serif; background-color: #0d0d0d; color: #fff; padding: 20px; text-align: center;'>
                    <div style='max-width: 500px; margin: auto; padding: 20px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; border: 1px solid #DAA520;'>
                        <h2 style='color: #DAA520; font-family: Cinzel;'>Verifikasi Akun Nethera Anda</h2>
                        <p>Halo <strong>{$nickname}</strong>,</p>
                        <p>Berikut adalah kode 6 digit Anda:</p>
                        <h1 style='color: #DAA520; font-size: 3rem; letter-spacing: 5px; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 5px;'>
                            {$otp_code}
                        </h1>
                        <p>Kode ini berlaku selama 5 menit. Jangan bagikan kode ini kepada siapapun.</p>
                        <hr style='border-color: rgba(255, 255, 255, 0.2);'>
                    </div>
                </body>
                </html>
            ";
            
            $mail->send();
            
            // Redirect ke halaman verifikasi OTP (Tahap 2)
            header("Location: verify_otp.php?user=" . urlencode($nickname));
            exit();

        } catch (Exception $e) {
            // Gagal Kirim Email: Hapus data yang baru dimasukkan 
            $last_id = mysqli_insert_id($conn);
            mysqli_query($conn, "DELETE FROM nethera WHERE id_nethera = $last_id");
            error_log("PHPMailer Error: " . $mail->ErrorInfo); // Log error
            
            header("Location: register.php?error=email_fail");
            exit();
        }

    } else {
        header("Location: register.php?error=db_error");
        exit();
    }
} else {
    die("Error SQL: " . mysqli_error($conn));
}
?>