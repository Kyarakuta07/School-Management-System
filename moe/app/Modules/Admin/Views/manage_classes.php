<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<header class="top-header">
    <h1>Manage Classes</h1>
    <h2>Kelola data nilai dan jadwal kelas di Odyssey Sanctuary</h2>
</header>

<!-- Sanctuary Points Chart -->
<div class="card full-width-card" style="margin-bottom: 24px;">
    <header class="card-header">
        <h3 class="card-h3">Total Poin Prestasi per Sanctuary</h3>
    </header>
    <div id="sanctuaryChart"></div>
</div>

<!-- Schedule Management -->
<div class="card full-width-card" style="margin-bottom: 24px;">
    <header class="card-header">
        <h3 class="card-h3">Class Schedule Management</h3>
        <a href="<?= base_url('admin/schedule/add') ?>" class="btn-save" style="text-decoration: none;">Tambah Jadwal
            Baru</a>
    </header>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nama Kelas</th>
                    <th>Nama Hakaes</th>
                    <th>Jadwal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($allSchedules)): ?>
                    <?php foreach ($allSchedules as $schedule): ?>
                        <tr>
                            <td>
                                <?= esc($schedule['class_name']) ?>
                            </td>
                            <td>
                                <?= esc($schedule['hakaes_name']) ?>
                            </td>
                            <td>
                                <?= esc($schedule['schedule_day'] . ', ' . $schedule['schedule_time']) ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?= base_url('admin/schedule/edit/' . $schedule['id_schedule']) ?>"
                                        class="btn-edit" title="Edit">
                                        <i class="uil uil-edit"></i>
                                    </a>
                                    <form action="<?= base_url('admin/schedule/delete') ?>" method="POST" style="display:inline"
                                        onsubmit="return confirm('Hapus jadwal ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $schedule['id_schedule'] ?>">
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
                        <td colspan="4" style="text-align: center; padding: 20px;">Tidak ada data jadwal kelas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Grades Management -->
<div class="card full-width-card">
    <header class="card-header">
        <h3 class="card-h3">All Class Grades</h3>
        <div style="display: flex; gap: 10px; align-items: center;">
            <div class="search-container">
                <i class="uil uil-search"></i>
                <input type="search" id="gradeSearchInput" class="search-input"
                    placeholder="Cari nama, sanctuary, atau kelas...">
            </div>
            <a href="<?= base_url('admin/grades/add') ?>" class="btn-save"
                style="text-decoration: none; white-space: nowrap;">Tambah Nilai</a>
        </div>
    </header>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Sanctuary</th>
                    <th>Kelas</th>
                    <th>History</th>
                    <th>Pop Culture</th>
                    <th>Mythology</th>
                    <th>History of Egypt</th>
                    <th>Oceanology</th>
                    <th>Astronomy</th>
                    <th>Total PP</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="gradeTableBody">
                <?php if (!empty($allGrades)): ?>
                    <?php foreach ($allGrades as $grade): ?>
                        <tr>
                            <td>
                                <?= esc($grade['nama_lengkap']) ?>
                            </td>
                            <td><span class="sanctuary-badge">
                                    <?= esc($grade['nama_sanctuary'] ?? '-') ?>
                                </span></td>
                            <td>
                                <?= esc($grade['class_name']) ?>
                            </td>
                            <td>
                                <?= $grade['history'] ?>
                            </td>
                            <td>
                                <?= $grade['pop_culture'] ?>
                            </td>
                            <td>
                                <?= $grade['mythology'] ?>
                            </td>
                            <td>
                                <?= $grade['history_of_egypt'] ?>
                            </td>
                            <td>
                                <?= $grade['oceanology'] ?>
                            </td>
                            <td>
                                <?= $grade['astronomy'] ?>
                            </td>
                            <td><strong>
                                    <?= $grade['total_pp'] ?>
                                </strong></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?= base_url('admin/grades/edit/' . $grade['id_grade']) ?>" class="btn-edit"
                                        title="Edit">
                                        <i class="uil uil-edit"></i>
                                    </a>
                                    <form action="<?= base_url('admin/grades/delete') ?>" method="POST" style="display:inline"
                                        onsubmit="return confirm('Hapus nilai ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $grade['id_grade'] ?>">
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
                        <td colspan="11" style="text-align: center; padding: 20px;">Tidak ada data nilai.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    const CHART_LABELS = <?= json_encode($sanctuaryLabels) ?>;
    const CHART_POINTS = <?= json_encode($sanctuaryPoints) ?>;
</script>
<script src="<?= base_url('js/admin/manage_classes.js') ?>"></script>
<?= $this->endSection() ?>