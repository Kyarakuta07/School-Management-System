<?php
require_once '../../includes/security_config.php';
session_start();
require_once '../../includes/csrf.php';

// Koneksi Database (Naik 2 folder)
include '../../connection.php'; 

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Nethera - MOE Admin</title>
    
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/cards.css" />
</head>
<body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <nav class="sidebar">
        <div class="sidebar-header">
            <img src="../../assets/landing/logo.png" class="sidebar-logo" alt="Logo" />
            <div class="brand-name">MOE<br>Admin</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="../index.php">
                <i class="uil uil-create-dashboard"></i> <span>Dashboard</span>
            </a>
            <a href="manage_nethera.php" class="active">
                <i class="uil uil-users-alt"></i> <span>Manage Nethera</span>
            </a>
            <a href="manage_classes.php">
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
        </div>
    </nav>

    <main class="main-content">
        
        <header class="top-header">
            <h1>Manage Nethera</h1>
            <h2>Kelola data anggota terdaftar di Mediterranean Of Egypt</h2>
        </header>
        
        <div class="card full-width-card">
            
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>All Registered Nethera</h3>

<div class="search-container">
    <input type="search" id="searchInput" class="search-input" placeholder="Search name or sanctuary...">
    <i class="uil uil-search"></i>
</div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No. Reg</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>No. HP</th>
                            <th>Sanctuary</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="netheraTableBody">
                        <?php if($result_all_nethera && mysqli_num_rows($result_all_nethera) > 0): ?>
                            <?php while($nethera = mysqli_fetch_assoc($result_all_nethera)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($nethera['no_registrasi']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($nethera['nama_lengkap']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($nethera['username']); ?></td>
                                <td><?php echo htmlspecialchars($nethera['noHP']); ?></td>
                                <td><?php echo htmlspecialchars($nethera['nama_sanctuary']); ?></td>
                                <td><?php echo htmlspecialchars($nethera['periode_masuk']); ?></td>
                                
                                <td>
                                    <span class="status-badge status-<?php echo $nethera['status_akun']; ?>">
                                        <?php echo htmlspecialchars($nethera['status_akun']); ?>
                                    </span>
                                </td>
                                
<td style="white-space: nowrap;">
    <div class="action-buttons">
        <a href="edit_nethera.php?id=<?php echo $nethera['id_nethera']; ?>" class="btn-edit" title="Edit">
            <i class="uil uil-edit"></i>
        </a>
        <button class="btn-delete" title="Delete" onclick="confirmDelete(<?php echo $nethera['id_nethera']; ?>)">
            <i class="uil uil-trash-alt"></i>
        </button>
    </div>
</td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #aaa;">
                                    No data available.
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

    <script src="../js/sidebar-toggle.js"></script>
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
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const netheraTableBody = document.getElementById('netheraTableBody');

            if (searchInput && netheraTableBody) {
                const performSearch = () => {
                    // Ambil nilai input
                    const searchTerm = searchInput.value;
                    
                    let xhr = new XMLHttpRequest();
                    // PATH AJAX: Naik satu level ke admin/ajax_search_nethera.php
                    xhr.open('POST', 'ajax_search_nethera.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    
                    xhr.onload = function() {
                        if (this.status === 200) {
                            // Update isi tabel
                            netheraTableBody.innerHTML = this.responseText;
                        } else {
                            // Tampilkan error jika server gagal merespon
                            netheraTableBody.innerHTML = '<tr><td colspan="7" style="color:red; text-align:center;">SERVER ERROR (' + this.status + ')</td></tr>';
                        }
                    };
                    
                    // Kirim data
                    xhr.send('search=' + searchTerm);
                };

                // PENTING: Gunakan event 'input' untuk deteksi ketikan secara real-time
                searchInput.addEventListener('input', performSearch);
                searchInput.addEventListener('search', performSearch); // Untuk deteksi saat tombol X diklik
            }
        });
    </script>
</body>
</html>