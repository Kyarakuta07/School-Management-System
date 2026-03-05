<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<header class="top-header">
    <h1>Manage Nethera</h1>
    <h2>Kelola data anggota terdaftar di Mediterranean Of Egypt</h2>
</header>

<!-- Stats Summary Cards -->
<div class="stats-row stats-row--5">
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(218, 165, 32, 0.2); color: var(--gold);">
            <i class="uil uil-users-alt"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $totalCount ?>
            </span>
            <span class="mini-stat-label">Total</span>
        </div>
    </div>
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(50, 205, 50, 0.2); color: #32cd32;">
            <i class="uil uil-check-circle"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $aktifCount ?>
            </span>
            <span class="mini-stat-label">Aktif</span>
        </div>
    </div>
    <div class="mini-stat-card">
        <div class="mini-stat-icon" style="background: rgba(255, 165, 0, 0.2); color: #ffa500;">
            <i class="uil uil-clock"></i>
        </div>
        <div class="mini-stat-info">
            <span class="mini-stat-value">
                <?= $pendingCount ?>
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
                <?= $hiatusCount ?>
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
                <?= $outCount ?>
            </span>
            <span class="mini-stat-label">Out</span>
        </div>
    </div>
</div>

<div class="card full-width-card">
    <div class="card-header card-header--flex">
        <h3 class="card-h3">
            <i class="uil uil-list-ul"></i> All Registered Nethera
        </h3>
        <div class="table-controls">
            <select id="statusFilter" class="filter-select" onchange="applyFilters()">
                <?php
                $filterOptions = ['all' => 'All Status', 'Aktif' => 'Aktif', 'Pending' => 'Pending', 'Hiatus' => 'Hiatus', 'Out' => 'Out'];
                foreach ($filterOptions as $val => $label):
                    $selected = ($currentStatus === $val) ? 'selected' : '';
                    ?>
                    <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <div class="search-container">
                <i class="uil uil-search"></i>
                <input type="search" id="searchInput" class="search-input"
                    placeholder="Search name, username, sanctuary..." value="<?= esc($currentSearch) ?>">
            </div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><i class="uil uil-tag-alt"></i> No. Reg</th>
                    <th><i class="uil uil-user"></i> Full Name</th>
                    <th><i class="uil uil-at"></i> Username</th>
                    <th><i class="uil uil-building"></i> Sanctuary</th>
                    <th><i class="uil uil-calendar-alt"></i> Periode</th>
                    <th><i class="uil uil-toggle-on"></i> Status</th>
                    <th><i class="uil uil-setting"></i> Actions</th>
                </tr>
            </thead>
            <tbody id="netheraTableBody">
                <?php if (!empty($allNethera)): ?>
                    <?php foreach ($allNethera as $nethera): ?>
                        <tr data-status="<?= esc($nethera['status_akun']) ?>">
                            <td>
                                <span class="reg-badge">
                                    <?= esc($nethera['no_registrasi']) ?>
                                </span>
                            </td>
                            <td>
                                <?= esc($nethera['nama_lengkap']) ?>
                            </td>
                            <td>@<?= esc($nethera['username']) ?></td>
                            <td>
                                <span class="sanctuary-badge">
                                    <?= esc($nethera['nama_sanctuary'] ?? '-') ?>
                                </span>
                            </td>
                            <td>
                                <?= esc($nethera['periode_masuk']) ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= str_replace(' ', '', $nethera['status_akun']) ?>">
                                    <?= esc($nethera['status_akun']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?= base_url('admin/nethera/edit/' . $nethera['id_nethera']) ?>" class="btn-edit"
                                        title="Edit">
                                        <i class="uil uil-edit"></i>
                                    </a>
                                    <form action="<?= base_url('admin/nethera/delete') ?>" method="POST" style="display:inline"
                                        onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_nethera" value="<?= $nethera['id_nethera'] ?>">
                                        <button type="submit" class="btn-delete" title="Delete">
                                            <i class="uil uil-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">Tidak ada data Nethera.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Links -->
    <?php if (isset($pager)): ?>
        <div class="pagination-footer" style="padding: 15px; display: flex; justify-content: flex-end;">
            <?= $pager ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_v('js/admin/manage_nethera.js') ?>"></script>
<?= $this->endSection() ?>