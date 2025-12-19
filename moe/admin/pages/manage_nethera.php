<?php
/**
 * Manage Nethera - MOE Admin Panel
 * User management with search, filter, and CRUD operations
 * 
 * REFACTORED: Uses modular layout components
 */

require_once '../../core/security_config.php';
session_start();
require_once '../../core/csrf.php';

// Koneksi Database
include '../../config/connection.php';

// Cek Login & Role
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// Query Data Nethera
$query_all_nethera = "SELECT n.id_nethera, n.no_registrasi, n.nama_lengkap, n.username, n.noHP, s.nama_sanctuary, n.periode_masuk, n.status_akun
                      FROM nethera n
                      LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
                      ORDER BY n.id_nethera ASC";
$result_all_nethera = mysqli_query($conn, $query_all_nethera);

// Stats counts
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM nethera WHERE role = 'Nethera'");
$aktif_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM nethera WHERE status_akun = 'Aktif' AND role = 'Nethera'");
$hiatus_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM nethera WHERE status_akun = 'Hiatus' AND role = 'Nethera'");
$out_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM nethera WHERE status_akun = 'Out' AND role = 'Nethera'");

$total_count = mysqli_fetch_assoc($total_query)['total'];
$aktif_count = mysqli_fetch_assoc($aktif_query)['total'];
$hiatus_count = mysqli_fetch_assoc($hiatus_query)['total'];
$out_count = mysqli_fetch_assoc($out_query)['total'];

// Layout config
$pageTitle = 'Manage Nethera';
$currentPage = 'nethera';
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
            <h1>Manage Nethera</h1>
            <h2>Kelola data anggota terdaftar di Mediterranean Of Egypt</h2>
        </header>

        <!-- Stats Summary Cards -->
        <div class="stats-row">
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(218, 165, 32, 0.2); color: var(--gold);">
                    <i class="uil uil-users-alt"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $total_count; ?></span>
                    <span class="mini-stat-label">Total Nethera</span>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(50, 205, 50, 0.2); color: #32cd32;">
                    <i class="uil uil-check-circle"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $aktif_count; ?></span>
                    <span class="mini-stat-label">Aktif</span>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(255, 165, 0, 0.2); color: #ffa500;">
                    <i class="uil uil-pause-circle"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $hiatus_count; ?></span>
                    <span class="mini-stat-label">Hiatus</span>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background: rgba(255, 107, 107, 0.2); color: #ff6b6b;">
                    <i class="uil uil-times-circle"></i>
                </div>
                <div class="mini-stat-info">
                    <span class="mini-stat-value"><?php echo $out_count; ?></span>
                    <span class="mini-stat-label">Out</span>
                </div>
            </div>
        </div>

        <div class="card full-width-card">

            <div class="card-header card-header--flex">
                <h3 class="card-h3">
                    <i class="uil uil-list-ul"></i> All Registered Nethera
                </h3>

                <div class="table-controls">
                    <!-- Filter Dropdown -->
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Hiatus">Hiatus</option>
                        <option value="Out">Out</option>
                        <option value="Pending">Pending</option>
                    </select>

                    <!-- Search Box -->
                    <div class="search-container">
                        <i class="uil uil-search search-icon"></i>
                        <input type="search" id="searchInput" class="search-input"
                            placeholder="Search name, username, sanctuary...">
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="uil uil-tag-alt"></i> No. Reg</th>
                            <th><i class="uil uil-user"></i> Full Name</th>
                            <th><i class="uil uil-at"></i> Username</th>
                            <th><i class="uil uil-phone"></i> No. HP</th>
                            <th><i class="uil uil-building"></i> Sanctuary</th>
                            <th><i class="uil uil-calendar-alt"></i> Period</th>
                            <th><i class="uil uil-toggle-on"></i> Status</th>
                            <th><i class="uil uil-setting"></i> Actions</th>
                        </tr>
                    </thead>

                    <tbody id="netheraTableBody">
                        <?php if ($result_all_nethera && mysqli_num_rows($result_all_nethera) > 0): ?>
                            <?php while ($nethera = mysqli_fetch_assoc($result_all_nethera)): ?>
                                <tr data-status="<?php echo htmlspecialchars($nethera['status_akun']); ?>">
                                    <td>
                                        <span
                                            class="reg-badge"><?php echo htmlspecialchars($nethera['no_registrasi']); ?></span>
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($nethera['nama_lengkap'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($nethera['nama_lengkap']); ?></strong>
                                        </div>
                                    </td>
                                    <td><code
                                            class="username-code">@<?php echo htmlspecialchars($nethera['username']); ?></code>
                                    </td>
                                    <td><?php echo htmlspecialchars($nethera['noHP'] ?? '-'); ?></td>
                                    <td>
                                        <span
                                            class="sanctuary-badge"><?php echo htmlspecialchars($nethera['nama_sanctuary']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($nethera['periode_masuk']); ?></td>

                                    <td>
                                        <span class="status-badge status-<?php echo $nethera['status_akun']; ?>">
                                            <?php echo htmlspecialchars($nethera['status_akun']); ?>
                                        </span>
                                    </td>

                                    <td style="white-space: nowrap;">
                                        <div class="action-buttons">
                                            <a href="edit_nethera.php?id=<?php echo $nethera['id_nethera']; ?>" class="btn-edit"
                                                title="Edit">
                                                <i class="uil uil-edit"></i>
                                            </a>
                                            <button class="btn-delete" title="Delete"
                                                onclick="confirmDelete(<?php echo $nethera['id_nethera']; ?>)">
                                                <i class="uil uil-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <div class="empty-icon"><i class="uil uil-user-times"></i></div>
                                    <h4>No Nethera Found</h4>
                                    <p>Data anggota belum tersedia.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Hidden Delete Form with CSRF -->
        <form id="deleteForm" method="POST" action="delete_nethera.php" style="display: none;">
            <input type="hidden" name="id" id="deleteId" value="">
            <?php echo csrf_token_field(); ?>
        </form>

    </main>

    <?php include '../layouts/components/_scripts.php'; ?>
    <script>
        // --- SECURE DELETE FUNCTION (POST with CSRF) ---
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data Nethera ini? Aksi ini tidak dapat dibatalkan.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Fungsi untuk memastikan sidebar toggle tetap berfungsi
        const toggleSidebar = () => document.body.classList.toggle("open");

        // --- AJAX Search Logic ---
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const netheraTableBody = document.getElementById('netheraTableBody');

            if (searchInput && netheraTableBody) {
                const performSearch = () => {
                    const searchTerm = searchInput.value;

                    let xhr = new XMLHttpRequest();
                    xhr.open('POST', 'ajax_search_nethera.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                    xhr.onload = function () {
                        if (this.status === 200) {
                            netheraTableBody.innerHTML = this.responseText;
                            applyFilter();
                        } else {
                            netheraTableBody.innerHTML = '<tr><td colspan="8" style="color:red; text-align:center;">SERVER ERROR (' + this.status + ')</td></tr>';
                        }
                    };

                    xhr.send('search=' + searchTerm);
                };

                searchInput.addEventListener('input', performSearch);
                searchInput.addEventListener('search', performSearch);
            }

            // --- Status Filter Logic ---
            const statusFilter = document.getElementById('statusFilter');

            function applyFilter() {
                const filterValue = statusFilter ? statusFilter.value : '';
                const rows = document.querySelectorAll('#netheraTableBody tr[data-status]');

                rows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status');
                    if (filterValue === '' || rowStatus === filterValue) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            if (statusFilter) {
                statusFilter.addEventListener('change', applyFilter);
            }
        });
    </script>
</body>

</html>