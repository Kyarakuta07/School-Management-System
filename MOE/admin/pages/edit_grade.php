<?php

session_start();
include '../../connection.php'; 


if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}


if (!isset($_GET['id'])) {
    header("Location: manage_classes.php");
    exit();
}
$id_grade_to_edit = $_GET['id'];


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
    echo "Data nilai tidak ditemukan.";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Nilai Kelas - MOE Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet" />
    
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
    <link rel="stylesheet" type="text/css" href="../css/layout.css" />
    <link rel="stylesheet" type="text/css" href="../css/cards.css" />
</head>
<body class="open">
    <div class="container">
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
                <a href="manage_nethera.php">
                    <i class="uil uil-users-alt"></i>
                    <span>Manage Nethera</span>
                </a>
                <a href="manage_classes.php" class="active">
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
                <h1 class="main-h1">Edit Nilai Kelas</h1>
                <h2 class="main-h2">Mengubah nilai untuk: <?php echo htmlspecialchars($grade_data['nama_lengkap']); ?></h2>
            </header>
            
            <div class="card full-width-card">
                <header class="card-header">
                    <h3 class="card-h3">Edit Form Nilai</h3>
                </header>
                <div class="form-container">
                    <form action="proses_update_grade.php" method="POST">
                        <input type="hidden" name="id_grade" value="<?php echo $grade_data['id_grade']; ?>">

                        <div class="form-group">
                            <label for="class_name">Nama Kelas</label>
                            <input type="text" id="class_name" name="class_name" value="<?php echo htmlspecialchars($grade_data['class_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="english">Nilai English</label>
                            <input type="number" id="english" name="english" value="<?php echo htmlspecialchars($grade_data['english']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="herbology">Nilai Herbology</label>
                            <input type="number" id="herbology" name="herbology" value="<?php echo htmlspecialchars($grade_data['herbology']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="oceanology">Nilai Oceanology</label>
                            <input type="number" id="oceanology" name="oceanology" value="<?php echo htmlspecialchars($grade_data['oceanology']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="astronomy">Nilai Astronomy</label>
                            <input type="number" id="astronomy" name="astronomy" value="<?php echo htmlspecialchars($grade_data['astronomy']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="total_pp">Total PP</label>
                            <input type="number" id="total_pp" name="total_pp" value="<?php echo htmlspecialchars($grade_data['total_pp']); ?>" required>
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
