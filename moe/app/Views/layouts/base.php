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
    <link rel="icon" type="image/png" href="<?= base_url('assets/landing/logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/landing/logo.png') ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <?= csrf_meta() ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Layout-specific head content (CSS, extra icons, etc.) -->
    <?= $this->renderSection('head') ?>
</head>

<body class="<?= $bodyClass ?? '' ?>">
    <!-- Background -->
    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <!-- Global API Base URL for JavaScript -->
    <script>
        const API_BASE = '<?= base_url('api/') ?>';
        const ASSET_BASE = '<?= base_url() ?>';
        // Expose to window for ES modules (const does NOT attach to window)
        window.API_BASE = API_BASE;
        window.ASSET_BASE = ASSET_BASE;
    </script>

    <!-- CSRF Helper for fetch() requests -->
    <script src="<?= base_url('js/shared/csrf_helper.js') ?>"></script>

    <!-- Flash Message Alerts -->
    <?= $this->include('partials/common/alerts') ?>

    <!-- Main Content Area (defined by child layouts) -->
    <?= $this->renderSection('body') ?>

    <!-- Toast Notification System (extracted to external file) -->
    <script src="<?= base_url('js/shared/toast.js') ?>"></script>

    <!-- Layout-specific scripts -->
    <?= $this->renderSection('scripts') ?>
</body>

</html>