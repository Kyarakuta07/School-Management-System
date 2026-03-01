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

<form action="<?= base_url('verify-otp') ?>" method="POST">
    <?= csrf_field() ?>

    <input type="hidden" name="username" value="<?= esc($username ?? '') ?>">

    <div class="input-group">
        <label for="otp">Kode Verifikasi (OTP)</label>
        <input type="text" name="otp_code" pattern="[0-9]{6}" inputmode="numeric" maxlength="6" class="form-control"
            placeholder="Masukkan 6 digit kode" required autocomplete="off">
        <i class="fa-solid fa-key input-icon"></i>
    </div>

    <button type="submit" class="btn-login" style="margin-top: 1.5rem;">Konfirmasi & Selesaikan</button>
</form>

<div
    style="margin-top: 1rem; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
    <form action="<?= base_url('resend-otp') ?>" method="POST" style="display: inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="username" value="<?= esc($username ?? '') ?>">
        <button type="submit"
            style="background: none; border: none; color: #DAA520; cursor: pointer; font-size: 0.9rem; padding: 0; text-decoration: none;">
            Kirim Ulang Kode OTP
        </button>
    </form>
    <span style="color: #555;">|</span>
    <a href="<?= base_url('register') ?>" class="back-link">
        Daftar Ulang
    </a>
</div>
<?= $this->endSection() ?>