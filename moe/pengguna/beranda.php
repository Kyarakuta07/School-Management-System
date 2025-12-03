<?php
session_start();
include '../connection.php';

// 1. CEK AUTENTIKASI DASAR
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Nethera') {
    header("Location: ../index.php?pesan=gagal_akses");
    exit();
}

$id_user = $_SESSION['id_nethera'];

// 2. QUERY: CEK STATUS DAN AMBIL NAMA SANCTUARY (Security dan Title Data)
$sql_info = "SELECT n.status_akun, s.nama_sanctuary
             FROM nethera n
             JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
             WHERE n.id_nethera = ?";
             
$stmt_info = mysqli_prepare($conn, $sql_info);

// --- PERBAIKAN KRITIS: CHECK JIKA QUERY GAGAL ---
if (!$stmt_info) {
    die("Error Database. Hubungi Admin: " . mysqli_error($conn));
}
// --- END PERBAIKAN KRITIS ---

mysqli_stmt_bind_param($stmt_info, "i", $id_user);
mysqli_stmt_execute($stmt_info);
$result_info = mysqli_stmt_get_result($stmt_info);
$user_info_data = mysqli_fetch_assoc($result_info);
mysqli_stmt_close($stmt_info);

// 3. PEMBERSIHAN DATA DAN ENFORCEMENT
$user_status = trim($user_info_data['status_akun']);
$sanctuary_name = htmlspecialchars($user_info_data['nama_sanctuary']);
$nama_pengguna = htmlspecialchars($_SESSION['nama_lengkap']);

// ENFORCEMENT: Cek status harus SAMA PERSIS dengan string 'Aktif'
if ($user_status !== 'Aktif') {
    session_destroy();
    
    $redirect_message = 'access_denied';
    if ($user_status === 'Pending') {
        $redirect_message = 'pending_approval';
    }
    
    header("Location: ../index.php?pesan=" . $redirect_message);
    exit();
}

// 4. Data Funfact (Perlu di-fetch lagi secara terpisah jika ingin ditampilkan)
// Karena kita menghapus logic stats, kita harus memastikan funfact ada
$fun_fact_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fun_fact FROM nethera WHERE id_nethera = $id_user"));
$fun_fact = htmlspecialchars($fun_fact_data['fun_fact'] ?? 'Belum ada funfact.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Beranda - <?php echo htmlspecialchars($sanctuary_name); ?> Sanctuary</title>
    
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="../assets/css/landing-style.css" />
    <link rel="stylesheet" href="css/beranda_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <div class="main-dashboard-wrapper">
        
        <header class="top-user-header">
            <h1 class="main-h1 cinzel-title">NETHARA COMMAND HUB</h1>
            <p class="main-h2">Anda adalah anggota dari <?php echo htmlspecialchars($sanctuary_name); ?> Sanctuary.</p>
        </header>

        <nav class="top-nav-menu">
            <a href="class.php" class="nav-btn"><i class="fa-solid fa-book-open"></i> Class</a>
            <a href="pet.php" class="nav-btn"><i class="fa-solid fa-paw"></i> Pet</a>
            <a href="trapeza.php" class="nav-btn"><i class="fa-solid fa-credit-card"></i> Trapeza</a>
            <a href="punish.php" class="nav-btn"><i class="fa-solid fa-gavel"></i> Punishment</a>
            <a href="staff.php" class="nav-btn"><i class="fa-solid fa-users"></i> Staff</a>
            <a href="../logout.php" class="logout-btn-header"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="profile-main-grid">
            
            <section class="profile-sidebar-panel">
                
                <div class="profile-avatar-box">
                    <img src="../assets/placeholder.png" alt="Avatar" class="profile-avatar-lg">
                    <h2 class="user-name-title"><?php echo $nama_pengguna; ?></h2>
                    <p class="profile-link">My Profile</p>
                </div>

                <div class="profile-card funfact-card">
                    <h3 class="card-title">MY FUNFACT</h3>
                    <p class="card-content"><?php echo $fun_fact; ?></p>
                </div>
                
                </section>


            <section class="info-command-panel">
                
                <div class="profile-card sanctuary-card">
                    <h3 class="card-title">ABOUT MY SANCTUARY</h3>
                    <div class="card-content">
                        <i class="fa-solid fa-ankh sanctuary-icon"></i>
                        <p>Anda adalah anggota dari <?php echo htmlspecialchars($sanctuary_name); ?> Sanctuary.
                            <br><br>
Sanctuary Ammit, the fourth sanctuary "Sanctu #4" was forged for Nethara, bearer of Ammit’s divine blood. It shelters children chosen for their sense of justice, clarity of judgment, iron strong hearts, and wandering spirits destined for greater paths.

In the myths of ancient Kemet, Ammit is the Devourer of Death: a fearsome being with the crocodile’s jaws, the lion’s strength, and the hippopotamus’s unyielding might. No wicked soul escapes her shadow.

Within the Hall of Two Truths, Anubis weighs each heart against Ma’at’s feather. When a heart sinks with the weight of its deeds, Ammit consumes it severing its path to Osiris and casting the soul into the eternal silence of the second death.

Feared more than worshipped, Ammit keeps vigil at the lake of fire, watching the edges of the afterlife. There she waits, patient and ancient, for the unworthy to fall into her grasp.
                    </p>
                    </div>
                </div>

                <div class="profile-card news-card">
                    <h3 class="card-title">MOE NEWS AND EVENT</h3>
                    <div class="card-content">
                        <p>Data event dan pengumuman terbaru akan muncul di sini.</p>
                        <a href="class.php" class="more-link">Go to Class Schedule</a>
                    </div>
                </div>
                
                </section>

        </main>
    </div>
</body>
</html>