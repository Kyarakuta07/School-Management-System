<?= $this->extend('layouts/auth') ?>

<?= $this->section('css') ?>
<style>
    .login-container {
        max-width: 450px !important;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-success"
        style="background: rgba(0, 200, 81, 0.2); border-color: #00c851; color: #00c851; padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; border: 1px solid;">
        <i class="fa-solid fa-check-circle"></i>
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('reset-password') ?>" method="POST">
    <?= csrf_field() ?>

    <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

    <div class="input-group">
        <label for="password">Password Baru</label>
        <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required
            minlength="8" autocomplete="new-password">
        <i class="fa-solid fa-lock input-icon"></i>
    </div>

    <div class="input-group">
        <label for="password_confirm">Konfirmasi Password</label>
        <input type="password" name="password_confirm" class="form-control" placeholder="Ulangi password baru" required
            minlength="8" autocomplete="new-password">
        <i class="fa-solid fa-lock input-icon"></i>
    </div>

    <button type="submit" class="btn-login" style="margin-top: 1.5rem;">Reset Password</button>
</form>

<a href="<?= base_url('login') ?>" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
</a>
<?= $this->endSection() ?>