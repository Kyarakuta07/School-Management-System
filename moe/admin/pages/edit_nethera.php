<?php
/**
 * Edit Nethera - MOE Admin Panel
 * Edit user data form
 * 
 * REFACTORED: Uses modular layout components
 * SECURITY FIX: Added CSRF protection
 */

require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';
include '../../config/connection.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_nethera.php");
    exit();
}

$id_nethera_to_edit = (int) $_GET['id'];

// --- QUERY NETHERA DATA ---
$sql_nethera = "SELECT * FROM nethera WHERE id_nethera = ?";
$stmt_nethera = mysqli_prepare($conn, $sql_nethera);
mysqli_stmt_bind_param($stmt_nethera, "i", $id_nethera_to_edit);
mysqli_stmt_execute($stmt_nethera);
$result_nethera = mysqli_stmt_get_result($stmt_nethera);
$nethera_data = mysqli_fetch_assoc($result_nethera);

if (!$nethera_data) {
    header("Location: manage_nethera.php?notif=not_found");
    exit();
}

// --- QUERY SANCTUARY DATA (untuk dropdown) ---
$sql_sanctuaries = "SELECT id_sanctuary, nama_sanctuary FROM sanctuary ORDER BY nama_sanctuary ASC";
$result_sanctuaries = mysqli_query($conn, $sql_sanctuaries);

// Layout config
$pageTitle = 'Edit Nethera';
$currentPage = 'nethera';
$cssPath = '../';
$basePath = '../';
$jsPath = '../';
$extraCss = ['css/edit_form.css'];
?>
<!DOCTYPE html>
<html lang="en">

<?php include '../layouts/components/_head.php'; ?>

<body class="open">
    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <?php include '../layouts/components/_sidebar.php'; ?>

    <main class="main-content">

        <header class="top-header">
            <h1 class="main-h1">Edit Nethera</h1>
            <h2 class="main-h2">Mengubah data untuk: <?php echo htmlspecialchars($nethera_data['nama_lengkap']); ?> (ID:
                <?php echo $nethera_data['no_registrasi']; ?>)
            </h2>
        </header>

        <div class="card full-width-card">
            <header class="card-header">
                <h3 class="card-h3">Edit Form</h3>
            </header>

            <div class="form-container">
                <div style="max-width: 600px; margin: 0 auto;">
                    <form action="proses_update_nethera.php" method="POST">
                        <input type="hidden" name="id_nethera" value="<?php echo $nethera_data['id_nethera']; ?>">
                        <?php echo csrf_token_field(); ?>

                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap"
                                value="<?php echo htmlspecialchars($nethera_data['nama_lengkap']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username"
                                value="<?php echo htmlspecialchars($nethera_data['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="noHP">No. HP / WhatsApp</label>
                            <input type="text" id="noHP" name="noHP"
                                value="<?php echo htmlspecialchars($nethera_data['noHP']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="no_registrasi">No. Registrasi</label>
                            <input type="text" id="no_registrasi" name="no_registrasi"
                                value="<?php echo htmlspecialchars($nethera_data['no_registrasi']); ?>"
                                placeholder="Akan digenerate otomatis saat status AKTIF" readonly
                                style="background-color: #e9e9e9; cursor: not-allowed;">
                            <small style="color: red;">*Dibuat otomatis oleh sistem saat Status diubah ke Aktif.</small>
                        </div>

                        <div class="form-group">
                            <label for="id_sanctuary">Sanctuary</label>
                            <select id="id_sanctuary" name="id_sanctuary">
                                <?php
                                mysqli_data_seek($result_sanctuaries, 0);
                                while ($sanctuary = mysqli_fetch_assoc($result_sanctuaries)):
                                    ?>
                                    <option value="<?php echo $sanctuary['id_sanctuary']; ?>" <?php if ($sanctuary['id_sanctuary'] == $nethera_data['id_sanctuary'])
                                           echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($sanctuary['nama_sanctuary']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="periode_masuk">Periode Masuk</label>
                            <input type="number" id="periode_masuk" name="periode_masuk"
                                value="<?php echo htmlspecialchars($nethera_data['periode_masuk']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status_akun">Status Akun</label>
                            <select id="status_akun" name="status_akun">
                                <?php
                                $statuses = ['Aktif', 'Pending', 'Hiatus', 'Out', 'Tidak Lulus'];
                                foreach ($statuses as $status):
                                    $selected = ($nethera_data['status_akun'] == $status) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $status; ?>" <?php echo $selected; ?>><?php echo $status; ?>
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
                            <a href="manage_nethera.php" class="btn-save"
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
    </main>

    <?php include '../layouts/components/_scripts.php'; ?>
    <script>
        const toggleSidebar = () => document.body.classList.toggle("open");
    </script>
</body>

</html>