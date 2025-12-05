<?php
session_start();
include 'connection.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$username = trim($_POST['username']); // Tambahkan trim()
$otp_code = trim($_POST['otp_code']); // Tambahkan trim()
$current_time = date("Y-m-d H:i:s");

// 1. Ambil data user, OTP, dan waktu kadaluarsa dari database
$sql_fetch = "SELECT otp_code, otp_expires FROM nethera WHERE username = ? AND status_akun = 'Pending'";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);

if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "s", $username);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    $user_otp_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_fetch);

    // 2. Cek apakah data ditemukan dan kode cocok
    // Gunakan trim() pada data DB dan input untuk perbandingan yang ketat
    if (!$user_otp_data || trim($user_otp_data['otp_code']) !== $otp_code) {
        // Data tidak ditemukan atau kode salah
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=invalid");
        exit();
    }

// 3. Cek apakah kode sudah kadaluarsa (waktu di DB < Waktu Sekarang)
    if ($user_otp_data['otp_expires'] < $current_time) {
        
        // --- LOGIKA RESEND (JANGAN HAPUS AKUN) ---
        
        // a. Generate kode baru
        $new_otp_code = rand(100000, 999999);
        $new_expiry_time = date("Y-m-d H:i:s", time() + 300); // 5 menit baru

        // b. Update database dengan kode dan waktu baru
        $sql_resend = "UPDATE nethera SET otp_code = ?, otp_expires = ? 
                       WHERE username = ?";
        $stmt_resend = mysqli_prepare($conn, $sql_resend);
        
        if ($stmt_resend) {
            mysqli_stmt_bind_param($stmt_resend, "sss", $new_otp_code, $new_expiry_time, $username);
            mysqli_stmt_execute($stmt_resend);
            
            // ** Di sini harus ada LOGIKA PENGIRIMAN EMAIL BARU **
            // (Anda harus memanggil PHPMailer lagi dengan $new_otp_code)
        }
        
        // Redirect kembali ke verify page dengan pesan "Kode baru sudah dikirim"
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=resend_success"); 
        exit();
    }

    // 4. Verifikasi Berhasil: Update status dan hapus kode OTP
    $sql_update = "UPDATE nethera SET otp_code = NULL, otp_expires = NULL 
                    WHERE username = ? AND status_akun = 'Pending'";
    
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "s", $username);
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Redirect ke halaman sukses/tunggu persetujuan admin
        header("Location: success_page.php?username=" . urlencode($username));
        exit();
    } else {
        // Error saat eksekusi UPDATE
        header("Location: verify_otp.php?user=" . urlencode($username) . "&status=db_error");
        exit();
    }

} else {
    // Error saat prepare statement fetch data
    die("Error preparing statement.");
}
?>