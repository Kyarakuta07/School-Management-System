<?= $this->extend('layouts/auth') ?>

<?= $this->section('css') ?>
<style>
    .login-container {
        max-width: 450px !important;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('info')): ?>
    <div class="alert-warning" style="background: rgba(184, 138, 27, 0.2); border-color: #DAA520; color: #DAA520;">
        <i class="fa-solid fa-info-circle"></i>
        <?= esc(session()->getFlashdata('info')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('forgot-password') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="input-group">
        <label for="email">Alamat Email</label>
        <input type="email" name="email" class="form-control" placeholder="Email terdaftar" required
            autocomplete="email">
        <i class="fa-solid fa-envelope input-icon"></i>
    </div>

    <button type="submit" class="btn-login" style="margin-top: 1.5rem;">Kirim Tautan Reset</button>
</form>

<a href="<?= base_url('login') ?>" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
</a>
<?= $this->endSection() ?>