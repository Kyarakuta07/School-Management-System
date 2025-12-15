<?php
/**
 * Nethera Dashboard (Beranda)
 * Mediterranean of Egypt - School Management System
 * 
 * Main dashboard for Nethera users showing profile,
 * fun fact, active pet, and sanctuary information.
 * 
 * REFACTORED - Uses new bootstrap system
 */

// ==================================================
// BOOTSTRAP - Single line replaces 5+ includes
// ==================================================
require_once '../includes/bootstrap.php';

// ==================================================
// AUTHENTICATION - One line instead of 10+
// ==================================================
Auth::requireNethera();

// ==================================================
// DATA FETCHING - Now using DB class
// ==================================================

$user_id = Auth::id();
$user_name = Auth::name();

// Get user info with sanctuary (using DB wrapper)
$user_info = DB::queryOne(
    "SELECT n.status_akun, n.profile_photo, n.fun_fact, s.nama_sanctuary, s.deskripsi
     FROM nethera n
     JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     WHERE n.id_nethera = ?",
    [$user_id]
);

// Handle missing user data
if (!$user_info) {
    app_log("User info not found for ID: $user_id", 'ERROR');
    redirect('../index.php?pesan=error');
}

// Extract data (using e() helper for XSS protection)
$sanctuary_name = $user_info['nama_sanctuary'];
$sanctuary_desc = $user_info['deskripsi'] ?? '';
$profile_photo = $user_info['profile_photo'];
$fun_fact = $user_info['fun_fact'] ?? 'Belum ada funfact.';

// ==================================================
// ACTIVE PET DATA
// ==================================================

$active_pet = DB::queryOne(
    "SELECT up.*, ps.name as species_name, ps.element, 
            ps.img_baby, ps.img_adult, ps.img_egg,
            ps.passive_buff_type, ps.passive_buff_value
     FROM user_pets up 
     JOIN pet_species ps ON up.species_id = ps.id 
     WHERE up.user_id = ? AND up.is_active = 1 AND up.status = 'ALIVE'
     LIMIT 1",
    [$user_id]
);

// Determine pet image and display info
$pet_image = null;
$pet_display_name = null;
$pet_buff_text = null;

if ($active_pet) {
    $pet_level = $active_pet['level'];

    // Image based on evolution stage
    if ($pet_level >= 15) {
        $pet_image = '../assets/pets/' . $active_pet['img_adult'];
    } elseif ($pet_level >= 5) {
        $pet_image = '../assets/pets/' . $active_pet['img_baby'];
    } else {
        $pet_image = '../assets/pets/' . ($active_pet['img_egg'] ?? 'default/egg.png');
    }

    $pet_display_name = $active_pet['nickname'] ?? $active_pet['species_name'];
    $pet_buff_text = '+' . $active_pet['passive_buff_value'] . '% ' . ucfirst(str_replace('_', ' ', $active_pet['passive_buff_type']));
}

// ==================================================
// GENERATE CSRF TOKEN
// ==================================================
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Beranda - <?= e($sanctuary_name) ?> Sanctuary</title>

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
            <p class="main-h2">Anda adalah anggota dari <?= e($sanctuary_name) ?> Sanctuary.</p>
        </header>

        <nav class="top-nav-menu">
            <a href="class.php" class="nav-btn"><i class="fa-solid fa-book-open"></i><span>Class</span></a>
            <a href="pet.php" class="nav-btn"><i class="fa-solid fa-paw"></i><span>Pet</span></a>
            <a href="trapeza.php" class="nav-btn"><i class="fa-solid fa-credit-card"></i><span>Trapeza</span></a>
            <a href="punishment.php" class="nav-btn"><i class="fa-solid fa-gavel"></i><span>Punishment</span></a>
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
                            ? '../assets/uploads/profiles/' . e($profile_photo)
                            : '../assets/placeholder.png';
                        ?>
                        <img src="<?= $avatarSrc ?>" alt="Avatar" class="profile-avatar-lg" id="avatarPreview">
                        <div class="avatar-edit-overlay">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                    </div>
                    <h2 class="user-name-title"><?= e($user_name) ?></h2>
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
                    <p class="card-content" id="funfactDisplay"><?= e($fun_fact) ?></p>
                </div>

                <?php if ($active_pet): ?>
                    <!-- STUDY BUDDY PET WIDGET -->
                    <a href="pet.php" class="profile-card study-buddy-card">
                        <h3 class="card-title"><i class="fa-solid fa-paw"></i> STUDY BUDDY</h3>
                        <div class="study-buddy-content">
                            <div class="study-buddy-pet">
                                <img src="<?= $pet_image ?>" alt="<?= e($pet_display_name) ?>" class="study-buddy-img"
                                    onerror="this.src='../assets/placeholder.png'">
                            </div>
                            <div class="study-buddy-info">
                                <span class="buddy-name"><?= e($pet_display_name) ?></span>
                                <span
                                    class="buddy-element <?= strtolower($active_pet['element']) ?>"><?= $active_pet['element'] ?></span>
                                <span class="buddy-buff"><?= $pet_buff_text ?></span>
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
                        <p>Anda adalah anggota dari <?= e($sanctuary_name) ?> Sanctuary.
                            <br><br>
                            <?php if (!empty($sanctuary_desc)): ?>
                                <?= e($sanctuary_desc) ?>
                            <?php else: ?>
                                Sanctuary Ammit, the fourth sanctuary "Sanctu #4" was forged for Nethara, bearer of Ammit's
                                divine blood. It shelters children chosen for their sense of justice, clarity of judgment,
                                iron strong hearts, and wandering spirits destined for greater paths.

                                In the myths of ancient Kemet, Ammit is the Devourer of Death: a fearsome being with the
                                crocodile's jaws, the lion's strength, and the hippopotamus's unyielding might. No wicked
                                soul escapes her shadow.

                                Within the Hall of Two Truths, Anubis weighs each heart against Ma'at's feather. When a
                                heart sinks with the weight of its deeds, Ammit consumes it severing its path to Osiris and
                                casting the soul into the eternal silence of the second death.

                                Feared more than worshipped, Ammit keeps vigil at the lake of fire, watching the edges of
                                the afterlife. There she waits, patient and ancient, for the unworthy to fall into her
                                grasp.
                            <?php endif; ?>
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
                    maxlength="500"><?= e($fun_fact !== 'Belum ada funfact.' ? $fun_fact : '') ?></textarea>
                <div class="char-count"><span id="charCount">0</span>/500</div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeFunfactModal()">Batal</button>
                <button class="btn-save" onclick="saveFunfact()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- CSRF Token -->
    <input type="hidden" id="csrfToken" value="<?= $csrf_token ?>">

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