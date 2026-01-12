<?php
/**
 * Edit Grade - MOE Admin Panel
 * Edit student grade records
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

if (!isset($_GET['id'])) {
    header("Location: manage_classes.php");
    exit();
}
$id_grade_to_edit = (int) $_GET['id'];

// Query untuk mengambil data nilai
$sql_grade = "SELECT cg.*, n.nama_lengkap 
              FROM class_grades cg
              JOIN nethera n ON cg.id_nethera = n.id_nethera
              WHERE cg.id_grade = ?";
$stmt_grade = mysqli_prepare($conn, $sql_grade);
mysqli_stmt_bind_param($stmt_grade, "i", $id_grade_to_edit);
mysqli_stmt_execute($stmt_grade);
$result_grade = mysqli_stmt_get_result($stmt_grade);
$grade_data = mysqli_fetch_assoc($result_grade);

if (!$grade_data) {
    header("Location: manage_classes.php?status=not_found");
    exit();
}

// Layout config
$pageTitle = 'Edit Nilai Kelas';
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
            <h1 class="main-h1">Edit Nilai Kelas</h1>
            <h2 class="main-h2">Mengubah nilai untuk: <?php echo htmlspecialchars($grade_data['nama_lengkap']); ?></h2>
        </header>

        <div class="card full-width-card">
            <header class="card-header">
                <h3 class="card-h3">Edit Form Nilai</h3>
            </header>

            <div class="form-container">
                <div style="max-width: 600px; margin: 0 auto;">
                    <form action="proses_update_grade.php" method="POST">
                        <input type="hidden" name="id_grade" value="<?php echo $grade_data['id_grade']; ?>">
                        <?php echo csrf_token_field(); ?>

                        <div class="form-group">
                            <label for="nama_anggota">Anggota Nethera</label>
                            <input type="text" id="nama_anggota"
                                value="<?php echo htmlspecialchars($grade_data['nama_lengkap']); ?>" disabled>
                            <input type="hidden" name="id_nethera" value="<?php echo $grade_data['id_nethera']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="class_name">Nama Kelas</label>
                            <input type="text" id="class_name" name="class_name"
                                value="<?php echo htmlspecialchars($grade_data['class_name']); ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">

                            <div class="form-group">
                                <label for="english">Nilai English</label>
                                <input type="number" id="english" name="english"
                                    value="<?php echo htmlspecialchars($grade_data['english']); ?>" min="0" max="100"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="herbology">Nilai Herbology</label>
                                <input type="number" id="herbology" name="herbology"
                                    value="<?php echo htmlspecialchars($grade_data['herbology']); ?>" min="0" max="100"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="oceanology">Nilai Oceanology</label>
                                <input type="number" id="oceanology" name="oceanology"
                                    value="<?php echo htmlspecialchars($grade_data['oceanology']); ?>" min="0" max="100"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="astronomy">Nilai Astronomy</label>
                                <input type="number" id="astronomy" name="astronomy"
                                    value="<?php echo htmlspecialchars($grade_data['astronomy']); ?>" min="0" max="100"
                                    required>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="total_pp">Total Poin Prestasi (PP)</label>
                                <input type="number" id="total_pp" name="total_pp"
                                    value="<?php echo htmlspecialchars($grade_data['total_pp']); ?>" required>
                            </div>
                        </div>

                        <div style="margin-top: 30px; text-align: right;">
                            <a href="manage_classes.php" class="btn-save"
                                style="background: none; border: 1px solid #777; color: #777; margin-right: 15px;">
                                Batal
                            </a>
                            <button type="submit" class="btn-save">Simpan Perubahan</button>
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