<?php

session_start();
include '../../connection.php'; 

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Class Schedule - MOE Admin</title>
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
                <h1 class="main-h1">Add New Schedule</h1>
                <h2 class="main-h2">Tambahkan jadwal kelas baru</h2>
            </header>
            
            <div class="card full-width-card">
                <header class="card-header">
                    <h3 class="card-h3">Schedule Form</h3>
                </header>
                <div class="form-container">
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
                            <input type="text" id="schedule_day" name="schedule_day" placeholder="Contoh: Senin" required>
                        </div>
                        <div class="form-group">
                            <label for="schedule_time">Waktu</label>
                            <input type="text" id="schedule_time" name="schedule_time" placeholder="Contoh: 19:00 WIB" required>
                        </div>
                         <div class="form-group">
                            <label for="class_image">Gambar Kelas (Opsional)</label>
                            <input type="file" id="class_image" name="class_image" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="class_description">Deskripsi Kelas (Opsional)</label>
                            <textarea id="class_description" name="class_description" rows="4"></textarea>
                        </div>
                        <button type="submit" class="btn-save">Simpan Jadwal</button>
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

