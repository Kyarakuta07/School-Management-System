<?php
/**
 * Manage Classes - MOE Admin Panel
 * Class grades and schedule management
 * 
 * REFACTORED: Uses modular layout components
 */

require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

include '../../config/connection.php';

// Allow Vasiki (admin) and Hakaes (teacher) to access
if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['Vasiki', 'Hakaes'])) {
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

// Layout config
$pageTitle = 'Manage Classes';
$currentPage = 'classes';
$cssPath = '../';
$basePath = '../';
$jsPath = '../';
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
                                            <form action="delete_schedule.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo $schedule['id_schedule']; ?>">
                                                <?php echo csrf_token_field(); ?>
                                                <button type="submit" class="btn-delete"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');"
                                                    title="Delete">
                                                    <i class="uil uil-trash-alt"></i>
                                                </button>
                                            </form>
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
                                            <form action="delete_grade.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo $grade['id_grade']; ?>">
                                                <?php echo csrf_token_field(); ?>
                                                <button type="submit" class="btn-delete"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus nilai ini?');"
                                                    title="Delete">
                                                    <i class="uil uil-trash-alt"></i>
                                                </button>
                                            </form>
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
    <?php include '../layouts/components/_scripts.php'; ?>

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
                    background: 'transparent',
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
                xhr.open('POST', 'ajax_search_grades.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (this.status == 200) {
                        gradesTableBody.innerHTML = this.responseText;
                    } else {
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