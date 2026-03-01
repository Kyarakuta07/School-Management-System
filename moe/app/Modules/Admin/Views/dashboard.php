<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<header class="top-header">
    <h1>Vasiki Dashboard</h1>
    <h2>Welcome back,
        <?= $userName ?>
    </h2>
</header>

<!-- Pending Alert Banner -->
<?php if ($totalPending > 0): ?>
    <div class="alert-banner alert-warning">
        <i class="uil uil-exclamation-triangle"></i>
        <div class="alert-content">
            <strong>
                <?= $totalPending ?> Pending Registration
                <?= $totalPending > 1 ? 's' : '' ?>
            </strong>
            <span>Members menunggu verifikasi akun</span>
        </div>
        <a href="<?= base_url('admin/nethera') ?>" class="alert-action">Review Now <i class="uil uil-arrow-right"></i></a>
    </div>
<?php endif; ?>

<!-- Quick Actions Bar -->
<div class="quick-actions">
    <a href="<?= base_url('admin/nethera') ?>" class="quick-action-btn">
        <i class="uil uil-users-alt"></i>
        <span>Manage Users</span>
    </a>
    <a href="<?= base_url('admin/classes') ?>" class="quick-action-btn">
        <i class="uil uil-book-open"></i>
        <span>View Classes</span>
    </a>
    <a href="<?= base_url('admin/grades/add') ?>" class="quick-action-btn">
        <i class="uil uil-plus-circle"></i>
        <span>Add Grade</span>
    </a>
    <a href="<?= base_url('admin/schedule/add') ?>" class="quick-action-btn">
        <i class="uil uil-calendar-alt"></i>
        <span>Add Schedule</span>
    </a>
</div>

<!-- Stats Row - 6 Cards -->
<div class="stats-row stats-row--6">
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(218, 165, 32, 0.2); color: var(--gold);">
            <i class="uil uil-users-alt"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $totalAll ?>
            </span>
            <span class="mini-stat-label">Total Members</span>
        </div>
    </div>
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(50, 205, 50, 0.2); color: #32cd32;">
            <i class="uil uil-check-circle"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $totalNethera ?>
            </span>
            <span class="mini-stat-label">Active</span>
        </div>
    </div>
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(255, 165, 0, 0.2); color: #ffa500;">
            <i class="uil uil-clock"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $totalPending ?>
            </span>
            <span class="mini-stat-label">Pending</span>
        </div>
    </div>
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(100, 149, 237, 0.2); color: #6495ed;">
            <i class="uil uil-pause-circle"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $totalHiatus ?>
            </span>
            <span class="mini-stat-label">Hiatus</span>
        </div>
    </div>
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(255, 107, 107, 0.2); color: #ff6b6b;">
            <i class="uil uil-times-circle"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $totalOut ?>
            </span>
            <span class="mini-stat-label">Out</span>
        </div>
    </div>
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(79, 172, 254, 0.2); color: #4facfe;">
            <i class="uil uil-building"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $sanctuaryCount ?>
            </span>
            <span class="mini-stat-label">Sanctuaries</span>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card card-list">
        <div class="card-header card-header--flex">
            <h3><i class="uil uil-user-plus"></i> Recent Registrations</h3>
            <a href="<?= base_url('admin/nethera') ?>" class="view-all-link">
                View All <i class="uil uil-arrow-right"></i>
            </a>
        </div>
        <div class="user-list">
            <?php if (!empty($latestUsers)): ?>
                <?php foreach ($latestUsers as $user): ?>
                    <?php $initial = strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                    <div class="user-item user-item--enhanced">
                        <div class="user-avatar-small">
                            <?= $initial ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name">
                                <?= esc($user['nama_lengkap']) ?>
                            </span>
                            <span class="user-sanctuary">
                                <?= esc($user['nama_sanctuary'] ?? 'No Sanctuary') ?>
                            </span>
                        </div>
                        <span class="status-badge status-<?= str_replace(' ', '', $user['status_akun']) ?>">
                            <?= esc($user['status_akun']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-message">No recent registrations.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-chart">
        <div class="card-header">
            <h3><i class="uil uil-chart-bar"></i> Active Members Distribution</h3>
        </div>
        <div id="area-chart"></div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    const CHART_LABELS = <?= json_encode($sanctuaryLabels) ?>;
    const CHART_DATA = <?= json_encode($sanctuaryValues) ?>;
</script>
<script src="<?= base_url('js/admin/dashboard.js') ?>"></script>
<?= $this->endSection() ?>