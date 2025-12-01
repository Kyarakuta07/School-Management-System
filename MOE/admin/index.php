<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include '../connection.php'; 

// Cek Login & Role Vasiki
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../index.php?pesan=gagal");
    exit();
}

// --- DATA QUERY ---

// 1. Total Active
$result_total_nethera = mysqli_query($conn, "SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Aktif'");
$total_nethera = mysqli_fetch_assoc($result_total_nethera)['total'];

// 2. Pending Registration
$result_pending = mysqli_query($conn, "SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Pending'");
$total_pending = mysqli_fetch_assoc($result_pending)['total'];

// 3. Latest Users
$query_latest_users = "SELECT nama_lengkap, status_akun, periode_masuk FROM nethera ORDER BY id_nethera DESC LIMIT 5";
$result_latest_users = mysqli_query($conn, $query_latest_users);

// 4. Chart Data
$query_sanctuary = "SELECT s.nama_sanctuary, COUNT(n.id_nethera) as jumlah 
                    FROM sanctuary s
                    LEFT JOIN nethera n ON s.id_sanctuary = n.id_sanctuary AND n.status_akun = 'Aktif'
                    GROUP BY s.nama_sanctuary ORDER BY s.id_sanctuary ASC";
$result_sanctuary = mysqli_query($conn, $query_sanctuary);

$sanctuary_labels = [];
$sanctuary_values = [];

while($row = mysqli_fetch_assoc($result_sanctuary)){
    $sanctuary_labels[] = $row['nama_sanctuary'];
    $sanctuary_values[] = $row['jumlah'];
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vasiki Dashboard - MOE</title>
    
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="css/style.css" />
    
  </head>
  <body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <nav class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/landing/logo.png" class="sidebar-logo" alt="Logo" />
            <div class="brand-name">MOE<br>Admin</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="active">
                <i class="uil uil-create-dashboard"></i> <span>Dashboard</span>
            </a>
            <a href="pages/manage_nethera.php">
                <i class="uil uil-users-alt"></i> <span>Manage Nethera</span>
            </a>
            <a href="pages/manage_classes.php">
                <i class="uil uil-book-open"></i> <span>Manage Classes</span>
            </a>
            <a href="#">
                <i class="uil uil-setting"></i> <span>Settings</span>
            </a>
            
            <div class="menu-bottom">
                <a href="../logout.php">
                    <i class="uil uil-signout"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        
        <header class="top-header">
            <h1>Vasiki Dashboard</h1>
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></h2>
        </header>

        <div class="dashboard-grid">
            
            <div class="card card-stat">
                <div class="stat-title">Total Active Nethera</div>
                <div class="stat-value"><?php echo $total_nethera; ?></div>
                <i class="uil uil-users-alt stat-icon"></i>
            </div>

            <div class="card card-stat" style="border-bottom-color: #ff6b6b;">
                <div class="stat-title">Pending Requests</div>
                <div class="stat-value"><?php echo $total_pending; ?></div>
                <i class="uil uil-user-plus stat-icon"></i>
            </div>
            
            <div class="card card-stat" style="border-bottom-color: #4facfe;">
                <div class="stat-title">Sanctuaries</div>
                <div class="stat-value"><?php echo count($sanctuary_labels); ?></div>
                <i class="uil uil-building stat-icon"></i>
            </div>

             <div class="card card-stat" style="border-bottom-color: #00fa9a;">
                <div class="stat-title">System Status</div>
                <div class="stat-value" style="font-size: 1.5rem; margin-top: 10px;">ONLINE</div>
                <i class="uil uil-server stat-icon"></i>
            </div>
        </div>

<div class="dashboard-grid">
            
            <div class="card card-list">
                
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Recent Registrations</h3>
                    <a href="pages/manage_nethera.php" style="font-size: 0.8rem; color: #DAA520; text-decoration: none;">
                        View All <i class="uil uil-arrow-right"></i>
                    </a>
                </div>

                <div class="user-list">
                    <?php 
                    // Pastikan pointer data di-reset sebelum loop
                    if(mysqli_num_rows($result_latest_users) > 0) {
                        mysqli_data_seek($result_latest_users, 0); 
                        while($user = mysqli_fetch_assoc($result_latest_users)): 
                    ?>
                        <div class="user-item">
                            <span class="user-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">
                                <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                            </span>
                            
                            <span class="status-badge status-<?php echo str_replace(' ', '', $user['status_akun']); ?>">
                                <?php echo htmlspecialchars($user['status_akun']); ?>
                            </span>
                        </div>
                    <?php 
                        endwhile; 
                    } else {
                        echo '<p style="text-align:center; color:#aaa; margin-top:20px;">No recent registrations.</p>';
                    }
                    ?>
                </div>
            </div> <div class="card card-chart">
                <div class="card-header">
                    <h3>Active Members Distribution</h3>
                </div>
                <div id="area-chart"></div>
            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Data dari PHP
        const labels = <?php echo json_encode($sanctuary_labels); ?>;
        const data = <?php echo json_encode($sanctuary_values); ?>;

        // ApexChart Config Dark Theme
        var options = {
            series: [{
                name: 'Members',
                data: data
            }],
            chart: {
                type: 'area',
                height: 320,
                fontFamily: 'Lato, sans-serif',
                background: 'transparent',
                toolbar: { show: false }
            },
            colors: ['#DAA520'], // Warna Emas
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.1,
                    stops: [0, 90, 100],
                    colorStops: [
                        { offset: 0, color: '#DAA520', opacity: 0.5 },
                        { offset: 100, color: '#DAA520', opacity: 0 }
                    ]
                }
            },
            theme: { mode: 'dark' }, 
            xaxis: {
    categories: labels,
    labels: { 
        style: { 
            colors: '#e0e0e0', /* Ubah jadi putih terang/abu terang */
            fontSize: '12px',
            fontFamily: 'Lato, sans-serif'
        } 
    },
    axisBorder: { show: false },
    axisTicks: { show: false }
},
            yaxis: {
                labels: { style: { colors: '#aaa' } }
            },
            grid: {
                borderColor: '#333',
                strokeDashArray: 4,
            }
        };

        var chart = new ApexCharts(document.querySelector("#area-chart"), options);
        chart.render();
    </script>
  </body>
</html>