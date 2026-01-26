<?php
// Pastikan koneksi database tersedia untuk mengambil daftar sanctuary
require_once __DIR__ . '/../../core/security_config.php';
require_once __DIR__ . '/../../core/helpers.php';
session_start(); // PENTING: Session harus aktif untuk CSRF token
require_once __DIR__ . '/../../config/connection.php';

// Logic untuk menampilkan pesan error jika ada redirect dari proses_register.php
$error_message = '';
$alert_class = 'alert-error';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'duplicate_entry':
            $error_message = 'Username, Email, atau No HP sudah terdaftar!';
            break;
        case 'password_weak':
            $error_message = 'Password harus min. 8 karakter (huruf besar, kecil, angka)';
            break;
        case 'registration_failed':
            $error_message = 'Registrasi gagal. Coba lagi!';
            break;
        case 'email_fail':
        case 'email_failed':
            $error_message = 'Email OTP gagal terkirim. Hubungi admin.';
            break;
        case 'csrf_failed':
            $error_message = 'Form tidak valid. Refresh halaman (F5)!';
            break;
        case 'rate_limited':
            $error_message = 'Terlalu banyak percobaan. Tunggu 1 jam!';
            break;
        case 'invalid_email':
            $error_message = 'Format email salah!';
            break;
        case 'invalid_phone':
            $error_message = 'No HP harus 10-15 digit angka!';
            break;
        case 'db_error':
            $error_message = 'Error database. Coba lagi!';
            break;
        case 'expired':
            $error_message = 'OTP kadaluarsa. Daftar ulang!';
            break;
        default:
            $error_message = 'Terjadi kesalahan. Coba lagi!';
    }
    $alert_class = 'alert-error';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mediterranean Of Egypt</title>

    <!-- Preconnect hints for faster resource loading -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="<?= asset('assets/css/global.css', '../../') ?>" />
    <style>
        /* Sedikit pelebaran agar form banyak inputnya tetap nyaman di desktop */
        .login-container {
            max-width: 500px !important;
        }
    </style>
</head>

<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-logo"><img src="../../assets/landing/logo.png" alt="MOE Logo"></div>
        <h1>REGISTRASI ANGGOTA BARU</h1>
        <p class="subtitle">Join the Guardians of the Mediterranean</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="../handlers/register.php" method="POST">

            <div class="input-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap" required
                    autocomplete="off">
                <i class="fa-solid fa-signature input-icon"></i>
            </div>

            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username yang unik" required
                    autocomplete="off">
                <i class="fa-solid fa-user-tag input-icon"></i>
            </div>

            <div class="input-group">
                <label for="email">Alamat Email</label>
                <input type="email" name="email" class="form-control" placeholder="Email aktif untuk verifikasi"
                    required autocomplete="email">
                <i class="fa-solid fa-envelope input-icon"></i>
            </div>

            <div class="input-group">
                <label for="noHP">Nomor HP/WA</label>
                <input type="text" name="noHP" class="form-control" placeholder="Nomor kontak aktif" required
                    autocomplete="tel">
                <i class="fa-solid fa-phone input-icon"></i>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="Password yang aman" required autocomplete="new-password">
                <i class="fa-solid fa-lock input-icon"></i>
                <!-- Password Strength Indicator -->
                <div id="password-strength" style="margin-top: 0.5rem; display: none;">
                    <div
                        style="height: 4px; background: #2a2a2a; border-radius: 2px; overflow: hidden; margin-bottom: 0.3rem;">
                        <div id="strength-bar"
                            style="height: 100%; width: 0%; transition: all 0.3s; background: #ff4444;"></div>
                    </div>
                    <small id="strength-text" style="color: #ff4444; font-size: 0.85rem;">Weak</small>
                </div>
            </div>

            <div class="input-group">
                <label for="tanggal_lahir">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control" required>
                <i class="fa-solid fa-calendar-day input-icon"></i>
            </div>

            <div class="input-group">
                <label for="periode_masuk">Periode Masuk</label>
                <input type="number" name="periode_masuk" class="form-control" value="1" required min="1">
                <i class="fa-solid fa-calendar-alt input-icon"></i>
            </div>

            <?php require_once __DIR__ . '/../../core/csrf.php';
            echo csrf_token_field(); ?>

            <button type="submit" class="btn-login" style="margin-top: 1.5rem;">Daftar & Verifikasi Email</button>
        </form>

        <a href="../../index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
        </a>
    </div>

    <script>
        // Password Strength Indicator
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.getElementById('password-strength');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');

        passwordInput.addEventListener('input', function () {
            const password = this.value;

            if (password.length === 0) {
                strengthIndicator.style.display = 'none';
                return;
            }

            strengthIndicator.style.display = 'block';

            let strength = 0;
            const checks = {
                length8: password.length >= 8,
                length12: password.length >= 12,
                mixed: /[a-z]/.test(password) && /[A-Z]/.test(password),
                number: /\d/.test(password),
                special: /[^a-zA-Z\d]/.test(password)
            };

            if (checks.length8) strength++;
            if (checks.length12) strength++;
            if (checks.mixed) strength++;
            if (checks.number) strength++;
            if (checks.special) strength++;

            const levels = [
                { label: 'Very Weak', color: '#ff4444', width: '20%' },
                { label: 'Weak', color: '#ff8800', width: '40%' },
                { label: 'Fair', color: '#ffbb33', width: '60%' },
                { label: 'Good', color: '#00c851', width: '80%' },
                { label: 'Strong', color: '#00c851', width: '90%' },
                { label: 'Very Strong', color: '#007e33', width: '100%' }
            ];

            const level = levels[strength] || levels[0];
            strengthBar.style.width = level.width;
            strengthBar.style.background = level.color;
            strengthText.textContent = level.label;
            strengthText.style.color = level.color;
        });
    </script>
</body>

</html>