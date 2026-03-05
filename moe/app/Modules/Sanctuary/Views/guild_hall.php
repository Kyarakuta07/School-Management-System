<?= $this->extend('layouts/user') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= asset_v('css/social/guild_hall.css') ?>">
<style>
    /* Custom override for guild hall if needed */
    .guild-header {
        margin-top: -1px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="main-dashboard-wrapper">
    <!-- TOP NAVIGATION -->
    <?= $this->include('App\Modules\User\Views\partials\navbar') ?>

    <main class="guild-main-content">
        <!-- HEADER -->
        <?= $this->include('App\Modules\Social\Views\partials\guild\_header') ?>

        <!-- THRONE ROOM -->
        <?= $this->include('App\Modules\Social\Views\partials\guild\_throne_room') ?>

        <!-- BARRACKS -->
        <?= $this->include('App\Modules\Social\Views\partials\guild\_member_list') ?>

        <!-- BACK LINK -->
        <section class="guild-section" style="text-align: center; padding: 2rem;">
            <a href="<?= base_url('world') ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to World
            </a>
        </section>
    </main>

    <!-- Bottom Nav -->
    <?= $this->include('App\Modules\User\Views\partials\bottom_nav') ?>
</div>
<?= $this->endSection() ?>