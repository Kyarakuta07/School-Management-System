<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0a">
    <title>
        <?= esc($pageTitle ?? 'MOE - Mediterranean of Egypt') ?>
    </title>

    <!-- SEO -->
    <meta name="description"
        content="<?= esc($pageDescription ?? 'Mediterranean Of Egypt School Management System') ?>">
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= asset_v('assets/landing/logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset_v('assets/landing/logo.png') ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <?= csrf_meta() ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?php // Layout-specific head content ?>
    <?= $this->renderSection('head') ?>
</head>

<body class="<?= $bodyClass ?? '' ?>">
    <!-- Background -->
    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <?php // Global JS constants ?>
    <script>
        const API_BASE = '<?= base_url('api/') ?>';
        const ASSET_BASE = '<?= base_url() ?>';
        // Expose to window for ES modules (const does NOT attach to window)
        window.API_BASE = API_BASE;
        window.ASSET_BASE = ASSET_BASE;
    </script>

    <?php // CSRF Helper ?>
    <script src="<?= asset_v('js/shared/csrf_helper.js') ?>"></script>

    <?php // Flash alerts ?>
    <?= $this->include('partials/common/alerts') ?>

    <?php // Main content ?>
    <?= $this->renderSection('body') ?>

    <?php // Toast system ?>
    <script src="<?= asset_v('js/shared/toast.js') ?>"></script>

    <?php // Scripts ?>
    <?= $this->renderSection('scripts') ?>
</body>

</html>