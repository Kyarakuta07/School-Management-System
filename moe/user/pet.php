<?php
/**
 * MOE Pet System - Main Pet Page V2
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Complete Redesign - Premium Dark Egyptian Fantasy Theme
 * Mobile-First with Desktop Support
 * 
 * REFACTORED: Now uses modular component includes
 * SECURITY FIX: Added security_config
 */

require_once '../core/security_config.php';
session_start();
include '../config/connection.php';
require_once '../core/helpers.php';

// Authentication check - Allow Nethera, Vasiki, Anubis, and Hakaes
if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['Nethera', 'Vasiki', 'Anubis', 'Hakaes'])) {
    header("Location: ../index.php?pesan=gagal_akses");
    exit();
}

$user_id = $_SESSION['id_nethera'];
$nama_pengguna = htmlspecialchars($_SESSION['nama_lengkap']);

// Check for active punishment (only for Nethera role)
if ($_SESSION['role'] === 'Nethera') {
    $punishment = has_active_punishment($conn, $user_id);
    if ($punishment && is_feature_locked($conn, $user_id, 'pet')) {
        header("Location: punishment.php?locked=pet");
        exit();
    }
}

// Get user gold
$gold_stmt = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
mysqli_stmt_bind_param($gold_stmt, "i", $user_id);
mysqli_stmt_execute($gold_stmt);
$gold_result = mysqli_stmt_get_result($gold_stmt);
$user_gold = 0;
if ($gold_row = mysqli_fetch_assoc($gold_result)) {
    $user_gold = $gold_row['gold'] ?? 0;
}
mysqli_stmt_close($gold_stmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0a">
    <title>Pet Companion - MOE Virtual Academy</title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="Collect, evolve, and battle with your virtual pet companions in MOE. Features gacha system, arena battles, and gamified pet care.">
    <meta name="keywords" content="MOE pet, virtual pet, gacha, pet battle, pet evolution, school gamification">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Pet Companion - MOE">
    <meta property="og:description" content="Collect and battle with virtual pet companions!">
    <meta property="og:image" content="../assets/landing/logo.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/landing/logo.png">
    <link rel="apple-touch-icon" href="../assets/landing/logo.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles (with cache busting) -->
    <link rel="stylesheet" href="<?= asset('user/css/pet_v2.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/gacha_premium.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/gacha_result_modal.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/my_pet_premium.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/collection_premium.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/shop_premium.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/arena_premium.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/achievements_premium.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/daily_login.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/sanctuary_war.css', '../') ?>">
    <link rel="stylesheet" href="<?= asset('user/css/leaderboard.css', '../') ?>">

    <!-- PixiJS for particles -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.3.2/pixi.min.js"></script>

    <!-- Spline 3D Viewer -->
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.0.54/build/spline-viewer.js"></script>
</head>

<body class="pet-page">
    <!-- 3D Background Layer (Spline) -->
    <div class="spline-bg-container" id="spline-bg"></div>

    <!-- Background -->
    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <!-- PixiJS Container -->
    <div id="pixi-bg-container"></div>

    <!-- App Container -->
    <div class="app-container">
        <?php include 'components/pet/_header.php'; ?>
        <?php include 'components/pet/_tab_nav.php'; ?>

        <main class="main-content">
            <?php include 'components/pet/tabs/_my_pet.php'; ?>
            <?php include 'components/pet/tabs/_collection.php'; ?>
            <?php include 'components/pet/tabs/_gacha.php'; ?>
            <?php include 'components/pet/tabs/_shop.php'; ?>
            <?php include 'components/pet/tabs/_arena.php'; ?>
            <?php include 'components/pet/tabs/_arena_3v3.php'; ?>
            <?php include 'components/pet/tabs/_sanctuary_war.php'; ?>
            <?php include 'components/pet/tabs/_leaderboard.php'; ?>
            <?php include 'components/pet/tabs/_achievements.php'; ?>
        </main>
    </div>

    <?php include 'components/pet/_bottom_nav.php'; ?>

    <!-- MODALS -->
    <?php include 'components/pet/modals/_gacha_result.php'; ?>
    <?php include 'components/pet/modals/_pet_modals.php'; ?>
    <?php include 'components/pet/modals/_evolution.php'; ?>
    <?php include 'components/pet/modals/_daily_login.php'; ?>
    <?php include 'components/pet/modals/_shop_modals.php'; ?>
    <?php include 'components/pet/modals/_confirm.php'; ?>

    <!-- Inline Styles -->
    <?php include 'components/pet/_inline_styles.php'; ?>

    <!-- Scripts -->
    <?php include 'components/pet/_scripts.php'; ?>
</body>

</html>