<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<header class="top-header">
    <h1 class="main-h1">Edit Nilai Kelas</h1>
    <h2 class="main-h2">Mengubah nilai untuk:
        <?= esc($gradeData['nama_lengkap']) ?>
    </h2>
</header>

<div class="card full-width-card">
    <header class="card-header">
        <h3 class="card-h3">Edit Form Nilai</h3>
    </header>

    <div class="form-container">
        <div style="max-width: 600px; margin: 0 auto;">
            <form action="<?= base_url('admin/grades/update') ?>" method="POST">
                <input type="hidden" name="id_grade" value="<?= $gradeData['id_grade'] ?>">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="nama_anggota">Anggota Nethera</label>
                    <input type="text" id="nama_anggota" value="<?= esc($gradeData['nama_lengkap']) ?>" disabled>
                    <input type="hidden" name="id_nethera" value="<?= $gradeData['id_nethera'] ?>">
                </div>

                <div class="form-group">
                    <label for="class_name">Nama Kelas</label>
                    <input type="text" id="class_name" name="class_name" value="<?= esc($gradeData['class_name']) ?>"
                        required>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label for="history">Nilai History</label>
                        <input type="number" id="history" name="history" value="<?= esc($gradeData['history']) ?>"
                            min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="pop_culture">Nilai Pop Culture</label>
                        <input type="number" id="pop_culture" name="pop_culture"
                            value="<?= esc($gradeData['pop_culture'] ?? 0) ?>" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="mythology">Nilai Mythology</label>
                        <input type="number" id="mythology" name="mythology"
                            value="<?= esc($gradeData['mythology'] ?? 0) ?>" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="history_of_egypt">Nilai History of Egypt</label>
                        <input type="number" id="history_of_egypt" name="history_of_egypt"
                            value="<?= esc($gradeData['history_of_egypt'] ?? 0) ?>" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="oceanology">Nilai Oceanology</label>
                        <input type="number" id="oceanology" name="oceanology"
                            value="<?= esc($gradeData['oceanology']) ?>" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="astronomy">Nilai Astronomy</label>
                        <input type="number" id="astronomy" name="astronomy" value="<?= esc($gradeData['astronomy']) ?>"
                            min="0" max="100" required>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="total_pp">Total Poin Prestasi (PP)</label>
                        <input type="number" id="total_pp" name="total_pp" value="<?= esc($gradeData['total_pp']) ?>"
                            readonly style="background-color: #333; cursor: not-allowed;">
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: right;">
                    <a href="<?= base_url('admin/classes') ?>" class="btn-save"
                        style="background: none; border: 1px solid #777; color: #777; margin-right: 15px;">
                        Batal
                    </a>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>