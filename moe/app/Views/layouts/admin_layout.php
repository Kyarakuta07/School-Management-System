<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= esc($pageTitle ?? 'MOE Admin') ?> - MOE Admin
    </title>

    <!-- Icons -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Core Admin Styles -->
    <link rel="stylesheet" href="<?= base_url('css/admin/style.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/admin/cards.css') ?>" />

    <!-- Extra CSS (page-specific) -->
    <?php if (!empty($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= base_url('css/admin/' . $css) ?>" />
        <?php endforeach; ?>
    <?php endif; ?>

    <?= $this->renderSection('css') ?>
</head>

<body>
    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <!-- Admin Sidebar -->
    <?= $this->include('partials/admin/sidebar') ?>

    <main class="main-content">
        <?= $this->renderSection('content') ?>
    </main>

    <!-- Common Scripts -->
    <script src="<?= base_url('js/admin/sidebar-toggle.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>