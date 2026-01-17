<?php
/**
 * Admin Head Component
 * Common meta tags, fonts, and CSS includes
 * 
 * Required variable: $pageTitle (string) - page title
 * Optional variable: $extraCss (array) - additional CSS files to include
 */
require_once dirname(__DIR__, 2) . '/core/helpers.php';
$pageTitle = $pageTitle ?? 'MOE Admin';
$extraCss = $extraCss ?? [];
?>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - MOE Admin</title>

    <!-- Icons -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Core Styles (with cache busting) -->
    <link rel="stylesheet" href="<?= asset('admin/css/style.css', $cssPath ?? '') ?>" />
    <link rel="stylesheet" href="<?= asset('admin/css/cards.css', $cssPath ?? '') ?>" />

    <!-- Extra CSS -->
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= asset('admin/' . $css, $cssPath ?? '') ?>" />
    <?php endforeach; ?>
</head>