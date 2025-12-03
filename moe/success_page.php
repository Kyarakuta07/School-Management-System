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
    
    <style> 
        /* Gaya khusus untuk halaman sukses */
        :root { --gold: #DAA520; --dark-text: #1a1a1a; }
        .login-container { 
            max-width: 480px !important; 
            padding: 40px 30px !important;
            text-align: center;
        }
        .success-icon { 
            color: var(--gold); 
            font-size: 3.5rem; 
            margin-bottom: 20px; 
            text-shadow: 0 0 10px rgba(218, 165, 32, 0.7); 
        }
        .waiting-status { 
            background: rgba(184, 138, 27, 0.2); /* Emas transparan */
            border-left: 4px solid var(--gold);
            padding: 20px; 
            border-radius: 8px;
            margin-top: 25px;
            text-align: left;
        }
        .waiting-status h3 {
            color: #fff; 
            font-size: 1.1rem; 
            font-family: 'Cinzel', serif; 
            margin-bottom: 5px;
        }
        .waiting-status p {
            color: #aaa; 
            font-size: 0.9rem;
        }
    </style> 
</head>
<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        
        <i class="fa-solid fa-hourglass-half success-icon"></i>
        
        <h1 style="color: var(--gold); font-family: 'Cinzel', serif; font-size: 2rem; margin-bottom: 10px;">
            REGISTRASI SUKSES!
        </h1>
        <p class="subtitle" style="color: #ccc; margin-bottom: 0;">
            Selamat datang, **<?php echo htmlspecialchars($nickname); ?>**.
        </p>

        <div class="waiting-status">
            <h3 style="color: #fff; font-size: 1.1rem; margin-bottom: 5px;">
                <i class="fa-solid fa-user-clock"></i> AKUN SEDANG DITINJAU
            </h3>
            <p>
                Anda telah menyelesaikan verifikasi. Akun Anda telah berstatus **PENDING** dan sedang ditinjau oleh Vasiki (Admin).
            </p>
            <p style="color: var(--gold); font-size: 0.85rem; margin-top: 10px;">
                Anda akan menerima notifikasi setelah akun Anda diaktifkan.
            </p>
        </div>
        
        <a href="index.php" class="btn-login" style="margin-top: 3rem;">
            Kembali ke Halaman Login
        </a>
    </div>
</body>
</html>