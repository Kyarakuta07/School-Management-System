<?php
/**
 * Admin Sidebar Component
 * Reusable navigation sidebar for admin panel
 * 
 * Required variable: $currentPage (string) - active page identifier
 * Valid values: 'dashboard', 'nethera', 'classes', 'settings'
 */
$currentPage = $currentPage ?? 'dashboard';
$userRole = $_SESSION['role'] ?? 'Vasiki';
$isHakaes = ($userRole === 'Hakaes');
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <img src="<?= $basePath ?? '' ?>../assets/landing/logo.png" class="sidebar-logo" alt="Logo" />
        <div class="brand-name">MOE<br><?= $isHakaes ? 'Teacher' : 'Admin' ?></div>
    </div>

    <div class="sidebar-menu">
        <?php if (!$isHakaes): ?>
            <a href="<?= $basePath ?? '' ?>index.php" <?= $currentPage === 'dashboard' ? 'class="active"' : '' ?>>
                <i class="uil uil-create-dashboard"></i> <span>Dashboard</span>
            </a>
            <a href="<?= $basePath ?? '' ?>pages/manage_nethera.php" <?= $currentPage === 'nethera' ? 'class="active"' : '' ?>>
                <i class="uil uil-users-alt"></i> <span>Manage Nethera</span>
            </a>
        <?php endif; ?>

        <a href="<?= $basePath ?? '' ?>pages/manage_classes.php" <?= $currentPage === 'classes' ? 'class="active"' : '' ?>>
            <i class="uil uil-book-open"></i> <span>Manage Classes</span>
        </a>
        <a href="<?= $basePath ?? '' ?>../user/beranda.php" <?= $currentPage === 'user_view' ? 'class="active"' : '' ?>>
            <i class="uil uil-eye"></i> <span>View User Dashboard</span>
        </a>

        <?php if (!$isHakaes): ?>
            <a href="#" <?= $currentPage === 'settings' ? 'class="active"' : '' ?>>
                <i class="uil uil-setting"></i> <span>Settings</span>
            </a>
        <?php endif; ?>

        <div class="menu-bottom">
            <a href="<?= $basePath ?? '' ?>../auth/handlers/logout.php">
                <i class="uil uil-signout"></i> <span>Logout</span>
            </a>
        </div>
    </div>
</nav>