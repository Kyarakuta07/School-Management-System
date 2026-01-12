<?php
/**
 * Add Grade - MOE Admin Panel
 * Add new grade record for students
 * 
 * REFACTORED: Uses modular layout components
 * SECURITY FIX: Added CSRF protection
 */

require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';
include '../../config/connection.php';

if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['Vasiki', 'Hakaes'])) {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// Ambil daftar Nethera aktif (untuk dropdown student)
$query_nethera = "SELECT id_nethera, nama_lengkap FROM nethera WHERE status_akun = 'Aktif' ORDER BY nama_lengkap ASC";
$result_nethera = mysqli_query($conn, $query_nethera);

// Layout config
$pageTitle = 'Add Grade';
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
            <h1>Add New Grade Record</h1>
            <h2 class="main-h2">Input nilai kelas untuk anggota Nethera.</h2>
        </header>

        <div class="card full-width-card">
            <header class="card-header">
                <h3 class="card-h3">Grade Input Form</h3>
            </header>

            <div class="form-container">
                <div style="max-width: 650px; margin: 0 auto;">
                    <form action="proses_add_grade.php" method="POST">
                        <?php echo csrf_token_field(); ?>

                        <div class="form-group">
                            <label for="id_nethera">Anggota Nethera</label>
                            <select id="id_nethera" name="id_nethera" required>
                                <option value="" disabled selected>-- Pilih Anggota Aktif --</option>
                                <?php while ($nethera = mysqli_fetch_assoc($result_nethera)): ?>
                                    <option value="<?php echo $nethera['id_nethera']; ?>">
                                        <?php echo htmlspecialchars($nethera['nama_lengkap']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="class_name">Nama Kelas (Contoh: Periode 1)</label>
                            <input type="text" id="class_name" name="class_name"
                                placeholder="Contoh: Periode 1 | Mid-Term" required>
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
                                <label for="herbology">Herbology</label>
                                <input type="number" id="herbology" name="herbology" min="0" max="100" required>
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
                            <a href="manage_classes.php" class="btn-save"
                                style="background: none; border: 1px solid #777; color: #777; margin-right: 15px;">
                                Batal
                            </a>
                            <button type="submit" class="btn-save">
                                Tambah Nilai
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