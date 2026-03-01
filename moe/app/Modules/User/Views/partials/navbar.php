<!-- TOP NAVIGATION (Unified) -->
<?php helper('auth');
$isLoggedIn = user_is_logged_in(); ?>

<header class="main-header <?= $isLoggedIn ? 'premium-dashboard-nav' : '' ?>">
    <div class="logo-container">
        <a href="<?= base_url('/') ?>">
            <img src="<?= base_url('assets/landing/logo.png') ?>" alt="Logo" class="logo">
        </a>
    </div>

    <!-- Hamburger Menu Toggle (Project Native) -->
    <input type="checkbox" id="nav-toggle" class="nav-toggle">
    <label for="nav-toggle" class="nav-toggle-label">
        <span></span>
        <span></span>
        <span></span>
    </label>

    <nav class="main-nav">
        <ul>
            <?php if ($isLoggedIn): ?>
                <li><a href="<?= base_url('beranda') ?>"
                        class="nav-btn <?= ($activePage ?? '') === 'beranda' ? 'active' : '' ?>"><i class="fas fa-home"></i>
                        Home</a></li>
                <li><a href="<?= base_url('class') ?>"
                        class="nav-btn <?= ($activePage ?? '') === 'class' ? 'active' : '' ?>"><i
                            class="fas fa-chalkboard-teacher"></i> Class</a></li>
                <li><a href="<?= base_url('pet') ?>" class="nav-btn <?= ($activePage ?? '') === 'pet' ? 'active' : '' ?>"><i
                            class="fas fa-paw"></i> Pet</a></li>
                <li><a href="<?= base_url('trapeza') ?>"
                        class="nav-btn <?= ($activePage ?? '') === 'trapeza' ? 'active' : '' ?>"><i
                            class="fas fa-vault"></i> Trapeza</a></li>
                <li><a href="<?= base_url('punishment') ?>"
                        class="nav-btn <?= ($activePage ?? '') === 'punishment' ? 'active' : '' ?>"><i
                            class="fas fa-gavel"></i>
                        Punishment</a></li>
            <?php else: ?>
                <li><a href="<?= base_url('/') ?>">HOME</a></li>
                <li><a href="<?= base_url('staff') ?>">STAFF</a></li>
                <li><a href="<?= base_url('classes') ?>">CLASS</a></li>
                <li><a href="<?= base_url('world') ?>">WORLD</a></li>
            <?php endif; ?>

            <!-- Mobile Only Buttons -->
            <li class="mobile-buttons">
                <?php if ($isLoggedIn): ?>
                    <form action="<?= base_url('logout') ?>" method="POST" style="display: inline; margin: 0;">
                        <?= csrf_field() ?>
                        <button type="submit" class="signup-btn"
                            style="border: none; cursor: pointer; font: inherit;">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="login-btn">Login</a>
                    <a href="<?= base_url('register') ?>" class="signup-btn">Sign Up</a>
                <?php endif; ?>
            </li>
        </ul>
    </nav>

    <div class="header-buttons desktop-buttons">
        <?php if (!$isLoggedIn): ?>
            <a href="<?= base_url('login') ?>" class="login-btn">Login</a>
            <a href="<?= base_url('register') ?>" class="signup-btn">Sign Up</a>
        <?php endif; ?>
    </div>
</header>