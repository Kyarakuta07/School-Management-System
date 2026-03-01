<?php
/**
 * Admin Sidebar Partial
 * Ported from legacy moe/admin/layouts/components/_sidebar.php
 *
 * Required: $currentPage (string) - active page identifier
 * Available via controller data or layout defaults.
 */
$currentPage = $currentPage ?? 'dashboard';
$userRole = user_role() ?? 'Vasiki';
$isHakaes = ($userRole === 'Hakaes');
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <img src="<?= base_url('assets/landing/logo.png') ?>" class="sidebar-logo" alt="Logo" />
        <div class="brand-name">MOE<br>
            <?= $isHakaes ? 'Teacher' : 'Admin' ?>
        </div>
    </div>

    <div class="sidebar-menu">
        <?php if (!$isHakaes): ?>
            <a href="<?= base_url('admin') ?>" <?= $currentPage === 'dashboard' ? 'class="active"' : '' ?>>
                <i class="uil uil-create-dashboard"></i> <span>Dashboard</span>
            </a>
            <a href="<?= base_url('admin/nethera') ?>" <?= $currentPage === 'nethera' ? 'class="active"' : '' ?>>
                <i class="uil uil-users-alt"></i> <span>Manage Nethera</span>
            </a>
        <?php endif; ?>

        <a href="<?= base_url('admin/classes') ?>" <?= $currentPage === 'classes' ? 'class="active"' : '' ?>>
            <i class="uil uil-book-open"></i> <span>Manage Classes</span>
        </a>
        <a href="<?= base_url('beranda') ?>" <?= $currentPage === 'user_view' ? 'class="active"' : '' ?>>
            <i class="uil uil-eye"></i> <span>View User Dashboard</span>
        </a>

        <?php if (!$isHakaes): ?>
            <a href="<?= base_url('admin') ?>" <?= $currentPage === 'settings' ? 'class="active"' : '' ?>>
                <i class="uil uil-setting"></i> <span>Settings</span>
            </a>
        <?php endif; ?>

        <div class="menu-bottom">
            <form action="<?= base_url('logout') ?>" method="POST" style="margin: 0;">
                <?= csrf_field() ?>
                <button type="submit"
                    style="background: none; border: none; color: inherit; cursor: pointer; display: flex; align-items: center; gap: 8px; width: 100%; padding: 0; font: inherit;">
                    <i class="uil uil-signout"></i> <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</nav>