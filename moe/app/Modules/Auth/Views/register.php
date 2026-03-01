<?= $this->extend('layouts/auth') ?>

<?= $this->section('css') ?>
<style>
    .login-container {
        max-width: 580px !important;
        padding: 2.5rem 2.5rem 2rem !important;
    }

    /* 2-column grid for desktop */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 1.2rem;
    }

    .form-grid .input-group {
        margin-bottom: 0.8rem;
    }

    /* Full-width fields span both columns */
    .form-grid .full-width {
        grid-column: 1 / -1;
    }

    /* Section dividers */
    .form-section-label {
        grid-column: 1 / -1;
        font-family: 'Cinzel', serif;
        font-size: 0.65rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 2px;
        padding-bottom: 0.3rem;
        margin-top: 0.5rem;
        margin-bottom: 0.2rem;
        border-bottom: 1px solid rgba(218, 165, 32, 0.15);
    }

    /* Compact password requirements */
    .password-requirements {
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 0.15rem 0.5rem !important;
    }

    /* Compact field hints */
    .field-hint {
        font-size: 0.65rem !important;
        margin-top: 0.2rem !important;
        opacity: 0.6;
        transition: opacity 0.3s;
    }

    .input-group:focus-within .field-hint {
        opacity: 1;
    }

    /* Smaller label font */
    .input-group label {
        font-size: 0.6rem !important;
        margin-bottom: 3px !important;
    }

    /* Compact inputs */
    .form-control {
        height: 38px !important;
        font-size: 0.85rem !important;
    }

    /* Fix icon alignment: center within input, not the full input-group */
    .input-group .input-icon {
        top: 34px !important;
        margin-top: 0 !important;
        transform: translateY(-50%);
    }

    /* Better submit button */
    .btn-register {
        grid-column: 1 / -1;
        width: 100%;
        padding: 12px;
        margin-top: 0.8rem;
        background: linear-gradient(135deg, #cca43b, #8c6a18);
        color: #000;
        border: 1px solid #ffd700;
        border-radius: 8px;
        font-family: 'Cinzel', serif;
        font-weight: 800;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        position: relative;
        overflow: hidden;
    }

    .btn-register::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
        transition: left 0.5s;
    }

    .btn-register:hover {
        filter: brightness(1.15);
        box-shadow: 0 0 25px rgba(218, 165, 32, 0.4);
        transform: translateY(-2px);
    }

    .btn-register:hover::before {
        left: 100%;
    }

    .btn-register:active {
        transform: translateY(0);
    }

    /* Smaller logo for register */
    .login-logo {
        width: 65px !important;
        height: 65px !important;
        margin-bottom: 0.7rem !important;
    }

    h1 {
        font-size: 1.15rem !important;
        margin-bottom: 0.2rem !important;
    }

    .subtitle {
        margin-bottom: 1.2rem !important;
        font-size: 0.6rem !important;
    }

    /* Responsive: single column on mobile */
    @media screen and (max-width: 550px) {
        .login-container {
            max-width: 100% !important;
            padding: 1.5rem 1.25rem !important;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-grid .full-width {
            grid-column: 1;
        }

        .form-section-label {
            grid-column: 1;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = session()->getFlashdata('errors') ?? [];
$errorMsg = session()->getFlashdata('error');
?>

<?php if ($errorMsg): ?>
    <div class="alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= esc($errorMsg) ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('register') ?>" method="POST" id="registerForm" novalidate>
    <?= csrf_field() ?>

    <div class="form-grid">

        <div class="form-section-label">Identitas</div>

        <!-- Nama Lengkap -->
        <div class="input-group">
            <label for="nama_lengkap">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" id="nama_lengkap"
                class="form-control <?= isset($errors['nama_lengkap']) ? 'is-invalid' : '' ?>"
                placeholder="Nama Lengkap" required autocomplete="off"
                pattern="^[a-zA-Z\s'.]+$" minlength="3" maxlength="100"
                value="<?= old('nama_lengkap') ?>">
            <i class="fa-solid fa-signature input-icon"></i>
            <span class="field-hint">Huruf, spasi, apostrof, titik</span>
            <?php if (isset($errors['nama_lengkap'])): ?>
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($errors['nama_lengkap']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Username -->
        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username"
                class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                placeholder="Username unik" required autocomplete="off"
                pattern="^[a-zA-Z0-9_]+$" minlength="3" maxlength="50"
                value="<?= old('username') ?>">
            <i class="fa-solid fa-user-tag input-icon"></i>
            <span class="field-hint">Huruf, angka, underscore</span>
            <?php if (isset($errors['username'])): ?>
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($errors['username']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-section-label">Kontak</div>

        <!-- Email -->
        <div class="input-group">
            <label for="email">Alamat Email</label>
            <input type="email" name="email" id="email"
                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                placeholder="Email aktif" required autocomplete="email"
                maxlength="100"
                value="<?= old('email') ?>">
            <i class="fa-solid fa-envelope input-icon"></i>
            <?php if (isset($errors['email'])): ?>
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <!-- No HP -->
        <div class="input-group">
            <label for="noHP">Nomor HP/WA</label>
            <input type="text" name="noHP" id="noHP"
                class="form-control <?= isset($errors['noHP']) ? 'is-invalid' : '' ?>"
                placeholder="081234567890" required autocomplete="tel"
                pattern="^[0-9+\-]+$" minlength="10" maxlength="15"
                value="<?= old('noHP') ?>">
            <i class="fa-solid fa-phone input-icon"></i>
            <?php if (isset($errors['noHP'])): ?>
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($errors['noHP']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-section-label">Keamanan</div>

        <!-- Password -->
        <div class="input-group full-width">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                placeholder="Buat password yang aman" required autocomplete="new-password"
                minlength="8">
            <i class="fa-solid fa-lock input-icon"></i>
            <div id="password-strength" style="margin-top: 0.4rem; display: none;">
                <div style="height: 3px; background: #2a2a2a; border-radius: 2px; overflow: hidden; margin-bottom: 0.2rem;">
                    <div id="strength-bar" style="height: 100%; width: 0%; transition: all 0.3s; background: #ff4444;">
                    </div>
                </div>
                <small id="strength-text" style="color: #ff4444; font-size: 0.75rem;">Weak</small>
            </div>
            <div class="password-requirements" id="pw-requirements">
                <div class="pw-req" id="req-length"><i class="fa-solid fa-circle"></i> Min. 8 karakter</div>
                <div class="pw-req" id="req-upper"><i class="fa-solid fa-circle"></i> 1 huruf besar</div>
                <div class="pw-req" id="req-lower"><i class="fa-solid fa-circle"></i> 1 huruf kecil</div>
                <div class="pw-req" id="req-number"><i class="fa-solid fa-circle"></i> 1 angka</div>
            </div>
            <?php if (isset($errors['password'])): ?>
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($errors['password']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Konfirmasi Password -->
        <div class="input-group full-width">
            <label for="password_confirm">Konfirmasi Password</label>
            <input type="password" id="password_confirm" name="password_confirm"
                class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                placeholder="Ketik ulang password" required autocomplete="new-password">
            <i class="fa-solid fa-lock input-icon"></i>
            <div id="confirm-feedback" class="field-error" style="display: none;">
                <i class="fa-solid fa-circle-exclamation"></i> Password tidak cocok
            </div>
            <?php if (isset($errors['password_confirm'])): ?>
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($errors['password_confirm']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-section-label">Informasi Lainnya</div>

        <!-- Tanggal Lahir -->
        <div class="input-group">
            <label for="tanggal_lahir">Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" id="tanggal_lahir"
                class="form-control <?= isset($errors['tanggal_lahir']) ? 'is-invalid' : '' ?>"
                required max="<?= date('Y-m-d') ?>"
                value="<?= old('tanggal_lahir') ?>">
            <i class="fa-solid fa-calendar-day input-icon"></i>
            <span class="field-hint">Usia minimal 10 tahun</span>
            <?php if (isset($errors['tanggal_lahir'])): ?>
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($errors['tanggal_lahir']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Periode Masuk -->
        <div class="input-group">
            <label for="periode_masuk">Periode Masuk</label>
            <input type="number" name="periode_masuk" id="periode_masuk"
                class="form-control <?= isset($errors['periode_masuk']) ? 'is-invalid' : '' ?>"
                required min="1"
                value="<?= old('periode_masuk', '1') ?>">
            <i class="fa-solid fa-calendar-alt input-icon"></i>
            <?php if (isset($errors['periode_masuk'])): ?>
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($errors['periode_masuk']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-register">
            <i class="fa-solid fa-scroll" style="margin-right: 8px;"></i>
            Daftar & Verifikasi Email
        </button>

    </div><!-- .form-grid -->
</form>

<a href="<?= base_url('login') ?>" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
</a>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    const strengthIndicator = document.getElementById('password-strength');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const confirmFeedback = document.getElementById('confirm-feedback');
    const reqLength = document.getElementById('req-length');
    const reqUpper = document.getElementById('req-upper');
    const reqLower = document.getElementById('req-lower');
    const reqNumber = document.getElementById('req-number');

    // Password strength + requirements checker
    passwordInput.addEventListener('input', function () {
        const pw = this.value;
        if (pw.length === 0) {
            strengthIndicator.style.display = 'none';
            [reqLength, reqUpper, reqLower, reqNumber].forEach(el => el.classList.remove('met'));
            return;
        }
        strengthIndicator.style.display = 'block';

        const checks = {
            length8: pw.length >= 8, length12: pw.length >= 12,
            upper: /[A-Z]/.test(pw), lower: /[a-z]/.test(pw),
            number: /\d/.test(pw), special: /[^a-zA-Z\d]/.test(pw)
        };

        reqLength.classList.toggle('met', checks.length8);
        reqUpper.classList.toggle('met', checks.upper);
        reqLower.classList.toggle('met', checks.lower);
        reqNumber.classList.toggle('met', checks.number);

        let strength = 0;
        if (checks.length8) strength++;
        if (checks.length12) strength++;
        if (checks.upper && checks.lower) strength++;
        if (checks.number) strength++;
        if (checks.special) strength++;

        const levels = [
            { label: 'Very Weak', color: '#ff4444', width: '20%' },
            { label: 'Weak', color: '#ff8800', width: '40%' },
            { label: 'Fair', color: '#ffbb33', width: '60%' },
            { label: 'Good', color: '#00c851', width: '80%' },
            { label: 'Strong', color: '#00c851', width: '90%' },
            { label: 'Very Strong', color: '#007e33', width: '100%' }
        ];
        const level = levels[strength] || levels[0];
        strengthBar.style.width = level.width;
        strengthBar.style.background = level.color;
        strengthText.textContent = level.label;
        strengthText.style.color = level.color;

        if (confirmInput.value.length > 0) checkConfirmMatch();
    });

    // Password confirm checker
    function checkConfirmMatch() {
        if (confirmInput.value.length === 0) {
            confirmFeedback.style.display = 'none';
            confirmInput.classList.remove('is-invalid', 'is-valid');
            return;
        }
        if (confirmInput.value !== passwordInput.value) {
            confirmFeedback.style.display = 'block';
            confirmInput.classList.add('is-invalid');
            confirmInput.classList.remove('is-valid');
        } else {
            confirmFeedback.style.display = 'none';
            confirmInput.classList.remove('is-invalid');
            confirmInput.classList.add('is-valid');
        }
    }
    confirmInput.addEventListener('input', checkConfirmMatch);

    // Real-time field validation
    function addLiveValidation(id, regex) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function () {
            if (this.value.length === 0) {
                this.classList.remove('is-invalid', 'is-valid');
                return;
            }
            if (!regex.test(this.value)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    }

    addLiveValidation('nama_lengkap', /^[a-zA-Z\s'.]+$/);
    addLiveValidation('username', /^[a-zA-Z0-9_]+$/);
    addLiveValidation('noHP', /^[0-9+\-]+$/);

    // Block invalid characters on keypress for username
    document.getElementById('username').addEventListener('keypress', function (e) {
        const char = String.fromCharCode(e.charCode || e.keyCode);
        if (!/[a-zA-Z0-9_]/.test(char)) e.preventDefault();
    });

    // Block non-numeric on keypress for noHP (allow + and -)
    document.getElementById('noHP').addEventListener('keypress', function (e) {
        const char = String.fromCharCode(e.charCode || e.keyCode);
        if (!/[0-9+\-]/.test(char)) e.preventDefault();
    });

    // Password ≠ Username check (client-side)
    const usernameInput = document.getElementById('username');
    function checkPasswordUsername() {
        const pw = passwordInput.value;
        const un = usernameInput.value;
        const pwUsernameWarn = document.getElementById('pw-username-warn');
        if (pw.length > 0 && un.length > 0 && pw.toLowerCase() === un.toLowerCase()) {
            if (!pwUsernameWarn) {
                const warn = document.createElement('div');
                warn.id = 'pw-username-warn';
                warn.className = 'field-error';
                warn.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Password tidak boleh sama dengan username';
                passwordInput.closest('.input-group').appendChild(warn);
            }
            passwordInput.classList.add('is-invalid');
        } else {
            if (pwUsernameWarn) pwUsernameWarn.remove();
        }
    }
    passwordInput.addEventListener('input', checkPasswordUsername);
    usernameInput.addEventListener('input', checkPasswordUsername);
</script>
<?= $this->endSection() ?>