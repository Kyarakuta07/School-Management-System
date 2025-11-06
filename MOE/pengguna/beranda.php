<?php
// Wajib ada di paling atas setiap halaman yang terproteksi
session_start();

// 1. MELINDUNGI HALAMAN:
// Cek apakah pengguna sudah login dan apakah rolenya adalah 'Nethera'
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Nethera') {
    // Jika belum login atau rolenya salah, tendang kembali ke halaman login
    header("Location: ../index.php?pesan=gagal");
    exit();
}

// Ambil nama pengguna dari session untuk disapa
$nama_pengguna = $_SESSION['nama_lengkap'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Odyssey Sanctuary</title>
    <link rel="stylesheet" href="../style.css"> </head>
<body>
    <div class="dashboard-container">
        <h1>Selamat Datang, <?php echo htmlspecialchars($nama_pengguna); ?>!</h1>
        <p>Ini adalah Dasbor Nethera Anda.</p>
        
        <hr>

        <h3>Menu Utama</h3>
        <nav>
            <a href="class.php">Class</a>
            <a href="pet.php">Pet</a>
            <a href="trapeza.php">Trapeza</a>
            <a href="punish.php">Punishment</a>
            <a href="staff.php">Staff</a>
        </nav>

        <hr>
        
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</body>
</html>