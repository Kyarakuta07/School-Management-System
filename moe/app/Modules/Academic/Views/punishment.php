<?= $this->extend('layouts/user') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= asset_v('css/academic/punishment_style.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="main-dashboard-wrapper">
    <!-- TOP NAVIGATION -->
    <?= $this->include('App\Modules\User\Views\partials\navbar') ?>

    <!-- HEADER -->
    <header class="hero-header">
        <div class="hero-content">
            <div class="greeting-section">
                <div class="greeting-emoji">⚖️</div>
                <div class="greeting-text">
                    <p class="greeting-line"><?= esc($sanctuaryName) ?> Sanctuary,</p>
                    <h1 class="user-name-hero">DISCIPLINE HUB</h1>
                </div>
            </div>

            <div class="hero-badges">
                <?php if ($canManage): ?>
                    <div class="sanctuary-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span><?= esc($role) ?> Mode Active</span>
                    </div>
                <?php else: ?>
                    <div class="sanctuary-badge">
                        <i class="fas fa-balance-scale"></i>
                        <span>Conduct Tracking</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if ($lockMessage): ?>
        <div class="alert-lock alert-top" style="--order: 0">
            <i class="fas fa-lock"></i>
            <span><?= esc($lockMessage) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($actionMessage): ?>
        <div class="alert-success alert-top" style="--order: 0"><i class="fas fa-check"></i> <?= esc($actionMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($actionError): ?>
        <div class="alert-error alert-top" style="--order: 0"><i class="fas fa-times"></i> <?= esc($actionError) ?></div>
    <?php endif; ?>

    <main class="punishment-main-content">

        <aside class="punishment-sidebar">

            <!-- Stats Card -->
            <div class="punishment-card stats-card" style="--order: 1">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> <?= $canManage ? 'System Stats' : 'Your Status' ?></h3>
                </div>
                <div class="card-body">
                    <div class="stat-item">
                        <span class="stat-label"><?= $canManage ? 'Active Cases' : 'Total Points' ?></span>
                        <span
                            class="stat-value <?= $totalPunishmentPoints > 20 ? 'danger' : ($totalPunishmentPoints > 10 ? 'warning' : 'safe') ?>">
                            <?= $canManage ? count($activePunishments) : $totalPunishmentPoints ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?= $canManage ? 'Total Records' : 'Active Sanctions' ?></span>
                        <span
                            class="stat-value"><?= $canManage ? count($punishmentHistory) : count($activePunishments) ?></span>
                    </div>

                    <?php if (!$canManage && $totalPunishmentPoints == 0): ?>
                        <div class="clean-record-badge">
                            <i class="fas fa-check-circle"></i>
                            <span>Clean Record</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ANUBIS: Add Punishment Form -->
            <?php if ($canManage): ?>
                <div class="anubis-panel" style="--order: 1">
                    <h3><i class="fas fa-gavel"></i> Discipline Inscription</h3>
                    <p class="panel-subtitle">Record violation in the eternal scrolls</p>

                    <form action="<?= base_url('punishment') ?>" method="POST" class="anubis-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_punishment">

                        <div class="form-group">
                            <label>Select Nethera</label>
                            <select name="target_id" class="form-control" required>
                                <option value="">-- Pilih User --</option>
                                <?php foreach ($allNethera as $nethera): ?>
                                    <option value="<?= $nethera['id_nethera'] ?>"><?= esc($nethera['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Jenis Pelanggaran</label>
                            <select name="jenis_pelanggaran" class="form-control" required>
                                <?php foreach ($violationTypes as $en => $id): ?>
                                    <option value="<?= esc($en) ?>"><?= esc($id) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2"
                                placeholder="Detail pelanggaran..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Jenis Hukuman</label>
                            <select name="jenis_hukuman" class="form-control" required>
                                <?php foreach ($punishmentTypes as $en => $id): ?>
                                    <option value="<?= esc($en) ?>"><?= esc($id) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Poin Pelanggaran</label>
                            <input type="number" name="poin" class="form-control" value="5" min="1" max="100">
                        </div>

                        <button type="submit" class="btn-anubis">
                            <i class="fas fa-gavel"></i> Tambah Punishment
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Active Punishments Card -->
            <?php if (count($activePunishments) > 0): ?>
                <div class="punishment-card active-punishment-card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Active Sanctions</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($activePunishments as $punishment): ?>
                            <div class="active-punishment-item">
                                <?php if ($canManage): ?>
                                    <div class="punishment-user"><?= esc($punishment['user_name'] ?? 'Unknown') ?></div>
                                <?php endif; ?>
                                <div class="punishment-title"><?= esc($punishment['jenis_pelanggaran']) ?></div>
                                <div class="punishment-points"><?= $punishment['poin_pelanggaran'] ?> pts</div>

                                <?php if ($canManage): ?>
                                    <form action="<?= base_url('punishment') ?>" method="POST" style="margin-top: 5px;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="release_punishment">
                                        <input type="hidden" name="punishment_id" value="<?= $punishment['id_punishment'] ?>">
                                        <button type="submit" class="btn-release">
                                            <i class="fas fa-unlock"></i> Release
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="punishment-card no-active-card">
                    <div class="card-body text-center">
                        <i class="fas fa-smile fa-3x" style="color: var(--gold); margin-bottom: 10px;"></i>
                        <p><?= $canManage ? 'No active punishments' : 'No active sanctions' ?></p>
                        <small><?= $canManage ? 'All users are in good standing!' : 'Keep up the good behavior!' ?></small>
                    </div>
                </div>
            <?php endif; ?>

        </aside>

        <!-- MAIN CONTENT: History -->
        <div class="punishment-main">

            <!-- Punishment History Section -->
            <section class="punishment-card history-card" style="--order: 2">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i>
                        <?= $canManage ? 'All Records' : 'Your Punishment History' ?></h2>
                </div>
                <div class="card-body">
                    <?php if (count($punishmentHistory) > 0): ?>
                        <div class="history-table">
                            <table>
                                <thead>
                                    <tr>
                                        <?php if ($canManage): ?>
                                            <th>User</th><?php endif; ?>
                                        <th>Date</th>
                                        <th>Violation</th>
                                        <th>Sanction</th>
                                        <th>Points</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($punishmentHistory as $record): ?>
                                        <tr>
                                            <?php if ($canManage): ?>
                                                <td data-label="User"><?= esc($record['user_name'] ?? 'Unknown') ?></td>
                                            <?php endif; ?>
                                            <td data-label="Date">
                                                <?= date('d M Y', strtotime($record['tanggal_pelanggaran'])) ?>
                                            </td>
                                            <td data-label="Violation"><?= esc($record['jenis_pelanggaran']) ?></td>
                                            <td data-label="Sanction"><?= esc($record['jenis_hukuman']) ?></td>
                                            <td data-label="Points"><span
                                                    class="points-badge"><?= $record['poin_pelanggaran'] ?></span></td>
                                            <td data-label="Status">
                                                <span class="status-badge <?= esc($record['status_hukuman']) ?>">
                                                    <?= esc(ucfirst($record['status_hukuman'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Links -->
                        <?php if ($pager): ?>
                            <div class="pagination-container" style="margin-top: 20px; display: flex; justify-content: center;">
                                <?= $pager->links('history') ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-check fa-4x"></i>
                            <h3><?= $canManage ? 'No Records' : 'Perfect Record!' ?></h3>
                            <p><?= $canManage ? 'No punishment records in the system.' : 'You have no punishment history. Keep maintaining excellent conduct!' ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Code of Conduct Section -->
            <section class="punishment-card conduct-card" style="--order: 3">
                <div class="card-header">
                    <h2><i class="fas fa-scroll"></i> Sanctuary Code of Conduct</h2>
                </div>
                <div class="card-body">
                    <div class="code-grid">
                        <?php $i = 0;
                        foreach ($codeOfConduct as $code): ?>
                            <div class="code-category" style="--order: <?= 4 + $i++ ?>">
                                <div class="code-header">
                                    <h4><i class="fas <?= esc($code['icon']) ?>"></i> <?= esc($code['category']) ?></h4>
                                    <span
                                        class="severity-badge <?= esc(strtolower($code['severity'])) ?>"><?= esc($code['severity']) ?></span>
                                </div>
                                <ul class="code-rules">
                                    <?php foreach ($code['rules'] as $rule): ?>
                                        <li><i class="fas fa-chevron-right"></i> <?= esc($rule) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="code-points">
                                    <i class="fas fa-exclamation-circle"></i>
                                    Penalty: <strong><?= $code['points'] ?> points</strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

        </div>

    </main>

</div>
<!-- Bottom Nav -->
<?= $this->include('App\Modules\User\Views\partials\bottom_nav') ?>
<?= $this->endSection() ?>