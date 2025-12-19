<?php
/**
 * Vasiki Dashboard - MOE Admin Panel
 * Main admin dashboard with stats and charts
 * 
 * REFACTORED: Uses modular layout components
 */

require_once '../core/security_config.php';
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Cache control
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include '../config/connection.php';

// Cek Login & Role Vasiki
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../index.php?pesan=gagal");
    exit();
}

// --- DATA QUERY ---

// 1. Total Active
$result_total_nethera = mysqli_query($conn, "SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Aktif' AND role = 'Nethera'");
$total_nethera = mysqli_fetch_assoc($result_total_nethera)['total'];

// 2. Pending Registration
$result_pending = mysqli_query($conn, "SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Pending' AND role = 'Nethera'");
$total_pending = mysqli_fetch_assoc($result_pending)['total'];

// 3. Hiatus Count
$result_hiatus = mysqli_query($conn, "SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Hiatus' AND role = 'Nethera'");
$total_hiatus = mysqli_fetch_assoc($result_hiatus)['total'];

// 4. Out Count
$result_out = mysqli_query($conn, "SELECT COUNT(id_nethera) as total FROM nethera WHERE status_akun = 'Out' AND role = 'Nethera'");
$total_out = mysqli_fetch_assoc($result_out)['total'];

// 5. Latest Users with more info
$query_latest_users = "SELECT n.nama_lengkap, n.status_akun, n.periode_masuk, s.nama_sanctuary, n.created_at 
                       FROM nethera n 
                       LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
                       WHERE n.role = 'Nethera'
                       ORDER BY n.id_nethera DESC LIMIT 5";
$result_latest_users = mysqli_query($conn, $query_latest_users);

// 6. Chart Data
$query_sanctuary = "SELECT s.nama_sanctuary, COUNT(n.id_nethera) as jumlah 
                    FROM sanctuary s
                    LEFT JOIN nethera n ON s.id_sanctuary = n.id_sanctuary AND n.status_akun = 'Aktif' AND n.role = 'Nethera'
                    GROUP BY s.nama_sanctuary ORDER BY s.id_sanctuary ASC";
$result_sanctuary = mysqli_query($conn, $query_sanctuary);

$sanctuary_labels = [];
$sanctuary_values = [];

while ($row = mysqli_fetch_assoc($result_sanctuary)) {
    $sanctuary_labels[] = $row['nama_sanctuary'];
    $sanctuary_values[] = $row['jumlah'];
}

// 7. Total All Members
$total_all = $total_nethera + $total_pending + $total_hiatus + $total_out;

// Layout config
$pageTitle = 'Vasiki Dashboard';
$currentPage = 'dashboard';
$cssPath = '';
$basePath = '';
?>
<!DOCTYPE html>
<html lang="en">

<?php include 'layouts/components/_head.php'; ?>

<body>
    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <?php include 'layouts/components/_sidebar.php'; ?>

    <main class="main-content">

        <header class="top-header">
            <h1>Vasiki Dashboard</h1>
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></h2>
        </header>

        <!-- Pending Alert Banner -->
        <?php if ($total_pending > 0): ?>
            <div class="alert-banner alert-warning">
                <i class="uil uil-exclamation-triangle"></i>
                <div class="alert-content">
                    <strong><?php echo $total_pending; ?> Pending
                        Registration<?php echo $total_pending > 1 ? 's' : ''; ?></strong>
                    <span>Members menunggu verifikasi akun</span>
                </div>
                <a href="pages/manage_nethera.php" class="alert-action">Review Now <i class="uil uil-arrow-right"></i></a>
            </div>
        <?php endif; ?>

        <!-- Quick Actions Bar -->
        <div class="quick-actions">
            <a href="pages/manage_nethera.php" class="quick-action-btn">
                <i class="uil uil-users-alt"></i>
                <span>Manage Users</span>
            </a>
            <a href="pages/manage_classes.php" class="quick-action-btn">
                <i class="uil uil-book-open"></i>
                <span>View Classes</span>
            </a>
            <a href="pages/add_grade.php" class="quick-action-btn">
                <i class="uil uil-plus-circle"></i>
                <span>Add Grade</span>
            </a>
            <a href="pages/add_schedule.php" class="quick-action-btn">
                <i class="uil uil-calendar-alt"></i>
                <span>Add Schedule</span>
            </a>
        </div>

        <!-- Stats Row - 6 Cards -->
        <div class="stats-row stats-row--6">
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(218, 165, 32, 0.2); color: var(--gold);">
                    <i class="uil uil-users-alt"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $total_all; ?></span>
                    <span class="mini-stat-label">Total Members</span>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(50, 205, 50, 0.2); color: #32cd32;">
                    <i class="uil uil-check-circle"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $total_nethera; ?></span>
                    <span class="mini-stat-label">Active</span>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(255, 165, 0, 0.2); color: #ffa500;">
                    <i class="uil uil-clock"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $total_pending; ?></span>
                    <span class="mini-stat-label">Pending</span>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(100, 149, 237, 0.2); color: #6495ed;">
                    <i class="uil uil-pause-circle"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $total_hiatus; ?></span>
                    <span class="mini-stat-label">Hiatus</span>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(255, 107, 107, 0.2); color: #ff6b6b;">
                    <i class="uil uil-times-circle"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $total_out; ?></span>
                    <span class="mini-stat-label">Out</span>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(79, 172, 254, 0.2); color: #4facfe;">
                    <i class="uil uil-building"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo count($sanctuary_labels); ?></span>
                    <span class="mini-stat-label">Sanctuaries</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">

            <div class="card card-list">
                <div class="card-header card-header--flex">
                    <h3><i class="uil uil-user-plus"></i> Recent Registrations</h3>
                    <a href="pages/manage_nethera.php" class="view-all-link">
                        View All <i class="uil uil-arrow-right"></i>
                    </a>
                </div>

                <div class="user-list">
                    <?php
                    if (mysqli_num_rows($result_latest_users) > 0) {
                        mysqli_data_seek($result_latest_users, 0);
                        while ($user = mysqli_fetch_assoc($result_latest_users)):
                            $initial = strtoupper(substr($user['nama_lengkap'], 0, 1));
                            ?>
                            <div class="user-item user-item--enhanced">
                                <div class="user-avatar-small"><?php echo $initial; ?></div>
                                <div class="user-details">
                                    <span class="user-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
                                    <span
                                        class="user-sanctuary"><?php echo htmlspecialchars($user['nama_sanctuary'] ?? 'No Sanctuary'); ?></span>
                                </div>
                                <span class="status-badge status-<?php echo str_replace(' ', '', $user['status_akun']); ?>">
                                    <?php echo htmlspecialchars($user['status_akun']); ?>
                                </span>
                            </div>
                            <?php
                        endwhile;
                    } else {
                        echo '<p class="empty-message">No recent registrations.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="card card-chart">
                <div class="card-header">
                    <h3><i class="uil uil-chart-bar"></i> Active Members Distribution</h3>
                </div>
                <div id="area-chart"></div>
            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <?php
    $jsPath = '';
    include 'layouts/components/_scripts.php';
    ?>
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
                        colors: '#e0e0e0',
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