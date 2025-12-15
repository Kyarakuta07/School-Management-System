<?php
require_once '../includes/security_config.php';
session_start();
require_once '../includes/csrf.php';
include '../connection.php';

// 1. CEK AUTENTIKASI DASAR
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Nethera') {
    header("Location: ../index.php?pesan=gagal_akses");
    exit();
}

$id_user = $_SESSION['id_nethera'];

// 2. QUERY: CEK STATUS DAN AMBIL NAMA SANCTUARY + PROFILE PHOTO
$sql_info = "SELECT n.status_akun, n.profile_photo, s.nama_sanctuary
             FROM nethera n
             JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
             WHERE n.id_nethera = ?";

$stmt_info = mysqli_prepare($conn, $sql_info);

// --- PERBAIKAN KRITIS: CHECK JIKA QUERY GAGAL ---
if (!$stmt_info) {
    error_log("Beranda query prepare failed for user $id_user: " . mysqli_error($conn));
    header("Location: ../index.php?pesan=error");
    exit();
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
$profile_photo = $user_info_data['profile_photo']; // Profile photo filename

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

    <link rel="stylesheet" href="../assets/css/global.css" />
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
            <a href="class.php" class="nav-btn"><i class="fa-solid fa-book-open"></i><span>Class</span></a>
            <a href="pet.php" class="nav-btn"><i class="fa-solid fa-paw"></i><span>Pet</span></a>
            <a href="trapeza.php" class="nav-btn"><i class="fa-solid fa-credit-card"></i><span>Trapeza</span></a>
            <a href="punish.php" class="nav-btn"><i class="fa-solid fa-gavel"></i><span>Punishment</span></a>
            <a href="staff.php" class="nav-btn"><i class="fa-solid fa-users"></i><span>Staff</span></a>
            <a href="../logout.php" class="logout-btn-header"><i
                    class="fa-solid fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>

        <main class="profile-main-grid">

            <section class="profile-sidebar-panel">

                <div class="profile-avatar-box">
                    <div class="avatar-wrapper" onclick="document.getElementById('photoUploadInput').click()">
                        <?php
                        $avatarSrc = $profile_photo
                            ? '../assets/uploads/profiles/' . htmlspecialchars($profile_photo)
                            : '../assets/placeholder.png';
                        ?>
                        <img src="<?php echo $avatarSrc; ?>" alt="Avatar" class="profile-avatar-lg" id="avatarPreview">
                        <div class="avatar-edit-overlay">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                    </div>
                    <h2 class="user-name-title"><?php echo $nama_pengguna; ?></h2>
                    <p class="profile-link">My Profile</p>

                    <!-- Hidden file input for photo upload -->
                    <input type="file" id="photoUploadInput" accept="image/jpeg,image/png,image/gif,image/webp"
                        style="display: none;">
                </div>

                <div class="profile-card funfact-card">
                    <div class="card-title-row">
                        <h3 class="card-title">MY FUNFACT</h3>
                        <button class="edit-btn" onclick="openFunfactModal()"><i class="fa-solid fa-pen"></i></button>
                    </div>
                    <p class="card-content" id="funfactDisplay"><?php echo $fun_fact; ?></p>
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

    <!-- Fun Fact Edit Modal -->
    <div class="modal-overlay" id="funfactModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Fun Fact</h3>
                <button class="modal-close" onclick="closeFunfactModal()">&times;</button>
            </div>
            <div class="modal-body">
                <textarea id="funfactInput" placeholder="Tulis fun fact tentang dirimu..."
                    maxlength="500"><?php echo htmlspecialchars($row['fun_fact'] ?? ''); ?></textarea>
                <div class="char-count"><span id="charCount">0</span>/500</div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeFunfactModal()">Batal</button>
                <button class="btn-save" onclick="saveFunfact()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- CSRF Token -->
    <input type="hidden" id="csrfToken" value="<?php echo generate_csrf_token(); ?>">

    <script>
        // --- FUN FACT MODAL ---
        const funfactModal = document.getElementById('funfactModal');
        const funfactInput = document.getElementById('funfactInput');
        const charCount = document.getElementById('charCount');

        function openFunfactModal() {
            funfactModal.classList.add('active');
            updateCharCount();
        }

        function closeFunfactModal() {
            funfactModal.classList.remove('active');
        }

        function updateCharCount() {
            charCount.textContent = funfactInput.value.length;
        }

        funfactInput.addEventListener('input', updateCharCount);

        function saveFunfact() {
            const csrfToken = document.getElementById('csrfToken').value;
            const funfact = funfactInput.value.trim();

            fetch('update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_funfact&fun_fact=${encodeURIComponent(funfact)}&csrf_token=${csrfToken}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('funfactDisplay').textContent = data.fun_fact || 'Belum ada funfact.';
                        closeFunfactModal();
                        showToast('Fun fact berhasil diupdate!', 'success');
                    } else {
                        showToast(data.message || 'Gagal menyimpan', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Terjadi kesalahan', 'error');
                });
        }

        // --- PHOTO UPLOAD ---
        const photoInput = document.getElementById('photoUploadInput');
        const avatarPreview = document.getElementById('avatarPreview');

        photoInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showToast('Ukuran file terlalu besar (max 2MB)', 'error');
                    return;
                }

                // Show loading state
                avatarPreview.style.opacity = '0.5';

                const formData = new FormData();
                formData.append('action', 'upload_photo');
                formData.append('profile_photo', file);
                formData.append('csrf_token', document.getElementById('csrfToken').value);

                fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        avatarPreview.style.opacity = '1';
                        if (data.success) {
                            // Add cache buster to force reload
                            avatarPreview.src = '../' + data.photo_url + '?t=' + Date.now();
                            showToast('Foto profil berhasil diupdate!', 'success');
                        } else {
                            showToast(data.message || 'Gagal upload foto', 'error');
                        }
                    })
                    .catch(err => {
                        avatarPreview.style.opacity = '1';
                        console.error(err);
                        showToast('Terjadi kesalahan', 'error');
                    });
            }
        });

        // --- TOAST NOTIFICATION ---
        function showToast(message, type = 'success') {
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Close modal on overlay click
        funfactModal.addEventListener('click', function (e) {
            if (e.target === this) closeFunfactModal();
        });
    </script>
</body>

</html>