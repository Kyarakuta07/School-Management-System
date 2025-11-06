<?php

session_start();

include '../../connection.php'; 


if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Vasiki') {

    header("Location: ../../index.php?pesan=gagal");
    exit();
}


$query_all_nethera = "SELECT n.id_nethera, n.no_registrasi, n.nama_lengkap, n.nickname, s.nama_sanctuary, n.periode_masuk, n.status_akun
                      FROM nethera n
                      LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
                      ORDER BY n.id_nethera ASC";
$result_all_nethera = mysqli_query($conn, $query_all_nethera);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Nethera - MOE Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet" />
    
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
    <link rel="stylesheet" type="text/css" href="../css/layout.css" />
    <link rel="stylesheet" type="text/css" href="../css/cards.css" />

    <style>
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-container {
            position: relative;
        }
        .search-input {
            width: 250px;
            padding: 8px 12px 8px 35px; 
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 0.9rem;
        }
        .search-input:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        .search-container i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--subtle-text-color);
        }
    </style>
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
                <a href="manage_nethera.php" class="active">
                    <i class="uil uil-users-alt"></i>
                    <span>Manage Nethera</span>
                </a>
                <a href="manage_classes.php">
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
                <h1 class="main-h1">Manage Nethera</h1>
                <h2 class="main-h2">Kelola semua anggota terdaftar di Mediterranean Of Egypt</h2>
            </header>
            
            <div class="card full-width-card">
                <header class="card-header">
                    <h3 class="card-h3">All Registered Nethera</h3>

                    <div class="search-container">
                        <i class="uil uil-search"></i>
                        <input type="search" id="searchInput" class="search-input" placeholder="Cari nama atau sanctuary...">
                    </div>
                </header>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Registrasi</th>
                                <th>Nama Lengkap</th>
                                <th>Nickname</th>
                                <th>Sanctuary</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody id="netheraTableBody">
                            <?php if($result_all_nethera && mysqli_num_rows($result_all_nethera) > 0): ?>
                                <?php while($nethera = mysqli_fetch_assoc($result_all_nethera)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($nethera['no_registrasi']); ?></td>
                                    <td><?php echo htmlspecialchars($nethera['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($nethera['nickname']); ?></td>
                                    <td><?php echo htmlspecialchars($nethera['nama_sanctuary']); ?></td>
                                    <td><?php echo htmlspecialchars($nethera['periode_masuk']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $nethera['status_akun'])); ?>">
                                            <?php echo htmlspecialchars($nethera['status_akun']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">

                                        <a href="edit_nethera.php?id=<?php echo $nethera['id_nethera']; ?>" class="btn-edit">
                                        <i class="uil uil-edit"></i>
                                        </a>
                                        <button class="btn-view"><i class="uil uil-eye"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">Tidak ada data Nethera.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const toggleSidebar = () => document.body.classList.toggle("open");


        document.addEventListener('DOMContentLoaded', function() {

            const searchInput = document.getElementById('searchInput');


            if (searchInput) {

                const performSearch = () => {
                    let searchTerm = searchInput.value;

                    let xhr = new XMLHttpRequest();
                    xhr.open('POST', 'ajax_search_nethera.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    
                    xhr.onload = function() {
                        if (this.status == 200) {
                            document.getElementById('netheraTableBody').innerHTML = this.responseText;
                        }
                    }
                    
                    xhr.send('search=' + searchTerm);
                };


                searchInput.addEventListener('keyup', performSearch);

                searchInput.addEventListener('search', performSearch);
            }
        });
    </script>
</body>
</html>

