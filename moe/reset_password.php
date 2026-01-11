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
    // DEBUG MODE - add &debug=1 to URL
    if (isset($_GET['debug'])) {
        echo "<pre style='background:#333;color:#fff;padding:20px;position:fixed;top:0;left:0;z-index:9999;'>";
        echo "Token received: " . htmlspecialchars(substr($token, 0, 30)) . "...\n";
        echo "Token length: " . strlen($token) . "\n";

        // Check if column exists
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM nethera LIKE 'reset_token'");
        echo "reset_token column exists: " . (mysqli_num_rows($check_col) > 0 ? 'YES' : 'NO') . "\n";

        // Check token in DB (any match)
        $debug_stmt = mysqli_prepare($conn, "SELECT id_nethera, reset_token, token_expires FROM nethera WHERE reset_token IS NOT NULL LIMIT 5");
        mysqli_stmt_execute($debug_stmt);
        $debug_result = mysqli_stmt_get_result($debug_stmt);
        echo "---\nTokens in DB:\n";
        while ($row = mysqli_fetch_assoc($debug_result)) {
            echo "ID: " . $row['id_nethera'] . " | Token: " . substr($row['reset_token'], 0, 20) . "... | Expires: " . $row['token_expires'] . "\n";
        }
        echo "Current time: " . date('Y-m-d H:i:s') . "\n";
        echo "</pre>";
    }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MOE</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="assets/css/global.css" />
    <style>
        .login-container {
            max-width: 450px !important;
        }

        .password-requirements {
            font-size: 0.85em;
            color: #888;
            margin-top: 0.5rem;
        }

        .password-requirements li {
            margin: 0.3rem 0;
        }
    </style>
</head>

<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-logo"><img src="assets/landing/logo.png" alt="MOE Logo"></div>
        <h1>RESET PASSWORD</h1>
        <p class="subtitle">Buat password baru untuk akun Anda</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert-success"
                style="background: rgba(39, 174, 96, 0.2); border: 1px solid #27ae60; color: #27ae60; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <i class="fa-solid fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <a href="index.php" class="btn-login"
                style="display: block; text-align: center; text-decoration: none; margin-top: 1rem;">
                <i class="fa-solid fa-sign-in-alt"></i> Login Sekarang
            </a>
        <?php elseif ($valid_token): ?>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="input-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password" name="new_password" id="new_password" class="form-control"
                        placeholder="Minimal 8 karakter" required minlength="8">
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                        placeholder="Ulangi password baru" required>
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>

                <ul class="password-requirements">
                    <li>Minimal 8 karakter</li>
                    <li>Mengandung huruf besar (A-Z)</li>
                    <li>Mengandung huruf kecil (a-z)</li>
                    <li>Mengandung angka (0-9)</li>
                </ul>

                <button type="submit" class="btn-login" style="margin-top: 1.5rem;">
                    <i class="fa-solid fa-key"></i> Reset Password
                </button>
            </form>
        <?php else: ?>
            <p style="text-align: center; color: #888; margin-top: 1rem;">
                Token tidak valid atau sudah kadaluarsa.
            </p>
            <a href="auth/views/forgot_password.php" class="btn-login"
                style="display: block; text-align: center; text-decoration: none; margin-top: 1rem;">
                <i class="fa-solid fa-redo"></i> Request Link Baru
            </a>
        <?php endif; ?>

        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
        </a>
    </div>
</body>

</html>