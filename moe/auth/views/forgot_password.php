<?php
require_once __DIR__ . '/../../core/security_config.php';
session_start();
require_once __DIR__ . '/../../core/csrf.php';
// File ini tidak memerlukan koneksi DB yang berat, hanya tampilan awal.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MOE</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/global.css" />
    <style>
        .login-container {
            max-width: 450px !important;
        }
    </style>
</head>

<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-logo"><img src="../../assets/landing/logo.png" alt="MOE Logo"></div>
        <h1>RESET PASSWORD</h1>
        <p class="subtitle">Masukkan alamat email terdaftar Anda</p>

        <?php
        if (isset($_GET['status'])): ?>
            <div class="alert-warning" style="background: rgba(184, 138, 27, 0.2); border-color: #DAA520; color: #DAA520;">
                <i class="fa-solid fa-info-circle"></i>
                <?php echo ($_GET['status'] == 'success') ? 'Tautan reset telah dikirim ke email Anda.' : 'Email tidak ditemukan di sistem.'; ?>
            </div>
        <?php endif; ?>

        <form action="../handlers/forgot_password.php" method="POST">

            <div class="input-group">
                <label for="email">Alamat Email</label>
                <input type="email" name="email" class="form-control" placeholder="Email terdaftar" required
                    autocomplete="email">
                <i class="fa-solid fa-envelope input-icon"></i>
            </div>

            <?php echo csrf_token_field(); ?>
            <button type="submit" class="btn-login" style="margin-top: 1.5rem;">Kirim Tautan Reset</button>
        </form>

        <a href="../../index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
        </a>
    </div>
</body>

</html>