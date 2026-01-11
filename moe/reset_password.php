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
        echo "<pre style='background:#333;color:#fff;padding:20px;position:fixed;top:0;left:0;z-index:9999;max-height:80vh;overflow:auto;'>";
        echo "Token received: " . htmlspecialchars($token) . "\n";
        echo "Token length: " . strlen($token) . "\n";
        echo "Token hex: " . bin2hex($token) . "\n\n";

        // Check if column exists
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM nethera LIKE 'reset_token'");
        echo "reset_token column exists: " . (mysqli_num_rows($check_col) > 0 ? 'YES' : 'NO') . "\n";

        // Check token in DB - exact match first
        $exact_stmt = mysqli_prepare($conn, "SELECT id_nethera FROM nethera WHERE reset_token = ?");
        mysqli_stmt_bind_param($exact_stmt, "s", $token);
        mysqli_stmt_execute($exact_stmt);
        $exact_result = mysqli_stmt_get_result($exact_stmt);
        echo "Exact token match: " . (mysqli_num_rows($exact_result) > 0 ? 'YES - ID: ' . mysqli_fetch_assoc($exact_result)['id_nethera'] : 'NO') . "\n";

        // Check token in DB (any match)
        $debug_stmt = mysqli_prepare($conn, "SELECT id_nethera, reset_token, token_expires FROM nethera WHERE reset_token IS NOT NULL ORDER BY token_expires DESC LIMIT 5");
        mysqli_stmt_execute($debug_stmt);
        $debug_result = mysqli_stmt_get_result($debug_stmt);
        echo "---\nTokens in DB:\n";
        while ($row = mysqli_fetch_assoc($debug_result)) {
            $match = ($row['reset_token'] === $token) ? ' ✓ MATCH!' : '';
            $expired = (strtotime($row['token_expires']) < time()) ? ' [EXPIRED]' : ' [VALID]';
            echo "ID: " . $row['id_nethera'] . $match . $expired . "\n";
            echo "  DB Token: " . $row['reset_token'] . "\n";
            echo "  Expires: " . $row['token_expires'] . "\n";
        }
        echo "Current time: " . date('Y-m-d H:i:s') . "\n";
        echo "MySQL NOW(): ";
        $now_result = mysqli_query($conn, "SELECT NOW() as now");
        echo mysqli_fetch_assoc($now_result)['now'] . "\n";
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
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Outfit:wght@300;400;600&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="assets/css/global.css" />
    <style>
        :root {
            --gold-primary: #FFD700;
            --gold-dark: #B8860B;
            --gold-dim: rgba(184, 134, 11, 0.3);
            --bg-dark: #050505;
            --glass-bg: rgba(20, 20, 20, 0.65);
            --glass-border: rgba(255, 215, 0, 0.3);
            --text-main: #e0e0e0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-dark);
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            perspective: 1000px;
            /* Enable 3D space */
        }

        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/landing/bg2.jpg') no-repeat center center/cover;
            /* Fallback */
            filter: brightness(0.3) blur(3px);
            z-index: -2;
            transform: scale(1.1);
        }

        /* Gold Dust Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: var(--gold-primary);
            border-radius: 50%;
            opacity: 0.3;
            animation: float-up linear infinite;
        }

        @keyframes float-up {
            from {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }

            50% {
                opacity: 0.6;
            }

            to {
                transform: translateY(-10vh) scale(1.5);
                opacity: 0;
            }
        }

        .login-container {
            max-width: 420px;
            width: 90%;
            background: linear-gradient(145deg, rgba(30, 30, 30, 0.8), rgba(10, 10, 10, 0.9));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            position: relative;
            z-index: 10;
            transform-style: preserve-3d;
            /* Key for 3D children */
            transform: translateZ(0);
            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.7),
                0 0 0 1px rgba(255, 215, 0, 0.1),
                inset 0 0 30px rgba(0, 0, 0, 0.5);
            transition: transform 0.1s ease-out;
            /* Smooth follow */
        }

        /* 3D Floating Logo */
        .login-logo {
            text-align: center;
            transform: translateZ(50px);
            /* Pop out 50px */
            margin-bottom: 1.5rem;
        }

        .login-logo img {
            height: 100px;
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.5));
            animation: logo-float 4s ease-in-out infinite;
        }

        @keyframes logo-float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        h1 {
            font-family: 'Cinzel', serif;
            background: linear-gradient(to bottom, #FFD700, #DAA520, #AA7700);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            text-align: center;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            transform: translateZ(30px);
            /* Pop out 30px */
            letter-spacing: 2px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 2.5rem;
            text-align: center;
            font-weight: 300;
            transform: translateZ(20px);
            /* Pop out 20px */
        }

        /* Inputs with 3D feel */
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
            transform: translateZ(20px);
        }

        /* Glass Input */
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 15px 15px 15px 50px;
            color: var(--gold-primary);
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.5);
        }

        .form-control:focus {
            background: rgba(0, 0, 0, 0.4);
            border-color: var(--gold-primary);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.3), inset 0 2px 5px rgba(0, 0, 0, 0.5);
            outline: none;
            transform: scale(1.02);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            transition: 0.3s;
            font-size: 1.1rem;
        }

        .form-control:focus+.input-icon {
            color: var(--gold-primary);
            text-shadow: 0 0 5px var(--gold-primary);
        }

        /* Requirements List */
        .password-requirements {
            font-size: 0.8rem;
            color: #888;
            margin: 0.5rem 0 2rem 0;
            padding: 1rem 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transform: translateZ(15px);
        }

        .password-requirements li {
            margin: 0.3rem 0;
            list-style: none;
            position: relative;
        }

        .password-requirements li::before {
            content: '❖';
            /* Diamond symbol */
            margin-right: 8px;
            color: var(--gold-dark);
            font-size: 0.7rem;
        }

        /* 3D Button */
        .btn-login {
            background: linear-gradient(135deg, #8B6508 0%, #FFD700 50%, #B8860B 100%);
            border: none;
            color: #1a1a1a;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 15px;
            border-radius: 12px;
            cursor: pointer;
            font-family: 'Cinzel', serif;
            width: 100%;
            transform: translateZ(40px);
            /* Highest pop */
            box-shadow:
                0 10px 20px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
            transform: skewX(-20deg);
            transition: 0.5s;
        }

        .btn-login:hover {
            transform: translateZ(50px) scale(1.05);
            /* Zoom eff */
            box-shadow:
                0 15px 30px rgba(255, 215, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: #666;
            text-decoration: none;
            transform: translateZ(20px);
            transition: 0.3s;
            width: 100%;
            text-align: center;
        }

        .back-link:hover {
            color: var(--gold-primary);
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            transform: translateZ(25px);
        }

        /* 3D Debug Panel */
        pre[style*="fixed"] {
            border: 1px solid var(--gold-dim);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.8);
            border-radius: 8px;
            font-family: 'Consolas', monospace;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>

    <div class="bg-image"></div>
    <div class="particles" id="particles"></div>

    <div class="login-container" id="tiltCard">
        <div class="login-logo"><img src="assets/landing/logo.png" alt="MOE Logo"></div>
        <h1>RESET PASSWORD</h1>
        <p class="subtitle">Secure your account with ancient magic</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error"
                style="color: #ff6b6b; background: rgba(255,0,0,0.1); padding: 10px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(255,0,0,0.2); transform: translateZ(20px);">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span style="margin-left:8px"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert-success"
                style="color: #2ecc71; background: rgba(0,255,0,0.1); padding: 10px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(0,255,0,0.2); transform: translateZ(20px);">
                <i class="fa-solid fa-check-circle"></i>
                <span style="margin-left:8px"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <a href="index.php" class="btn-login"
                style="display: block; text-align: center; text-decoration: none; margin-top: 1rem;">
                LOGIN NOW
            </a>
        <?php elseif ($valid_token): ?>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="input-group">
                    <input type="password" name="new_password" id="new_password" class="form-control"
                        placeholder="New Password" required minlength="8">
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                        placeholder="Confirm Password" required>
                    <i class="fa-solid fa-key input-icon"></i>
                </div>

                <ul class="password-requirements">
                    <li>Minimum 8 characters</li>
                    <li>At least one uppercase letter (A-Z)</li>
                    <li>At least one number (0-9)</li>
                </ul>

                <button type="submit" class="btn-login">
                    Set New Password
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; color: #888; margin: 2rem 0; transform: translateZ(20px);">
                <i class="fa-solid fa-link-slash"
                    style="font-size: 3rem; margin-bottom: 1rem; color: #444; text-shadow: 0 -1px 0 rgba(255,255,255,0.1);"></i>
                <p>This summon circle (link) has faded.</p>
            </div>
            <a href="auth/views/forgot_password.php" class="btn-login"
                style="display: block; text-align: center; text-decoration: none;">
                Request New Link
            </a>
        <?php endif; ?>

        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Login
        </a>
    </div>

    <script>
        // 3D Tilt Effect
        const card = document.getElementById('tiltCard');
        const container = document.body;

        container.addEventListener('mousemove', (e) => {
            const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });

        // Reset on mouse leave
        container.addEventListener('mouseleave', () => {
            card.style.transform = `rotateY(0deg) rotateX(0deg)`;
        });

        // Generate Particles
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 50; i++) {
            const p = document.createElement('div');
            p.classList.add('particle');
            p.style.left = Math.random() * 100 + 'vw';
            p.style.width = Math.random() * 4 + 'px';
            p.style.height = p.style.width;
            p.style.animationDuration = Math.random() * 5 + 5 + 's';
            p.style.animationDelay = Math.random() * 5 + 's';
            particlesContainer.appendChild(p);
        }
    </script>
</body>

</html>