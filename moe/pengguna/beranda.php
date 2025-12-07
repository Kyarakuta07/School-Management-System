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

// 1. Siapkan kerangka query dengan tanda tanya (?) sebagai placeholder
$stmt = mysqli_prepare($conn, "SELECT fun_fact FROM nethera WHERE id_nethera = ?");

// 2. Cek apakah prepare berhasil (penting untuk debugging)
if ($stmt) {
    // 3. Masukkan data ke placeholder
    // "i" artinya integer (karena id_nethera adalah angka)
    mysqli_stmt_bind_param($stmt, "i", $id_user);

    // 4. Jalankan query
    mysqli_stmt_execute($stmt);

    // 5. Ambil hasilnya
    $result = mysqli_stmt_get_result($stmt);
    
    // Ambil data sebagai array asosiatif
    $row = mysqli_fetch_assoc($result);

    // Tutup statement
    mysqli_stmt_close($stmt);
} else {
    // Log error jika query gagal disiapkan (jangan tampilkan ke user)
    error_log("Query prepare failed: " . mysqli_error($conn));
    $row = null; // Set default jika gagal
}

$fun_fact = htmlspecialchars($row['fun_fact'] ?? 'Belum ada funfact.');

// 5. STUDY BUDDY - Get active pet for dashboard display
$active_pet = null;
$pet_query = "SELECT up.*, ps.name as species_name, ps.element, ps.img_baby, ps.img_adult, ps.passive_buff_type, ps.passive_buff_value
              FROM user_pets up 
              JOIN pet_species ps ON up.species_id = ps.id 
              WHERE up.user_id = ? AND up.is_active = 1 AND up.status = 'ALIVE'
              LIMIT 1";
$pet_stmt = mysqli_prepare($conn, $pet_query);
if ($pet_stmt) {
    mysqli_stmt_bind_param($pet_stmt, "i", $id_user);
    mysqli_stmt_execute($pet_stmt);
    $pet_result = mysqli_stmt_get_result($pet_stmt);
    $active_pet = mysqli_fetch_assoc($pet_result);
    mysqli_stmt_close($pet_stmt);
}

