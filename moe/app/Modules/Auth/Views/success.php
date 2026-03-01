<?= $this->extend('layouts/auth') ?>

<?= $this->section('css') ?>
<style>
    .login-container {
        max-width: 450px !important;
        padding: clamp(2rem, 5vh, 3.5rem) clamp(1.5rem, 5vw, 2rem) !important;
        text-align: center;
        border-bottom: 3px solid var(--gold);
    }

    .success-icon-container {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 2px solid var(--gold);
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 20px;
        box-shadow: 0 0 20px rgba(218, 165, 32, 0.4);
        animation: pulseGlow 2s ease-in-out infinite;
    }

    @keyframes pulseGlow {

        0%,
        100% {
            box-shadow: 0 0 15px rgba(218, 165, 32, 0.3);
        }

        50% {
            box-shadow: 0 0 30px rgba(218, 165, 32, 0.6);
        }
    }

    .success-icon {
        color: var(--gold);
        font-size: 2.5rem;
    }

    .success-title {
        color: var(--gold);
        font-family: 'Cinzel', serif;
        font-size: 1.8rem;
        margin-bottom: 10px;
        letter-spacing: 2px;
    }

    .success-message {
        color: #ccc;
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    .btn-home {
        background: transparent;
        border: 1px solid var(--gold);
        color: var(--gold);
        padding: 10px 25px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.3s;
        display: inline-block;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
    }

    .btn-home:hover {
        background: var(--gold);
        color: #000;
        box-shadow: 0 0 15px rgba(218, 165, 32, 0.6);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="success-icon-container">
    <i class="fa-solid fa-check success-icon"></i>
</div>

<h1 class="success-title">BERHASIL</h1>

<div class="success-message">
    <p>Selamat datang, <strong><?= esc($username ?? 'Anggota Baru') ?></strong>.</p>
    <p style="font-size: 0.9rem; color: #888; margin-top: 5px;">
        Email Anda telah diverifikasi. Akun Anda sedang menunggu persetujuan Vasiki (Admin).
    </p>
</div>

<a href="<?= base_url('login') ?>" class="btn-home">
    <i class="fa-solid fa-right-to-bracket"></i> Kembali ke Login
</a>
<?= $this->endSection() ?>