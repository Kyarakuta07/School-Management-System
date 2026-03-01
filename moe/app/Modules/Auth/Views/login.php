<?= $this->extend('layouts/auth') ?>

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

<?php if (session()->getFlashdata('warning')): ?>
    <div class="alert-warning">
        <i class="fa-solid fa-clock"></i>
        <?= esc(session()->getFlashdata('warning')) ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('login') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="input-group">
        <label for="username">USERNAME</label>
        <input type="text" name="username" class="form-control" placeholder="Enter username" required
            autocomplete="off">
        <i class="fa-solid fa-user input-icon"></i>
    </div>

    <div class="input-group">
        <label for="password">PASSWORD</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
        <i class="fa-solid fa-lock input-icon"></i>
    </div>

    <button type="submit" class="btn-login">LOGIN</button>

    <div class="footer-links">
        <a href="<?= base_url('forgot-password') ?>">Forgot Password?</a>
        <a href="<?= base_url('register') ?>">Create Account</a>
    </div>
</form>

<a href="<?= base_url('/') ?>" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> BACK TO HOME
</a>
<?= $this->endSection() ?>