<?php $bodyClass = 'auth-layout'; ?>
<?= $this->extend('layouts/base') ?>

<?= $this->section('head') ?>
<?php // Auth CSS ?>
<link rel="stylesheet" href="<?= asset_v('css/shared/global.css') ?>">

<?php // Page CSS ?>
<?= $this->renderSection('css') ?>
<?= $this->endSection() ?>

<?= $this->section('body') ?>
<div class="login-container <?= $containerClass ?? '' ?>" <?= isset($containerMaxWidth) ? 'style="max-width:' . $containerMaxWidth . '"' : '' ?>>
    <!-- Logo -->
    <div class="login-logo">
        <img src="<?= asset_v('assets/landing/logo.png') ?>" alt="MOE Logo">
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

    <?php // Page content ?>
    <?= $this->renderSection('content') ?>
</div>
<?= $this->endSection() ?>