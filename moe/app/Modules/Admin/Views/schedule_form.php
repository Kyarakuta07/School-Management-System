<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<header class="top-header">
    <h1>
        <?= $isEdit ? 'Edit Schedule' : 'Add New Schedule' ?>
    </h1>
    <h2 class="main-h2">
        <?php if ($isEdit): ?>
            Mengubah data untuk kelas:
            <?= esc($scheduleData['class_name']) ?>
        <?php else: ?>
            Tambahkan jadwal kelas baru
        <?php endif; ?>
    </h2>
</header>

<div class="card full-width-card">
    <header class="card-header">
        <h3 class="card-h3">Schedule Form</h3>
    </header>

    <div class="form-container">
        <div style="max-width: 600px; margin: 0 auto;">
            <form action="<?= base_url($isEdit ? 'admin/schedule/update' : 'admin/schedule/store') ?>" method="POST"
                enctype="multipart/form-data">
                <?= csrf_field() ?>

                <?php if ($isEdit): ?>
                    <input type="hidden" name="id_schedule" value="<?= $scheduleData['id_schedule'] ?>">
                    <input type="hidden" name="old_image_path" value="<?= esc($scheduleData['class_image_url'] ?? '') ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="class_name">Nama Kelas</label>
                    <input type="text" id="class_name" name="class_name"
                        value="<?= esc($scheduleData['class_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="hakaes_name">Nama Hakaes</label>
                    <input type="text" id="hakaes_name" name="hakaes_name"
                        value="<?= esc($scheduleData['hakaes_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="schedule_day">Hari</label>
                    <input type="text" id="schedule_day" name="schedule_day" placeholder="Contoh: Senin"
                        value="<?= esc($scheduleData['schedule_day'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="schedule_time">Waktu</label>
                    <input type="text" id="schedule_time" name="schedule_time" placeholder="Contoh: 19:00 WIB"
                        value="<?= esc($scheduleData['schedule_time'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <?php if ($isEdit && !empty($scheduleData['class_image_url'])): ?>
                        <label>Gambar Saat Ini</label>
                        <img src="<?= base_url($scheduleData['class_image_url']) ?>" alt="Class Image"
                            style="max-width: 150px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #DAA520;">
                    <?php endif; ?>
                    <label for="class_image">Gambar Kelas (Opsional)</label>
                    <input type="file" id="class_image" name="class_image" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="class_description">Deskripsi Kelas (Opsional)</label>
                    <textarea id="class_description" name="class_description"
                        rows="4"><?= esc($scheduleData['class_description'] ?? '') ?></textarea>
                </div>

                <div style="margin-top: 30px; text-align: right;">
                    <a href="<?= base_url('admin/classes') ?>" class="btn-save"
                        style="background: none; border: 1px solid #777; color: #777; margin-right: 15px;">
                        Batal
                    </a>
                    <button type="submit" class="btn-save">
                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Jadwal' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>