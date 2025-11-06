<?php

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include '../connection.php'; 

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../index.php?pesan=gagal");
    exit();
}




$result_total_nethera = mysqli_query($conn, "SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Aktif'");
$total_nethera = mysqli_fetch_assoc($result_total_nethera)['total'];

$result_pending = mysqli_query($conn, "SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Pending'");
$total_pending = mysqli_fetch_assoc($result_pending)['total'];

$query_latest_users = "SELECT nama_lengkap, status_akun, periode_masuk FROM nethera ORDER BY id_nethera DESC LIMIT 5";
$result_latest_users = mysqli_query($conn, $query_latest_users);

$query_sanctuary = "SELECT s.nama_sanctuary, COUNT(n.id_nethera) as jumlah 
                    FROM sanctuary s
                    LEFT JOIN nethera n ON s.id_sanctuary = n.id_sanctuary AND n.status_akun = 'Aktif'
                    GROUP BY s.nama_sanctuary ORDER BY s.id_sanctuary ASC";
$result_sanctuary = mysqli_query($conn, $query_sanctuary);
$sanctuary_chart_data = [];
while($row = mysqli_fetch_assoc($result_sanctuary)){
    $sanctuary_chart_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - MOE</title>
    
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <!-- 
    SKRIP PENCEGAHAN BFCACHE (BACK-FORWARD CACHE)
    Skrip ini memastikan halaman selalu dimuat ulang dari server saat pengguna
    menekan tombol "Back" di browser setelah logout. Ini melengkapi header PHP di atas.
    -->
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

    <nav class="sidebar">
      <div class="sidebar-inner">
        <header class="sidebar-header">
          <button type="button" class="sidebar-burger" onclick="toggleSidebar()"></button>
          <img src="../logo.png" class="sidebar-logo" alt="MOE Logo" />
        </header>
        <nav class="sidebar-menu">
            <a href="#" class="active">
                <i class="uil uil-create-dashboard"></i>
                <span>Dashboard</span>
            </a>
            <a href="pages/manage_nethera.php">
                <i class="uil uil-users-alt"></i>
                <span>Manage Nethera</span>
            </a>
            <a href="pages/manage_classes.php">
                <i class="uil uil-folder"></i>
                <span>Manage Classes</span>
            </a>
            <a href="#" class="has-border">
                <i class="uil uil-setting"></i>
                <span>Settings</span>
            </a>
             <a href="../logout.php">
                <i class="uil uil-signout"></i>
                <span>Logout</span>
            </a>
        </nav>
      </div>
    </nav>

    <main class="main">
        <header class="main-header">
            <h1 class="main-h1">Dashboard</h1>
            <h2 class="main-h2">Welcome back, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></h2>
        </header>
        <div class="main-cards-container">
            <div class="card card-headline">
                <h3 class="card-h3">Total Active Nethera</h3>
                <div class="card-headline-body">
                    <h2 class="card-h2"><?php echo $total_nethera; ?></h2>
                    <span class="card-headline-icon uil uil-users-alt"></span>
                </div>
            </div>
            <div class="card card-headline">
                <h3 class="card-h3">Pending Registrations</h3>
                <div class="card-headline-body">
                    <h2 class="card-h2"><?php echo $total_pending; ?></h2>
                    <span class="card-headline-icon uil uil-user-plus"></span>
                </div>
            </div>

            <div class="card card-list">
                <header class="card-header">
                    <h3 class="card-h3">Recent Registrations</h3>
                </header>
                <ul>
                    <?php while($user = mysqli_fetch_assoc($result_latest_users)): ?>
                    <li>
                        <span><?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $user['status_akun'])); ?>"><?php echo htmlspecialchars($user['status_akun']); ?></span>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <div class="card card-area-chart">
                <header class="card-header">
                    <h3 class="card-h3 no-bottom-margin">Active Members per Sanctuary</h3>
                </header>
                <div id="area-chart"></div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        const sanctuaryChartData = <?php echo json_encode($sanctuary_chart_data); ?>;
        const toggleSidebar = () => document.body.classList.toggle("open");
    </script>
    <script src="js/main.js"></script>
  </body>
</html>
