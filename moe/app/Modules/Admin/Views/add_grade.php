<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<header class="top-header">
    <h1>Add New Grade Record</h1>
    <h2 class="main-h2">Input nilai kelas untuk anggota Nethera.</h2>
</header>

<div class="card full-width-card">
    <header class="card-header">
        <h3 class="card-h3">Grade Input Form</h3>
    </header>

    <div class="form-container">
        <div style="max-width: 650px; margin: 0 auto;">
            <form action="<?= base_url('admin/grades/store') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="id_nethera">Anggota Nethera</label>
                    <select id="id_nethera" name="id_nethera" required>
                        <option value="" disabled selected>-- Pilih Anggota Aktif --</option>
                        <?php foreach ($activeNethera as $nethera): ?>
                            <option value="<?= $nethera['id_nethera'] ?>">
                                <?= esc($nethera['nama_lengkap']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="class_name">Nama Kelas (Contoh: Periode 1)</label>
                    <input type="text" id="class_name" name="class_name" placeholder="Contoh: Periode 1 | Mid-Term"
                        required>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <h3 class="card-h3"
                        style="grid-column: 1 / -1; margin-bottom: 10px; color: #aaa; border-bottom: 1px solid #444;">
                        Input Nilai (0-100)</h3>

                    <div class="form-group">
                        <label for="history">History</label>
                        <input type="number" id="history" name="history" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="pop_culture">Pop Culture</label>
                        <input type="number" id="pop_culture" name="pop_culture" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="mythology">Mythology</label>
                        <input type="number" id="mythology" name="mythology" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="history_of_egypt">History of Egypt</label>
                        <input type="number" id="history_of_egypt" name="history_of_egypt" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="oceanology">Oceanology</label>
                        <input type="number" id="oceanology" name="oceanology" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="astronomy">Astronomy</label>
                        <input type="number" id="astronomy" name="astronomy" min="0" max="100" required>
                    </div>
                </div>

                <div style="margin-top: 40px; text-align: right;">
                    <a href="<?= base_url('admin/classes') ?>" class="btn-save"
                        style="background: none; border: 1px solid #777; color: #777; margin-right: 15px;">
                        Batal
                    </a>
                    <button type="submit" class="btn-save">Tambah Nilai</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>