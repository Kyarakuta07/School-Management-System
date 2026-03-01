<?php $bodyClass = 'auth-layout'; ?>
<?= $this->extend('layouts/base') ?>

<?= $this->section('head') ?>
<!-- Auth pages use css/shared/global.css which has login-specific styles (glass card, inputs, buttons) -->
<link rel="stylesheet" href="<?= base_url('css/shared/global.css') ?>">

<!-- Page-specific CSS -->
<?= $this->renderSection('css') ?>
<?= $this->endSection() ?>

<?= $this->section('body') ?>
<div class="login-container <?= $containerClass ?? '' ?>" <?= isset($containerMaxWidth) ? 'style="max-width:' . $containerMaxWidth . '"' : '' ?>>
    <!-- Logo -->
    <div class="login-logo">
        <img src="<?= base_url('assets/landing/logo.png') ?>" alt="MOE Logo">
    </div>

    <!-- Page Title & Subtitle -->
    <h1>
        <?= esc($authTitle ?? 'MEDITERRANEAN OF EGYPT') ?>
    </h1>
    <?php if (!empty($authSubtitle)): ?>
        <p class="subtitle">
            <?= esc($authSubtitle) ?>
        </p>
    <?php endif; ?>

    <!-- Page Content (form, success message, etc.) -->
    <?= $this->renderSection('content') ?>
</div>
<?= $this->endSection() ?>