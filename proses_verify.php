<?php
session_start();
include 'connection.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$nickname = $_POST['nickname'];
$otp_code = $_POST['otp_code'];
$current_time = date("Y-m-d H:i:s");

// 1. Ambil data user, OTP, dan waktu kadaluarsa dari database
$sql_fetch = "SELECT otp_code, otp_expires FROM nethera WHERE nickname = ? AND status_akun = 'Pending'";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);

if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "s", $nickname);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    $user_otp_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_fetch);

    // 2. Cek apakah data ditemukan dan kode cocok
    if (!$user_otp_data || $user_otp_data['otp_code'] !== $otp_code) {
        // Data tidak ditemukan atau kode salah
        header("Location: verify_otp.php?user=" . urlencode($nickname) . "&status=invalid");
        exit();
    }

    // 3. Cek apakah kode sudah kadaluarsa (waktu di DB < Waktu Sekarang)
    if ($user_otp_data['otp_expires'] < $current_time) {
        // Hapus data user karena kode kadaluarsa dan tidak bisa digunakan lagi
        $sql_delete = "DELETE FROM nethera WHERE nickname = ? AND status_akun = 'Pending'";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "s", $nickname);
        mysqli_stmt_execute($stmt_delete);
        
        header("Location: register.php?error=expired"); // Kirim ke register dengan error expired
        exit();
    }

    // 4. Verifikasi Berhasil: Update status dan hapus kode OTP
    $sql_update = "UPDATE nethera SET otp_code = NULL, otp_expires = NULL 
                    WHERE nickname = ? AND status_akun = 'Pending'";
    
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "s", $nickname);
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Redirect ke halaman sukses/tunggu persetujuan admin
        header("Location: success_page.php?nickname=" . urlencode($nickname));
        exit();
    } else {
        header("Location: verify_otp.php?user=" . urlencode($nickname) . "&status=db_error");
        exit();
    }

} else {
    die("Error preparing statement.");
}
?>