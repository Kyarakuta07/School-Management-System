<?php
session_start();
// Halaman ini tidak perlu koneksi DB, hanya view

$nickname = isset($_GET['nickname']) ? $_GET['nickname'] : 'Anggota Baru';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil - MOE</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css" /> 
    <style> .login-container { max-width: 450px !important; } </style> 
</head>
<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-logo"><img src="assets/landing/logo.png" alt="MOE Logo"></div>
        
        <h1 style="color: #32cd32;">VERIFIKASI SUKSES!</h1>
        <p class="subtitle" style="margin-top: 1.5rem; margin-bottom: 2rem;">
            Selamat datang, <?php echo htmlspecialchars($nickname); ?>.
        </p>

        <div class="alert-warning">
            <i class="fa-solid fa-hourglass-half"></i> 
            Pendaftaran Anda berhasil. Akun Anda telah berstatus **PENDING** dan sedang ditinjau oleh Vasiki (Admin).
        </div>
        
        <p style="color: #aaa; margin-top: 1rem;">
            Anda akan menerima notifikasi setelah akun Anda diaktifkan.
        </p>

        <a href="index.php" class="btn-login" style="margin-top: 2rem; background: #555; border: 1px solid #777;">
            Kembali ke Halaman Utama
        </a>
    </div>
</body>
</html>