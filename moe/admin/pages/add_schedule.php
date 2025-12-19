<?php
/**
 * Add Schedule - MOE Admin Panel
 * Add new class schedule
 * 
 * REFACTORED: Uses modular layout components
 */

session_start();
include '../../config/connection.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// Layout config
$pageTitle = 'Add Class Schedule';
$currentPage = 'classes';
$cssPath = '../';
$basePath = '../';
$jsPath = '../';
$extraCss = ['css/edit_schedule.css'];
?>
<!DOCTYPE html>
<html lang="en">

<?php include '../layouts/components/_head.php'; ?>

<body>
    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <?php include '../layouts/components/_sidebar.php'; ?>

    <main class="main-content">

        <header class="top-header">
            <h1>Add New Schedule</h1>
            <h2 class="main-h2">Tambahkan jadwal kelas baru</h2>
        </header>

        <div class="card full-width-card">
            <header class="card-header">
                <h3 class="card-h3">Schedule Form</h3>
            </header>

            <div class="form-container">
                <div style="max-width: 600px; margin: 0 auto;">
                    <form action="proses_add_schedule.php" method="POST" enctype="multipart/form-data">

                        <div class="form-group">
                            <label for="class_name">Nama Kelas</label>
                            <input type="text" id="class_name" name="class_name" required>
                        </div>
                        <div class="form-group">
                            <label for="hakaes_name">Nama Hakaes</label>
                            <input type="text" id="hakaes_name" name="hakaes_name" required>
                        </div>
                        <div class="form-group">
                            <label for="schedule_day">Hari</label>
                            <input type="text" id="schedule_day" name="schedule_day" placeholder="Contoh: Senin"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="schedule_time">Waktu</label>
                            <input type="text" id="schedule_time" name="schedule_time" placeholder="Contoh: 19:00 WIB"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="class_image">Gambar Kelas (Opsional)</label>
                            <input type="file" id="class_image" name="class_image" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label for="class_description">Deskripsi Kelas (Opsional)</label>
                            <textarea id="class_description" name="class_description" rows="4"></textarea>
                        </div>

                        <div style="margin-top: 30px; text-align: right;">
                            <a href="manage_classes.php" class="btn-save"
                                style="background: none; border: 1px solid #777; color: #777; margin-right: 15px;">
                                Batal
                            </a>
                            <button type="submit" class="btn-save">
                                Simpan Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include '../layouts/components/_scripts.php'; ?>
    <script>
        const toggleSidebar = () => document.body.classList.toggle("open");
    </script>
</body>

</html>