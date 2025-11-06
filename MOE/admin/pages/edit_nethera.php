<?php

session_start();
include '../../connection.php'; 


if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}


if (!isset($_GET['id'])) {

    header("Location: manage_nethera.php");
    exit();
}
$id_nethera_to_edit = $_GET['id'];


$sql_nethera = "SELECT * FROM nethera WHERE id_nethera = ?";
$stmt_nethera = mysqli_prepare($conn, $sql_nethera);
mysqli_stmt_bind_param($stmt_nethera, "i", $id_nethera_to_edit);
mysqli_stmt_execute($stmt_nethera);
$result_nethera = mysqli_stmt_get_result($stmt_nethera);
$nethera_data = mysqli_fetch_assoc($result_nethera);

if (!$nethera_data) {
    echo "Data Nethera tidak ditemukan.";
    exit();
}

$sql_sanctuaries = "SELECT * FROM sanctuary ORDER BY nama_sanctuary ASC";
$result_sanctuaries = mysqli_query($conn, $sql_sanctuaries);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Nethera - MOE Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet" />
    
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
    <link rel="stylesheet" type="text/css" href="../css/layout.css" />
    <link rel="stylesheet" type="text/css" href="../css/cards.css" />
</head>
<body class="open">
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <header class="sidebar-header">
                <button type="button" class="sidebar-burger" onclick="toggleSidebar()"></button>
                <img src="../../logo.png" class="sidebar-logo" alt="MOE Logo"/>
            </header>
            <nav class="sidebar-menu">
                <a href="../index.php">
                    <i class="uil uil-create-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_nethera.php" class="active">
                    <i class="uil uil-users-alt"></i>
                    <span>Manage Nethera</span>
                </a>
                <a href="#">
                    <i class="uil uil-folder"></i>
                    <span>Manage Classes</span>
                </a>
                <a href="#">
                    <i class="uil uil-setting"></i>
                    <span>Settings</span>
                </a>
                <a href="../../logout.php" class="has-border">
                    <i class="uil uil-signout"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <main class="main">
            <header class="main-header">
                <h1 class="main-h1">Edit Nethera</h1>
                <h2 class="main-h2">Mengubah data untuk: <?php echo htmlspecialchars($nethera_data['nama_lengkap']); ?></h2>
            </header>
            
            <div class="card full-width-card">
                <header class="card-header">
                    <h3 class="card-h3">Edit Form</h3>
                </header>
                <div class="form-container">
                    <form action="proses_update_nethera.php" method="POST">
                        <input type="hidden" name="id_nethera" value="<?php echo $nethera_data['id_nethera']; ?>">

                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nethera_data['nama_lengkap']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="nickname">Nickname</label>
                            <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($nethera_data['nickname']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="no_registrasi">No. Registrasi</label>
                            <input type="text" id="no_registrasi" name="no_registrasi" value="<?php echo htmlspecialchars($nethera_data['no_registrasi']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="id_sanctuary">Sanctuary</label>
                            <select id="id_sanctuary" name="id_sanctuary">
                                <?php while($sanctuary = mysqli_fetch_assoc($result_sanctuaries)): ?>
                                    <option value="<?php echo $sanctuary['id_sanctuary']; ?>" <?php if($sanctuary['id_sanctuary'] == $nethera_data['id_sanctuary']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($sanctuary['nama_sanctuary']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="periode_masuk">Periode Masuk</label>
                            <input type="number" id="periode_masuk" name="periode_masuk" value="<?php echo htmlspecialchars($nethera_data['periode_masuk']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status_akun">Status Akun</label>
                            <select id="status_akun" name="status_akun">
                                <option value="Aktif" <?php if($nethera_data['status_akun'] == 'Aktif') echo 'selected'; ?>>Aktif</option>
                                <option value="Hiatus" <?php if($nethera_data['status_akun'] == 'Hiatus') echo 'selected'; ?>>Hiatus</option>
                                <option value="Out" <?php if($nethera_data['status_akun'] == 'Out') echo 'selected'; ?>>Out</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-save">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        const toggleSidebar = () => document.body.classList.toggle("open");
    </script>
</body>
</html>

