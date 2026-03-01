<header class="main-header">
    <div class="logo-container">
        <a href="<?= base_url('/') ?>">
            <img src="<?= base_url('assets/landing/logo.png') ?>" alt="Logo" class="logo">
        </a>
    </div>

    <!-- Hamburger Menu Toggle -->
    <input type="checkbox" id="nav-toggle" class="nav-toggle">
    <label for="nav-toggle" class="nav-toggle-label">
        <span></span>
        <span></span>
        <span></span>
    </label>

    <nav class="main-nav">
        <ul>
            <li><a href="<?= base_url('/') ?>">Home</a></li>
            <li><a href="<?= base_url('staff') ?>">Staff</a></li>
            <li><a href="<?= base_url('classes') ?>">Class</a></li>
            <li><a href="<?= base_url('world') ?>">World</a></li>
            <!-- Mobile Only Buttons -->
            <li class="mobile-buttons">
                <a href="<?= base_url('login') ?>" class="login-btn">Login</a>
                <a href="<?= base_url('register') ?>" class="signup-btn">Sign Up</a>
            </li>
        </ul>
    </nav>

    <div class="header-buttons desktop-buttons">
        <a href="<?= base_url('login') ?>" class="login-btn">Login</a>
        <a href="<?= base_url('register') ?>" class="signup-btn">Sign Up</a>
    </div>
</header>