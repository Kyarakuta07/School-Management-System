<?php
/**
 * Reset Password Page
 * Mediterranean of Egypt - School Management System
 * 
 * Halaman untuk reset password setelah user klik link dari email
 */

require_once __DIR__ . '/core/security_config.php';
session_start();
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/config/connection.php';

// Ambil token dari URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error_message = '';
$success_message = '';
$valid_token = false;
$user_id = null;

// Validasi token
if (!empty($token)) {
    // Cek token di database
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_nethera, username, token_expires 
         FROM nethera 
         WHERE reset_token = ? AND token_expires > NOW()"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user_data) {
            $valid_token = true;
            $user_id = $user_data['id_nethera'];
            $_SESSION['reset_user_id'] = $user_id;
            $_SESSION['reset_token'] = $token;
        } else {
            $error_message = 'Token tidak valid atau sudah kadaluarsa. Silahkan request ulang.';
        }
    }
} else {
    $error_message = 'Token tidak ditemukan.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Validate CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Sesi tidak valid. Silahkan refresh halaman.';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate passwords
        if (strlen($new_password) < 8) {
            $error_message = 'Password minimal 8 karakter.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Password tidak cocok.';
        } elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $error_message = 'Password harus mengandung huruf besar, huruf kecil, dan angka.';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = mysqli_prepare(
                $conn,
                "UPDATE nethera SET password = ?, reset_token = NULL, token_expires = NULL WHERE id_nethera = ?"
            );

            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    $success_message = 'Password berhasil direset! Silahkan login dengan password baru.';
                    $valid_token = false; // Hide form after success

                    // Clear session
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_token']);
                } else {
                    error_log("Reset password update failed: " . mysqli_error($conn));
                    $error_message = 'Gagal mereset password. Silahkan coba lagi.';
                }
                mysqli_stmt_close($update_stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reset Password - MOE</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Outfit:wght@300;400;600&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="assets/css/global.css" />
    <style>
        :root {
            --gold-primary: #FFD700;
            --gold-dark: #B8860B;
            --bg-dark: #0a0a0a;
            --glass-bg: rgba(18, 18, 18, 0.95);
            --border-color: rgba(255, 215, 0, 0.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }

        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/landing/bg2.jpg') no-repeat center center/cover;
            filter: brightness(0.25) blur(4px);
            z-index: -1;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .login-logo img {
            height: 80px;
            filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.2));
        }

        h1 {
            font-family: 'Cinzel', serif;
            color: var(--gold-primary);
            font-size: 1.6rem;
            text-align: center;
            margin: 0 0 0.5rem 0;
            letter-spacing: 1px;
        }

        .subtitle {
            color: #888;
            font-size: 0.95rem;
            text-align: center;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.2rem;
        }

        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid #333;
            border-radius: 10px;
            padding: 14px 15px 14px 45px;
            color: #fff;
            font-size: 1rem;
            font-family: 'Outfit', sans-serif;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold-primary);
            background: rgba(255, 255, 255, 0.05);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            transition: 0.3s;
        }

        .form-control:focus+.input-icon {
            color: var(--gold-primary);
        }

        .password-requirements {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .password-requirements li {
            list-style: none;
            color: #888;
            font-size: 0.85rem;
            margin: 5px 0;
            padding-left: 1.2rem;
            position: relative;
        }

        .password-requirements li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--gold-dark);
            font-weight: bold;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(to right, #B8860B, #FFD700);
            border: none;
            padding: 14px;
            border-radius: 10px;
            color: #000;
            font-weight: 700;
            font-family: 'Cinzel', serif;
            font-size: 1rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .bg-overlay {
            display: none;
        }

        /* Remove heavy overlay */
        .particles {
            display: none;
        }

        /* Remove particles for performance */

        .alert-error,
        .alert-success {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.4;
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }

        /* Mobile Adjustments */
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
                background: #111;
                /* Solid bg on mobile for better text readability */
            }

            h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>
    <div class="bg-image"></div>

    <div class="login-container">
        <div class="login-logo"><img src="assets/landing/logo.png" alt="MOE Logo"></div>
        <h1>RESET PASSWORD</h1>
        <p class="subtitle">Buat password baru untuk akun Anda</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert-success">
                <i class="fa-solid fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <a href="index.php" class="btn-login"
                style="display: block; text-align: center; text-decoration: none; margin-top: 1rem;">
                LOGIN SEKARANG
            </a>
        <?php elseif ($valid_token): ?>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="input-group">
                    <input type="password" name="new_password" class="form-control" placeholder="Password Baru" required
                        minlength="8">
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Konfirmasi Password"
                        required>
                    <i class="fa-solid fa-key input-icon"></i>
                </div>

                <ul class="password-requirements">
                    <li>Minimal 8 karakter</li>
                    <li>Huruf besar (A-Z) & Angka (0-9)</li>
                </ul>

                <button type="submit" class="btn-login">
                    SIMPAN PASSWORD
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; color: #666; margin: 2rem 0;">
                <i class="fa-solid fa-link-slash" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>Link expired atau tidak valid.</p>
            </div>
            <a href="auth/views/forgot_password.php" class="btn-login"
                style="display: block; text-align: center; text-decoration: none;">
                REQUEST LINK BARU
            </a>
        <?php endif; ?>

        <a href="index.php" class="back-link">
            Rembali ke Login
        </a>
    </div>
</body>

</html>