<?= $this->extend('layouts/user') ?>

<?= $this->section('head') ?>
<!-- Consolidated Production Bundle (Global + Navbar + Pet Core) -->
<link rel="stylesheet" href="<?= asset_v('css/pet/pet_bundle_v1.css') ?>">
<!-- Gacha & Bestiary Premium Styles -->
<link rel="stylesheet" href="<?= asset_v('css/pet/gacha_premium.css') ?>">
<!-- Sub-tab CSS files are lazy-loaded by pet/ui.js -->
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- PixiJS Container -->
<div id="pixi-bg-container"></div>

<!-- App Container -->
<div class="app-container">
    <?= $this->include('components/pet/_header') ?>
    <?= $this->include('components/pet/_tab_nav') ?>

    <main class="main-content">
        <?= $this->include('components/pet/tabs/_my_pet') ?>
        <?= $this->include('components/pet/tabs/_collection') ?>
        <?= $this->include('components/pet/tabs/_library') ?>
        <?= $this->include('components/pet/tabs/_gacha') ?>
        <?= $this->include('components/pet/tabs/_shop') ?>
        <?= $this->include('components/pet/tabs/_arena') ?>
        <?= $this->include('components/pet/tabs/_arena_3v3') ?>
        <?= $this->include('components/pet/tabs/_sanctuary_war') ?>
        <?= $this->include('components/pet/tabs/_leaderboard') ?>
        <?= $this->include('components/pet/tabs/_history') ?>
        <?= $this->include('components/pet/tabs/_achievements') ?>
    </main>
</div>

<?= $this->include('components/pet/_bottom_nav') ?>

<!-- MODALS -->
<?= $this->include('components/pet/modals/_gacha_result') ?>
<?= $this->include('components/pet/modals/_daily_login') ?>
<?= $this->include('components/pet/modals/_pet_modals') ?>
<?= $this->include('components/pet/modals/_evolution') ?>
<?= $this->include('components/pet/modals/_shop_modals') ?>
<?= $this->include('components/pet/modals/_confirm') ?>

<!-- Inline Styles -->
<?= $this->include('components/pet/_inline_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('page_scripts') ?>
<!-- Pet Scripts -->
<?= $this->include('components/pet/_scripts') ?>
<?= $this->endSection() ?>