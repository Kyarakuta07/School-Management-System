<?php // My Sanctuary â€” CI4 View ?>
<?php
// Helper function for safe avatar URL within view
$getSafeAvatar = function ($photo) {
    if (empty($photo))
        return '';
    $safe = basename($photo);
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $safe))
        return '';
    return base_url('assets/uploads/profiles/' . $safe);
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sanctuary -
        <?= esc($sanctuaryName) ?> | MOE
    </title>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="<?= asset_v('css/shared/global.css') ?>">
    <link rel="stylesheet" href="<?= asset_v('css/user/beranda_style.css') ?>">

    <link rel="stylesheet" href="<?= asset_v('css/sanctuary/sanctuary.css') ?>">
</head>

<body>
    <div class="sanctuary-wrapper">
        <!-- HEADER -->
        <?= view('App\Modules\Sanctuary\Views\partials\_header') ?>

        <!-- DASHBOARD GRID -->
        <div class="dashboard-grid">

            <!-- ACTION MESSAGE -->
            <?php if ($actionMessage): ?>
                <div class="control-card full-width"
                    style="background: <?= $actionSuccess ? 'rgba(50,180,50,0.2)' : 'rgba(180,50,50,0.2)' ?>; border-color: <?= $actionSuccess ? '#4a4' : '#a44' ?>;">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <i class="fas <?= $actionSuccess ? 'fa-check-circle' : 'fa-times-circle' ?>"
                            style="color: <?= $actionSuccess ? '#4a4' : '#a44' ?>; font-size: 1.5rem;"></i>
                        <span style="margin-left: 10px;">
                            <?= esc($actionMessage) ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TREASURY -->
            <?= view('App\Modules\Sanctuary\Views\partials\_treasury') ?>

            <!-- UPGRADES -->
            <?= view('App\Modules\Sanctuary\Views\partials\_upgrades') ?>

            <!-- MEMBERS & LEADERSHIP -->
            <?= view('App\Modules\Sanctuary\Views\partials\_members') ?>

            <!-- DAILY REWARD -->
            <?= view('App\Modules\Sanctuary\Views\partials\_daily_reward') ?>
        </div>

        <!-- BACK NAVIGATION -->
        <div class="back-nav">
            <a href="<?= base_url('beranda') ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to
                Dashboard</a>
        </div>
    </div>

    <script src="<?= asset_v('js/sanctuary/sanctuary.js') ?>"></script>
</body>

</html>