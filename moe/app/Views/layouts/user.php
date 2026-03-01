<?= $this->extend('layouts/base') ?>

<?= $this->section('head') ?>
<!-- Global User CSS (shared variables, reset, bg styles) -->
<link rel="stylesheet" href="<?= base_url('css/shared/global.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/shared/navbar.css') ?>">

<!-- Page-specific CSS -->
<?= $this->renderSection('css') ?>
<?= $this->endSection() ?>

<?= $this->section('body') ?>
<!-- Page Content -->
<!-- NOTE: Pages manually include partials/user/navbar and partials/user/bottom_nav
         at appropriate positions within their content, since some pages (beranda)
         have a hero header above the nav. -->
<?= $this->renderSection('content') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->renderSection('page_scripts') ?>
<?= $this->endSection() ?>