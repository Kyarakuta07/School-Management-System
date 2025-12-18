<?php
// Wajib ada di paling atas
session_start();
include '../../config/connection.php'; 

// Proteksi Halaman
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// Ambil ID dari URL
if (!isset($_GET['id'])) {
    header("Location: manage_classes.php");
    exit();
}
$id_schedule_to_edit = $_GET['id'];

// Query untuk mengambil data jadwal yang akan diedit
$sql = "SELECT * FROM class_schedule WHERE id_schedule = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_schedule_to_edit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$schedule_data = mysqli_fetch_assoc($result);

if (!$schedule_data) {
    die("Data jadwal tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Class Schedule - MOE Admin</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap" rel="stylesheet" />
    
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
    <link rel="stylesheet" type="text/css" href="../css/cards.css" />
    <link rel="stylesheet" type="text/css" href="../css/edit_schedule.css" />
</head>
<body class="open">

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <nav class="sidebar">
        <header class="sidebar-header">
            <button type="button" class="sidebar-burger" onclick="toggleSidebar()"></button>
            <img src="../../assets/landing/logo.png" class="sidebar-logo" alt="MOE Logo"/>
            <div class="brand-name">MOE<br>Admin</div>
        </header>
        <nav class="sidebar-menu">
            <a href="../index.php">
                <i class="uil uil-create-dashboard"></i> <span>Dashboard</span>
            </a>
            <a href="manage_nethera.php">
                <i class="uil uil-users-alt"></i> <span>Manage Nethera</span>
            </a>
            <a href="manage_classes.php" class="active">
                <i class="uil uil-book-open"></i> <span>Manage Classes</span>
            </a>
            <a href="#">
                <i class="uil uil-setting"></i> <span>Settings</span>
            </a>
            <div class="menu-bottom">
                <a href="../../auth/handlers/logout.php">
                    <i class="uil uil-signout"></i> <span>Logout</span>
                </a>
            </div>
        </nav>
    </nav>

    <main class="main-content">
        
        <header class="top-header">
            <h1>Edit Schedule</h1>
            <h2 class="main-h2">Mengubah data untuk kelas: <?php echo htmlspecialchars($schedule_data['class_name']); ?></h2>
        </header>
        
        <div class="card full-width-card">
            <header class="card-header">
                <h3 class="card-h3">Schedule Form</h3>
            </header>
            
            <div class="form-container">
                <div style="max-width: 600px; margin: 0 auto;"> 
                    <form action="proses_update_schedule.php" method="POST" enctype="multipart/form-data">
                        
                        <input type="hidden" name="id_schedule" value="<?php echo $schedule_data['id_schedule']; ?>">
                        <input type="hidden" name="old_image_path" value="<?php echo htmlspecialchars($schedule_data['class_image_url']); ?>">

                        <div class="form-group">
                            <label for="class_name">Nama Kelas</label>
                            <input type="text" id="class_name" name="class_name" value="<?php echo htmlspecialchars($schedule_data['class_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hakaes_name">Nama Hakaes</label>
                            <input type="text" id="hakaes_name" name="hakaes_name" value="<?php echo htmlspecialchars($schedule_data['hakaes_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedule_day">Hari</label>
                            <input type="text" id="schedule_day" name="schedule_day" value="<?php echo htmlspecialchars($schedule_data['schedule_day']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedule_time">Waktu</label>
                            <input type="text" id="schedule_time" name="schedule_time" value="<?php echo htmlspecialchars($schedule_data['schedule_time']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Gambar Saat Ini</label>
                            <?php if (!empty($schedule_data['class_image_url'])): ?>
                                <img src="../../<?php echo htmlspecialchars($schedule_data['class_image_url']); ?>" alt="Class Image" style="max-width: 150px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #DAA520;">
                            <?php else: ?>
                                <p style="color: #aaa; font-size: 0.9rem;">Tidak ada gambar terlampir.</p>
                            <?php endif; ?>
                            <input type="file" id="class_image" name="class_image" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label for="class_description">Deskripsi Kelas (Opsional)</label>
                            <textarea id="class_description" name="class_description" rows="4"><?php echo htmlspecialchars($schedule_data['class_description']); ?></textarea>
                        </div>
                        
                        <div style="margin-top: 40px; text-align: right;"> 
                            <a href="manage_classes.php" class="btn-save" style="background: none; border: 1px solid #777; color: #777; margin-right: 15px;">
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

    <script>
        const toggleSidebar = () => document.body.classList.toggle("open");
    </script>
</body>
</html>