<?= $this->extend('layouts/base') ?>

<?= $this->section('head') ?>
<!-- Admin-specific Icons -->
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />

<!-- Core Admin Styles -->
<link rel="stylesheet" href="<?= base_url('css/admin/style.css') ?>" />
<link rel="stylesheet" href="<?= base_url('css/admin/cards.css') ?>" />

<!-- Extra CSS (page-specific, passed from controller) -->
<?php if (!empty($extraCss)): ?>
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= base_url('css/admin/' . $css) ?>" />
    <?php endforeach; ?>
<?php endif; ?>

<!-- Page-specific CSS -->
<?= $this->renderSection('css') ?>
<?= $this->endSection() ?>

<?= $this->section('body') ?>
<!-- Admin Sidebar -->
<?= $this->include('partials/admin/sidebar') ?>

<main class="main-content">
    <?= $this->renderSection('content') ?>
</main>

<!-- Common Admin Scripts -->
<script src="<?= base_url('js/admin/sidebar-toggle.js') ?>"></script>
<?= $this->endSection() ?>