// Determine pet image path based on level
$pet_image = null;
$pet_display_name = null;
$pet_buff_text = null;
if ($active_pet) {
    $pet_level = $active_pet['level'];
    if ($pet_level >= 15) {
        $pet_image = '../assets/pets/' . $active_pet['img_adult'];
    } else if ($pet_level >= 5) {
        $pet_image = '../assets/pets/' . $active_pet['img_baby'];
    } else {
        $pet_image = '../assets/pets/' . ($active_pet['img_egg'] ?? 'default/egg.png');
    }
    $pet_display_name = $active_pet['nickname'] ?? $active_pet['species_name'];
    $pet_buff_text = '+' . $active_pet['passive_buff_value'] . '% ' . ucfirst(str_replace('_', ' ', $active_pet['passive_buff_type']));
}
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

                <?php if ($active_pet): ?>
                    <!-- STUDY BUDDY PET WIDGET -->
                    <a href="pet.php" class="profile-card study-buddy-card">
                        <h3 class="card-title"><i class="fa-solid fa-paw"></i> STUDY BUDDY</h3>
                        <div class="study-buddy-content">
                            <div class="study-buddy-pet">
                                <img src="<?php echo $pet_image; ?>" alt="<?php echo $pet_display_name; ?>"
                                    class="study-buddy-img" onerror="this.src='../assets/placeholder.png'">
                            </div>
                            <div class="study-buddy-info">
                                <span class="buddy-name"><?php echo htmlspecialchars($pet_display_name); ?></span>
                                <span
                                    class="buddy-element <?php echo strtolower($active_pet['element']); ?>"><?php echo $active_pet['element']; ?></span>
                                <span class="buddy-buff"><?php echo $pet_buff_text; ?></span>
                            </div>
                        </div>
                    </a>
                    <style>
                        .study-buddy-card {
                            display: block;
                            text-decoration: none;
                            transition: all 0.3s ease;
                            border: 1px solid rgba(218, 165, 32, 0.3) !important;
                        }

                        .study-buddy-card:hover {
                            border-color: #DAA520 !important;
                            transform: translateY(-3px);
                            box-shadow: 0 5px 20px rgba(218, 165, 32, 0.2);
                        }

                        .study-buddy-card .card-title {
                            color: #DAA520;
                            margin-bottom: 12px;
                        }

                        .study-buddy-card .card-title i {
                            margin-right: 6px;
                        }

                        .study-buddy-content {
                            display: flex;
                            align-items: center;
                            gap: 15px;
                        }

                        .study-buddy-pet {
                            width: 60px;
                            height: 60px;
                            flex-shrink: 0;
                        }

                        .study-buddy-img {
                            width: 100%;
                            height: 100%;
                            object-fit: contain;
                            animation: buddy-float 3s ease-in-out infinite;
                            filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.5));
                        }

                        @keyframes buddy-float {

                            0%,
                            100% {
                                transform: translateY(0) rotate(-2deg);
                            }

                            50% {
                                transform: translateY(-8px) rotate(2deg);
                            }
                        }

                        .study-buddy-info {
                            display: flex;
                            flex-direction: column;
                            gap: 4px;
                        }

                        .buddy-name {
                            font-family: 'Cinzel', serif;
                            font-size: 1rem;
                            color: #fff;
                        }

                        .buddy-element {
                            font-size: 0.7rem;
                            padding: 2px 8px;
                            border-radius: 10px;
                            width: fit-content;
                            text-transform: uppercase;
                            font-weight: bold;
                        }

                        .buddy-element.fire {
                            background: rgba(255, 107, 53, 0.2);
                            color: #ff6b35;
                        }

                        .buddy-element.water {
                            background: rgba(78, 205, 196, 0.2);
                            color: #4ecdc4;
                        }

                        .buddy-element.earth {
                            background: rgba(139, 115, 85, 0.2);
                            color: #c4a77d;
                        }

                        .buddy-element.air {
                            background: rgba(168, 218, 220, 0.2);
                            color: #a8dadc;
                        }

                        .buddy-element.dark {
                            background: rgba(108, 92, 231, 0.2);
                            color: #6c5ce7;
                        }

                        .buddy-element.light {
                            background: rgba(255, 234, 167, 0.2);
                            color: #d4a017;
                        }

                        .buddy-buff {
                            font-size: 0.75rem;
                            color: #2ecc71;
                            font-weight: bold;
                        }
                    </style>
                <?php endif; ?>

            </section>


            <section class="info-command-panel">

                <div class="profile-card sanctuary-card">
                    <h3 class="card-title">ABOUT MY SANCTUARY</h3>
                    <div class="card-content">
                        <i class="fa-solid fa-ankh sanctuary-icon"></i>
                        <p>Anda adalah anggota dari <?php echo htmlspecialchars($sanctuary_name); ?> Sanctuary.
                            <br><br>
                            Sanctuary Ammit, the fourth sanctuary "Sanctu #4" was forged for Nethara, bearer of Ammit’s
                            divine blood. It shelters children chosen for their sense of justice, clarity of judgment,
                            iron strong hearts, and wandering spirits destined for greater paths.

                            In the myths of ancient Kemet, Ammit is the Devourer of Death: a fearsome being with the
                            crocodile’s jaws, the lion’s strength, and the hippopotamus’s unyielding might. No wicked
                            soul escapes her shadow.

                            Within the Hall of Two Truths, Anubis weighs each heart against Ma’at’s feather. When a
                            heart sinks with the weight of its deeds, Ammit consumes it severing its path to Osiris and
                            casting the soul into the eternal silence of the second death.

                            Feared more than worshipped, Ammit keeps vigil at the lake of fire, watching the edges of
                            the afterlife. There she waits, patient and ancient, for the unworthy to fall into her
                            grasp.
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