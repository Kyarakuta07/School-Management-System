<?php
require_once __DIR__ . '/../../core/security_config.php';
session_start();
// Wajib ada untuk cek database
require_once __DIR__ . '/../../config/connection.php';

// File ini dipanggil dari proses_register.php dengan parameter user
$username = isset($_GET['user']) ? $_GET['user'] : '';

// Logic untuk menampilkan pesan status (misal: kode salah/kadaluarsa)
$status_message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'invalid') {
        $status_message = 'Kode OTP salah atau tidak cocok. Mohon coba lagi.';
    } else if ($_GET['status'] == 'expired') {
        $status_message = 'Kode OTP sudah kadaluarsa. Silakan daftar ulang.';
    } else if ($_GET['status'] == 'db_error') {
        $status_message = 'Terjadi kesalahan saat memproses data. Coba lagi.';
    }
}

// ===============================================
// === LOGIC BARU: PROTEKSI DAN CHECK KELAYAKAN ===
// ===============================================

// 1. Cek apakah user ada dan masih memiliki kode OTP yang harus diverifikasi
if (empty($username)) {
    // Jika tidak ada username di URL, redirect ke halaman daftar
    header("Location: register.php");
    exit();
}

$sql_check = "SELECT otp_code FROM nethera WHERE username = ? AND otp_code IS NOT NULL";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $username);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) == 0) {
    // Jika user tidak ditemukan ATAU kolom OTP-nya sudah NULL (berarti sudah diverifikasi)
    // Langsung arahkan ke halaman tunggu persetujuan
    header("Location: success_page.php?username=" . urlencode($username));
    exit();
}

mysqli_stmt_close($stmt_check);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - MOE</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css" />
</head>

<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-logo"><img src="assets/landing/logo.png" alt="MOE Logo"></div>
        <h1>VERIFIKASI AKUN</h1>
        <p class="subtitle">Kode 6 digit telah dikirim ke email Anda.</p>

        <?php if (!empty($status_message)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <form action="../handlers/verify_otp.php" method="POST">

            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">

            <div class="input-group">
                <label for="otp">Kode Verifikasi (OTP)</label>
                <input type="text" name="otp_code" pattern="[0-9]{6}" inputmode="numeric" maxlength="6"
                    class="form-control" placeholder="Masukkan 6 digit kode" required autocomplete="off">>
                <i class="fa-solid fa-key input-icon"></i>
            </div>

            <?php require_once __DIR__ . '/../../core/csrf.php';
            echo csrf_token_field(); ?>

            <button type="submit" class="btn-login" style="margin-top: 1.5rem;">Konfirmasi & Selesaikan</button>
        </form>

        <div style="margin-top: 1rem; font-size: 0.9rem;">
            <a href="resend_otp.php?user=<?php echo urlencode($username); ?>"
                style="color: #DAA520; text-decoration: none;">
                Kirim Ulang Kode OTP
            </a>
            <span style="color: #555; margin: 0 10px;">|</span>
            <a href="register.php" class="back-link">
                Daftar Ulang
            </a>
        </div>
    </div>
</body>

</html>