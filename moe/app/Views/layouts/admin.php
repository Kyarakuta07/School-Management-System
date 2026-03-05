<?= $this->extend('layouts/base') ?>

<?= $this->section('head') ?>
<?php // Admin icons ?>
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />

<?php // Core admin styles ?>
<link rel="stylesheet" href="<?= asset_v('css/admin/style.css') ?>" />
<link rel="stylesheet" href="<?= asset_v('css/admin/cards.css') ?>" />

<?php // Extra CSS (page-specific) ?>
<?php if (!empty($extraCss)): ?>
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= base_url('css/admin/' . $css) ?>" />
    <?php endforeach; ?>
<?php endif; ?>

<?php // Page CSS ?>
<?= $this->renderSection('css') ?>
<?= $this->endSection() ?>

<?= $this->section('body') ?>
<?php // Admin sidebar ?>
<?= $this->include('partials/admin/sidebar') ?>

<main class="main-content">
    <?= $this->renderSection('content') ?>
</main>

<?php // Common admin scripts ?>
<script src="<?= asset_v('js/admin/sidebar-toggle.js') ?>"></script>
<?= $this->endSection() ?>