<?php
// Ensure SITE_URL is defined (should be via environment.php)
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/core/environment.php';
}
?>
<header class="main-header">
    <div class="logo-container">
        <a href="<?= SITE_URL ?>/home.php">
            <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="Logo" class="logo">
        </a>
    </div>

    <!-- Hamburger Menu Toggle (Checkbox Hack) -->
    <input type="checkbox" id="nav-toggle" class="nav-toggle">
    <label for="nav-toggle" class="nav-toggle-label">
        <span></span>
        <span></span>
        <span></span>
    </label>

    <nav class="main-nav">
        <ul>
            <li><a href="<?= SITE_URL ?>/home.php">Home</a></li>
            <li><a href="<?= SITE_URL ?>/staff.php">Staff</a></li>
            <li><a href="<?= SITE_URL ?>/classes.php">Class</a></li>
            <li><a href="<?= SITE_URL ?>/world.php">World</a></li>
            <!-- Mobile Only Buttons -->
            <li class="mobile-buttons">
                <a href="<?= SITE_URL ?>/index.php" class="login-btn">Login</a>
                <a href="<?= SITE_URL ?>/auth/views/register.php" class="signup-btn">Sign Up</a>
            </li>
        </ul>
    </nav>

    <div class="header-buttons desktop-buttons">
        <a href="<?= SITE_URL ?>/index.php" class="login-btn">Login</a>
        <a href="<?= SITE_URL ?>/auth/views/register.php" class="signup-btn">Sign Up</a>
    </div>
</header>