<?php
require_once __DIR__ . '/../../core/helpers.php';
session_start();
// Halaman ini tidak perlu koneksi DB, hanya view

$username = isset($_GET['username']) ? $_GET['username'] : 'Anggota Baru';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Success - MOE</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../../assets/landing/logo.png">
    <link rel="shortcut icon" type="image/png" href="../../assets/landing/logo.png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="<?= asset('assets/css/global.css', '../../') ?>" />

    <style>
        /* Gaya khusus untuk halaman sukses - Minimalist */
        body {
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }

        .login-container {
            max-width: 450px !important;
            padding: clamp(2rem, 5vh, 3.5rem) clamp(1.5rem, 5vw, 2rem) !important;
            text-align: center;
            border-bottom: 3px solid var(--gold);
        }

        .success-icon-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            box-shadow: 0 0 20px rgba(218, 165, 32, 0.4);
        }

        .success-icon {
            color: var(--gold);
            font-size: 2.5rem;
        }

        .success-title {
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .success-message {
            color: #ccc;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn-home {
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
            padding: 10px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            display: inline-block;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .btn-home:hover {
            background: var(--gold);
            color: #000;
            box-shadow: 0 0 15px rgba(218, 165, 32, 0.6);
        }
    </style>
</head>

<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">

        <div class="success-icon-container">
            <i class="fa-solid fa-check success-icon"></i>
        </div>

        <h1 class="success-title">SUCCESS</h1>

        <div class="success-message">
            <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>.</p>
            <p style="font-size: 0.9rem; color: #888; margin-top: 5px;">Your account is pending approval by Vasiki.</p>
        </div>

        <a href="../../index.php" class="btn-home">
            <i class="fa-solid fa-right-to-bracket"></i> Back to Login
        </a>
    </div>
</body>

</html>