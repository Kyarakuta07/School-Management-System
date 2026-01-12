<?php
/**
 * Nethera Dashboard (Beranda)
 * Mediterranean of Egypt - School Management System
 * 
 * Main dashboard for Nethera users showing profile,
 * fun fact, active pet, and sanctuary information.
 */

// ==================================================
// TRADITIONAL SETUP (no bootstrap to avoid conflicts)
// SECURITY FIX: Added security_config
// ==================================================
require_once '../core/security_config.php';
session_start();
include '../config/connection.php';
require_once '../core/Database.php';
require_once '../core/helpers.php';
require_once '../core/csrf.php';

// Initialize DB wrapper
DB::init($conn);

// Authentication check - Allow Nethera, Vasiki (admin), Anubis, and Hakaes (teacher)
if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['Nethera', 'Vasiki', 'Anubis', 'Hakaes'])) {
    header("Location: ../index.php?pesan=gagal_akses");
    exit();
}

$user_id = $_SESSION['id_nethera'];
$user_name = htmlspecialchars($_SESSION['nama_lengkap']);
$user_role = $_SESSION['role'];

// Check if user can access admin dashboard
$can_access_admin = in_array($user_role, ['Vasiki', 'Anubis', 'Hakaes']);

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

// ==================================================
// TIME-BASED GREETING
// ==================================================
$hour = (int) date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning';
    $greeting_emoji = '🌅';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'Good Afternoon';
    $greeting_emoji = '☀️';
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = 'Good Evening';
    $greeting_emoji = '🌆';
} else {
    $greeting = 'Good Night';
    $greeting_emoji = '🌙';
}
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

        <!-- REDESIGNED HEADER -->
        <header class="hero-header">
            <div class="hero-content">
                <div class="greeting-section">
                    <span class="greeting-emoji"><?= $greeting_emoji ?></span>
                    <div class="greeting-text">
                        <p class="greeting-line"><?= $greeting ?>,</p>
                        <h1 class="user-name-hero"><?= e($user_name) ?></h1>
                    </div>
                </div>
                <?php if ($can_access_admin): ?>
                    <a href="../admin/index.php" class="admin-btn-hero" title="Admin Dashboard">
                        <i class="fa-solid fa-crown"></i>
                    </a>
                <?php endif; ?>
                <a href="../auth/handlers/logout.php" class="logout-btn-hero" title="Logout">
                    <i class="fa-solid fa-sign-out-alt"></i>
                </a>
            </div>
            <div class="sanctuary-badge">
                <i class="fas fa-shield-alt"></i>
                <span><?= e($sanctuary_name) ?> Sanctuary</span>
            </div>
        </header>

        <!-- TOP NAVIGATION -->
        <nav class="top-nav-menu">
            <a href="beranda.php" class="nav-btn active"><i class="fa-solid fa-home"></i><span>Home</span></a>
            <a href="class.php" class="nav-btn"><i class="fa-solid fa-book-open"></i><span>Class</span></a>
            <a href="pet.php" class="nav-btn"><i class="fa-solid fa-paw"></i><span>Pet</span></a>
            <a href="trapeza.php" class="nav-btn"><i class="fa-solid fa-credit-card"></i><span>Trapeza</span></a>
            <a href="punishment.php" class="nav-btn"><i class="fa-solid fa-gavel"></i><span>Punishment</span></a>
        </nav>

        <!-- MAIN DASHBOARD GRID -->
        <main class="dashboard-grid">

            <!-- LEFT COLUMN: PROFILE + FUNFACT -->
            <section class="dashboard-card profile-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-circle"></i> My Profile</h3>
                </div>
                <div class="card-body profile-body">
                    <!-- Avatar -->
                    <div class="avatar-container" onclick="document.getElementById('photoUploadInput').click()">
                        <?php if ($profile_photo): ?>
                            <img src="../assets/uploads/profiles/<?= e($profile_photo) ?>" alt="" class="avatar-img"
                                id="avatarPreview">
                        <?php else: ?>
                            <div class="avatar-placeholder" id="avatarPreview">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div class="avatar-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <input type="file" id="photoUploadInput" accept="image/jpeg,image/png,image/gif,image/webp"
                        style="display: none;">

                    <h2 class="profile-name"><?= e($user_name) ?></h2>
                    <p class="profile-role"><i class="fas fa-user-shield"></i> Nethera</p>

                    <div class="profile-divider"></div>

                    <!-- Fun Fact Section -->
                    <div class="funfact-section">
                        <div class="funfact-header">
                            <i class="fas fa-lightbulb"></i>
                            <span>My Fun Fact</span>
                            <button class="edit-btn-mini" onclick="openFunfactModal()">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <p class="funfact-text" id="funfactDisplay"><?= e($fun_fact) ?></p>
                    </div>
                </div>
            </section>

            <!-- RIGHT COLUMN: STUDY BUDDY + SANCTUARY -->
            <section class="right-column">

                <!-- STUDY BUDDY -->
                <?php if ($active_pet): ?>
                    <a href="pet.php" class="dashboard-card study-buddy-card">
                        <div class="card-header">
                            <h3><i class="fas fa-dragon"></i> Study Buddy</h3>
                            <span class="card-link">View All →</span>
                        </div>
                        <div class="card-body buddy-body">
                            <div class="buddy-pet">
                                <img src="<?= $pet_image ?>" alt="<?= e($pet_display_name) ?>" class="buddy-img"
                                    onerror="this.src='../assets/placeholder.png'">
                                <span
                                    class="element-badge <?= strtolower($active_pet['element']) ?>"><?= $active_pet['element'] ?></span>
                            </div>
                            <div class="buddy-info">
                                <h4 class="buddy-name"><?= e($pet_display_name) ?></h4>
                                <span class="buddy-level">Level <?= $active_pet['level'] ?></span>
                                <p class="buddy-buff"><i class="fas fa-sparkles"></i> <?= $pet_buff_text ?></p>
                            </div>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="pet.php" class="dashboard-card study-buddy-card empty">
                        <div class="card-header">
                            <h3><i class="fas fa-dragon"></i> Study Buddy</h3>
                        </div>
                        <div class="card-body buddy-empty">
                            <i class="fas fa-egg"></i>
                            <p>No active companion</p>
                            <span class="get-pet-btn">Get Your Pet →</span>
                        </div>
                    </a>
                <?php endif; ?>

                <!-- ABOUT MY SANCTUARY -->
                <div class="dashboard-card sanctuary-card">
                    <div class="card-header">
                        <h3><i class="fas fa-ankh"></i> About My Sanctuary</h3>
                    </div>
                    <div class="card-body sanctuary-body">
                        <div class="sanctuary-icon-wrapper">
                            <i class="fas fa-ankh"></i>
                        </div>
                        <h4 class="sanctuary-name"><?= e($sanctuary_name) ?> Sanctuary</h4>
                        <div class="sanctuary-lore">
                            <?php if (!empty($sanctuary_desc)): ?>
                                <p><?= e($sanctuary_desc) ?></p>
                            <?php else: ?>
                                <p>Sanctuary Ammit, the fourth sanctuary "Sanctu #4" was forged for Nethara, bearer of
                                    Ammit's divine blood. It shelters children chosen for their sense of justice, clarity of
                                    judgment, iron strong hearts, and wandering spirits destined for greater paths.</p>
                                <p>In the myths of ancient Kemet, Ammit is the Devourer of Death: a fearsome being with the
                                    crocodile's jaws, the lion's strength, and the hippopotamus's unyielding might. No
                                    wicked soul escapes her shadow.</p>
                            <?php endif; ?>
                        </div>
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

    <!-- BOTTOM NAVIGATION (Mobile Only) -->
    <nav class="bottom-nav">
        <a href="beranda.php" class="bottom-nav-item active">
            <i class="fa-solid fa-home"></i>
            <span>Home</span>
        </a>
        <a href="class.php" class="bottom-nav-item">
            <i class="fa-solid fa-book-open"></i>
            <span>Class</span>
        </a>
        <a href="pet.php" class="bottom-nav-item">
            <i class="fa-solid fa-paw"></i>
            <span>Pet</span>
        </a>
        <a href="trapeza.php" class="bottom-nav-item">
            <i class="fa-solid fa-credit-card"></i>
            <span>Bank</span>
        </a>
        <a href="punishment.php" class="bottom-nav-item">
            <i class="fa-solid fa-gavel"></i>
            <span>Rules</span>
        </a>
    </nav>

</body>

</html>