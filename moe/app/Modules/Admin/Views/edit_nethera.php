<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<header class="top-header">
    <h1 class="main-h1">Edit Nethera</h1>
    <h2 class="main-h2">Mengubah data untuk:
        <?= esc($netheraData['nama_lengkap']) ?> (ID:
        <?= $netheraData['no_registrasi'] ?>)
    </h2>
</header>

<div class="card full-width-card">
    <header class="card-header">
        <h3 class="card-h3">Edit Form</h3>
    </header>

    <div class="form-container">
        <div style="max-width: 600px; margin: 0 auto;">
            <form action="<?= base_url('admin/nethera/update') ?>" method="POST">
                <input type="hidden" name="id_nethera" value="<?= $netheraData['id_nethera'] ?>">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap"
                        value="<?= esc($netheraData['nama_lengkap']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= esc($netheraData['username']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="noHP">No. HP / WhatsApp</label>
                    <input type="text" id="noHP" name="noHP" value="<?= esc($netheraData['noHP']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="no_registrasi">No. Registrasi</label>
                    <input type="text" id="no_registrasi" name="no_registrasi"
                        value="<?= esc($netheraData['no_registrasi']) ?>"
                        placeholder="Akan digenerate otomatis saat status AKTIF" readonly
                        style="background-color: #e9e9e9; cursor: not-allowed;">
                    <small style="color: red;">*Dibuat otomatis oleh sistem saat Status diubah ke Aktif.</small>
                </div>

                <div class="form-group">
                    <label for="id_sanctuary">Sanctuary</label>
                    <select id="id_sanctuary" name="id_sanctuary">
                        <?php foreach ($sanctuaries as $sanctuary): ?>
                            <option value="<?= $sanctuary['id_sanctuary'] ?>"
                                <?= ($sanctuary['id_sanctuary'] == $netheraData['id_sanctuary']) ? 'selected' : '' ?>>
                                <?= esc($sanctuary['nama_sanctuary']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="periode_masuk">Periode Masuk</label>
                    <input type="number" id="periode_masuk" name="periode_masuk"
                        value="<?= esc($netheraData['periode_masuk']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="status_akun">Status Akun</label>
                    <select id="status_akun" name="status_akun">
                        <?php
                        $statuses = ['Aktif', 'Pending', 'Hiatus', 'Out'];
                        foreach ($statuses as $status):
                            $selected = ($netheraData['status_akun'] == $status) ? 'selected' : '';
                            ?>
                            <option value="<?= $status ?>" <?= $selected ?>>
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password baru..."
                        autocomplete="new-password">
                </div>

                <div style="margin-top: 30px; text-align: right;">
                    <a href="<?= base_url('admin/nethera') ?>" class="btn-save"
                        style="background: none; border: 1px solid #777; color: #777; margin-right: 15px;">
                        Batal
                    </a>
                    <button type="submit" class="btn-save">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>