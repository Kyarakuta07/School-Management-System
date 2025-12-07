<?php
// Wajib ada untuk cek sesi jika pengguna sudah login
require_once 'includes/security_config.php';
session_start();

// Cek apakah ada pesan dari URL
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';

// Jika pengguna sudah login, arahkan ke dashboard
if (isset($_SESSION['status_login']) && $_SESSION['status_login'] == 'berhasil') {
    if ($_SESSION['role'] == 'Vasiki') {
        header("Location: admin/index.php");
        exit();
    } else if ($_SESSION['role'] == 'Nethera') {
        header("Location: pengguna/beranda.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mediterranean Of Egypt</title>
    
    <!-- Preconnect hints for faster resource loading -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css" />
</head>

<body>

    <div id="notification-area" class="notification-container">
    </div>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-logo"><img src="assets/landing/logo.png" alt="MOE Logo"></div>
        <h1>MEDITERRANEAN OF EGYPT</h1>
        <p class="subtitle">ENTER YOUR CREDENTIALS</p>

        <form action="proses_login.php" method="POST">

            <div class="input-group">
                <label for="username">USERNAME</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required
                    autocomplete="off">
                <i class="fa-solid fa-user input-icon"></i>
            </div>

            <div class="input-group">
                <label for="password">PASSWORD</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                <i class="fa-solid fa-lock input-icon"></i>
            </div>

            <?php require_once 'includes/csrf.php'; echo csrf_token_field(); ?>

            <button type="submit" class="btn-login">LOGIN</button>

            <div class="footer-links">
                <a href="forgot_password.php">Forgot Password?</a>
                <a href="register.php">Create Account</a>
            </div>
        </form>

        <a href="home.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> BACK TO HOME
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pesan = "<?php echo htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?>";
            const notifArea = document.getElementById('notification-area');
            let type = '';
            let message = '';

            // --- LOGIC PESAN ---
            if (pesan === 'gagal') {
                type = 'toast-error';
                message = '<i class="fa-solid fa-times-circle"></i> Login Gagal! Periksa kembali username dan password Anda.';
            } else if (pesan === 'pending_approval') {
                type = 'toast-warning';
                message = '<i class="fa-solid fa-hourglass-half"></i> Akses Ditolak. Akun Anda belum diaktifkan oleh Vasiki atau masih Pending Verifikasi.';
            } else if (pesan === 'access_denied') {
                type = 'toast-warning';
                message = '<i class="fa-solid fa-shield-alt"></i> Akses Ditolak. Status akun Anda tidak aktif.';
            } else if (pesan === 'logout') {
                type = 'toast-success';
                message = '<i class="fa-solid fa-check-circle"></i> Anda berhasil keluar dari portal.';
            } else if (pesan === 'rate_limited') {
                type = 'toast-error';
                message = '<i class="fa-solid fa-ban"></i> Terlalu banyak percobaan login. Silakan tunggu beberapa menit.';
            }

            // --- TAMPILKAN TOAST ---
            if (message) {
                notifArea.innerHTML = `<div class="${type} toast-alert">${message}</div>`;
                notifArea.classList.add('show');

                // Sembunyikan setelah 5 detik
                setTimeout(() => {
                    notifArea.classList.remove('show');
                }, 5000);
            }
        });
    </script>
</body>

</html>