<?php
require_once '../../includes/security_config.php';
session_start();
require_once '../../includes/csrf.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

include '../../connection.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// --- QUERY DATA ---
$query_all_grades = "SELECT n.nama_lengkap, n.username, s.nama_sanctuary, cg.*
                     FROM class_grades cg
                     JOIN nethera n ON cg.id_nethera = n.id_nethera
                     LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
                     ORDER BY cg.id_grade DESC";
$result_all_grades = mysqli_query($conn, $query_all_grades);

$query_chart = "SELECT s.nama_sanctuary, SUM(cg.total_pp) as total_points
                FROM class_grades cg
                JOIN nethera n ON cg.id_nethera = n.id_nethera
                JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
                GROUP BY s.nama_sanctuary
                ORDER BY total_points DESC";
$result_chart = mysqli_query($conn, $query_chart);

$sanctuary_labels = [];
$sanctuary_points = [];
while ($row = mysqli_fetch_assoc($result_chart)) {
    $sanctuary_labels[] = $row['nama_sanctuary'];
    $sanctuary_points[] = $row['total_points'];
}

$query_all_schedules = "SELECT * FROM class_schedule ORDER BY id_schedule ASC";
$result_all_schedules = mysqli_query($conn, $query_all_schedules);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - MOE Admin</title>

    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" type="text/css" href="../css/style.css" />
    <link rel="stylesheet" type="text/css" href="../css/cards.css" />
</head>

<body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <nav class="sidebar">
        <header class="sidebar-header">
            <button type="button" class="sidebar-burger" onclick="toggleSidebar()"></button>
            <img src="../../assets/landing/logo.png" class="sidebar-logo" alt="MOE Logo" />
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
                <a href="../../logout.php">
                    <i class="uil uil-signout"></i> <span>Logout</span>
                </a>
            </div>
        </nav>
    </nav>

    <main class="main-content">

        <header class="top-header">
            <h1>Manage Classes</h1>
            <h2>Kelola data nilai dan jadwal kelas di Odyssey Sanctuary</h2>
        </header>

        <div class="card full-width-card" style="margin-bottom: 24px;">
            <header class="card-header">
                <h3 class="card-h3">Total Poin Prestasi per Sanctuary</h3>
            </header>
            <div id="sanctuaryChart"></div>
        </div>

        <div class="card full-width-card" style="margin-bottom: 24px;">
            <header class="card-header">
                <h3 class="card-h3">Class Schedule Management</h3>
                <a href="add_schedule.php" class="btn-save" style="text-decoration: none;">Tambah Jadwal Baru</a>
            </header>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Kelas</th>
                            <th>Nama Hakaes</th>
                            <th>Jadwal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_all_schedules && mysqli_num_rows($result_all_schedules) > 0): ?>
                            <?php while ($schedule = mysqli_fetch_assoc($result_all_schedules)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($schedule['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['hakaes_name']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['schedule_day'] . ', ' . $schedule['schedule_time']); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_schedule.php?id=<?php echo $schedule['id_schedule']; ?>"
                                                class="btn-edit" title="Edit">
                                                <i class="uil uil-edit"></i>
                                            </a>
                                            <a href="delete_schedule.php?id=<?php echo $schedule['id_schedule']; ?>"
                                                class="btn-delete"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');"
                                                title="Delete">
                                                <i class="uil uil-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">Tidak ada data jadwal kelas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card full-width-card">
            <header class="card-header">
                <h3 class="card-h3">All Class Grades</h3>
                <div class="search-container">
                    <i class="uil uil-search"></i>
                    <input type="search" id="gradeSearchInput" class="search-input"
                        placeholder="Cari nama, sanctuary, atau kelas...">
                </div>
            </header>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>Sanctuary</th>
                            <th>Nama Kelas</th>
                            <th>English</th>
                            <th>Herbology</th>
                            <th>Oceanology</th>
                            <th>Astronomy</th>
                            <th>Total PP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTableBody">
                        <?php if ($result_all_grades && mysqli_num_rows($result_all_grades) > 0): ?>
                            <?php while ($grade = mysqli_fetch_assoc($result_all_grades)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['nama_sanctuary']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['english']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['herbology']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['oceanology']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['astronomy']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($grade['total_pp']); ?></strong></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_grade.php?id=<?php echo $grade['id_grade']; ?>" class="btn-edit"
                                                title="Edit">
                                                <i class="uil uil-edit"></i>
                                            </a>
                                            <a href="delete_grade.php?id=<?php echo $grade['id_grade']; ?>" class="btn-delete"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus nilai ini?');"
                                                title="Delete">
                                                <i class="uil uil-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 20px;">Tidak ada data nilai kelas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="../js/sidebar-toggle.js"></script>

    <script>
        const toggleSidebar = () => document.body.classList.toggle("open");

        document.addEventListener('DOMContentLoaded', function () {
            // --- 1. SETUP CHART (Total Poin Prestasi per Sanctuary) ---
            var options = {
                series: [{
                    name: 'Total Poin',
                    data: <?php echo json_encode($sanctuary_points); ?>
                }],
                chart: {
                    type: 'bar', height: 350, toolbar: { show: false },
                    background: 'transparent', // Penting untuk tema gelap
                },
                plotOptions: { bar: { borderRadius: 4, horizontal: false, distributed: true } },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: <?php echo json_encode($sanctuary_labels); ?>,
                    labels: { style: { colors: '#FFFFFF', fontSize: '12px' } }
                },
                yaxis: { labels: { style: { colors: '#8A8A8A' } } },
                grid: { borderColor: '#444444', strokeDashArray: 3 },
                tooltip: { theme: 'dark' },
                legend: { show: false },
            };
            var chart = new ApexCharts(document.querySelector("#sanctuaryChart"), options);
            chart.render();

            // --- 2. SETUP SEARCH GRADES (AJAX) ---
            const gradeSearchInput = document.getElementById('gradeSearchInput');
            const gradesTableBody = document.getElementById('gradesTableBody');

            const fetchGrades = (searchTerm) => {
                let xhr = new XMLHttpRequest();
                // Pastikan path ke file ajax_search_grades.php benar (sama folder)
                xhr.open('POST', 'ajax_search_grades.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (this.status == 200) {
                        gradesTableBody.innerHTML = this.responseText;
                    } else {
                        // Tampilkan error jika server gagal merespon
                        gradesTableBody.innerHTML = '<tr><td colspan="9" style="color:red; text-align:center;">SERVER ERROR (' + this.status + ')</td></tr>';
                    }
                }
                xhr.send('search=' + searchTerm);
            };

            if (gradeSearchInput && gradesTableBody) {
                gradeSearchInput.addEventListener('input', function () {
                    fetchGrades(this.value);
                });
                gradeSearchInput.addEventListener('search', function () {
                    if (this.value === '') {
                        fetchGrades('');
                    }
                });
            }
        });
    </script>
</body>

</html>