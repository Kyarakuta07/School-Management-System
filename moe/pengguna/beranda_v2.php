<?php
/**
 * Nethera Dashboard (Beranda) - V2 REDESIGN
 * Mediterranean of Egypt - School Management System
 * 
 * Premium modern dashboard with card-based layout
 */

// ==================================================
// TRADITIONAL SETUP (no bootstrap to avoid conflicts)
// ==================================================
session_start();
include '../connection.php';
require_once '../includes/Database.php';
require_once '../includes/helpers.php';
require_once '../includes/csrf.php';

// Initialize DB wrapper
DB::init($conn);

// Authentication check
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Nethera') {
    header("Location: ../index.php?pesan=gagal_akses");
    exit();
}

$user_id = $_SESSION['id_nethera'];
$user_name = htmlspecialchars($_SESSION['nama_lengkap']);

// Get user info with sanctuary (using DB wrapper)
$user_info = DB::queryOne(
    "SELECT n.status_akun, n.profile_photo, n.fun_fact, s.nama_sanctuary, s.deskripsi, s.id_sanctuary
     FROM nethera n
     JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     WHERE n.id_nethera = ?",
    [$user_id]
);

// Handle missing user data
if (!$user_info) {
    header("Location: ../index.php?pesan=error");
    exit();
}

// Extract data
$sanctuary_name = $user_info['nama_sanctuary'];
$sanctuary_desc = $user_info['deskripsi'] ?? '';
$sanctuary_id = $user_info['id_sanctuary'];
$profile_photo = $user_info['profile_photo'];
$fun_fact = $user_info['fun_fact'] ?? 'Share something interesting about yourself...';

// ==================================================
// FETCH USER STATS (with error handling)
// ==================================================

// Get total gold
try {
    $gold_result = DB::queryOne(
        "SELECT gold FROM user_stats WHERE user_id = ?",
        [$user_id]
    );
    $total_gold = $gold_result['gold'] ?? 0;
} catch (Exception $e) {
    $total_gold = 0;
}

// Get total pets
try {
    $pets_result = DB::queryOne(
        "SELECT COUNT(*) as total FROM user_pets WHERE user_id = ? AND status = 'ALIVE'",
        [$user_id]
    );
    $total_pets = $pets_result['total'] ?? 0;
} catch (Exception $e) {
    $total_pets = 0;
}

// Get sanctuary ranking
try {
    $rank_result = DB::queryOne(
        "SELECT COUNT(*) + 1 as rank 
         FROM nethera 
         WHERE id_sanctuary = ? AND id_nethera != ?",
        [$sanctuary_id, $user_id]
    );
    $sanctuary_rank = $rank_result['rank'] ?? '-';
} catch (Exception $e) {
    $sanctuary_rank = '-';
}

// ==================================================
// ACTIVE PET DATA (with error handling)
// ==================================================

try {
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
} catch (Exception $e) {
    $active_pet = null;
}

// Determine pet image and display info
$pet_image = null;
$pet_display_name = 'No Active Pet';
$pet_buff_text = null;
$pet_level = 0;
$pet_element = null;

if ($active_pet) {
    $pet_level = $active_pet['level'];
    $pet_element = $active_pet['element'];

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
// TIME-BASED GREETING
// ==================================================
$hour = date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning';
    $greeting_icon = '🌅';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'Good Afternoon';
    $greeting_icon = '☀️';
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = 'Good Evening';
    $greeting_icon = '🌆';
} else {
    $greeting = 'Good Night';
    $greeting_icon = '🌙';
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

    <title>Dashboard - <?= e($sanctuary_name) ?> Sanctuary</title>

    <link rel="stylesheet" href="../assets/css/global.css" />
    <link rel="stylesheet" href="../assets/css/landing-style.css" />
    <link rel="stylesheet" href="css/beranda_style.css" />
    <link rel="stylesheet" href="css/beranda_v2_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <div class="main-dashboard-wrapper">

        <!-- HERO SECTION -->
        <header class="hero-header">
            <div class="hero-content">
                <div class="greeting-section">
                    <span class="greeting-icon"><?= $greeting_icon ?></span>
                    <div class="greeting-text">
                        <h2 class="greeting"><?= $greeting ?>,</h2>
                        <h1 class="user-name-hero"><?= e($user_name) ?></h1>
                    </div>
                </div>
                <div class="hero-actions">
                    <a href="../logout.php" class="logout-btn-hero" title="Logout">
                        <i class="fa-solid fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
            <div class="sanctuary-badge">
                <i class="fas fa-shield-alt"></i>
                <span><?= e($sanctuary_name) ?> Sanctuary</span>
            </div>
        </header>

        <!-- MAIN NAVIGATION -->
        <nav class="top-nav-menu">
            <a href="beranda.php" class="nav-btn active"><i class="fa-solid fa-home"></i><span>Home</span></a>
            <a href="class.php" class="nav-btn"><i class="fa-solid fa-book-open"></i><span>Class</span></a>
            <a href="pet.php" class="nav-btn"><i class="fa-solid fa-paw"></i><span>Pet</span></a>
            <a href="trapeza.php" class="nav-btn"><i class="fa-solid fa-credit-card"></i><span>Trapeza</span></a>
            <a href="punishment.php" class="nav-btn"><i class="fa-solid fa-gavel"></i><span>Punishment</span></a>
        </nav>

        <!-- STATS OVERVIEW -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon gold-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Gold</span>
                    <span class="stat-value" data-count="<?= $total_gold ?>">0</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pet-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Pets</span>
                    <span class="stat-value" data-count="<?= $total_pets ?>">0</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rank-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Rank</span>
                    <span class="stat-value">#<?= $sanctuary_rank ?></span>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT GRID -->
        <main class="dashboard-grid">

            <!-- PROFILE CARD -->
            <div class="dashboard-card profile-card-new">
                <div class="card-header-new">
                    <h3><i class="fas fa-user-circle"></i> Profile</h3>
                </div>
                <div class="card-body-new">
                    <div class="avatar-wrapper" onclick="document.getElementById('profilePhotoInput').click()">
                        <img src="<?= $profile_photo ? '../uploads/profiles/' . e($profile_photo) : '../assets/default-avatar.png' ?>"
                            alt="Profile" class="profile-avatar-new" id="profileAvatarImg">
                        <div class="avatar-edit-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <input type="file" id="profilePhotoInput" accept="image/*" style="display: none;"
                        onchange="uploadProfilePhoto(this)">

                    <div class="profile-info-new">
                        <h4 class="profile-name"><?= e($user_name) ?></h4>
                        <p class="profile-role"><i class="fas fa-user-shield"></i> Nethera</p>
                    </div>
                </div>
            </div>

            <!-- ACTIVE PET CARD -->
            <div class="dashboard-card pet-showcase-card">
                <div class="card-header-new">
                    <h3><i class="fas fa-dragon"></i> Active Companion</h3>
                    <a href="pet.php" class="card-link">View All</a>
                </div>
                <div class="card-body-new">
                    <?php if ($active_pet): ?>
                        <div class="pet-showcase">
                            <div class="pet-image-wrapper">
                                <img src="<?= e($pet_image) ?>" alt="<?= e($pet_display_name) ?>" class="pet-image-large">
                                <div class="pet-element-badge <?= strtolower($pet_element) ?>">
                                    <?= e($pet_element) ?>
                                </div>
                            </div>
                            <div class="pet-details">
                                <h4 class="pet-name-large"><?= e($pet_display_name) ?></h4>
                                <div class="pet-level-badge">Lv. <?= $pet_level ?></div>
                                <?php if ($pet_buff_text): ?>
                                    <p class="pet-buff"><i class="fas fa-sparkles"></i> <?= e($pet_buff_text) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-egg fa-3x"></i>
                            <p>No active pet</p>
                            <a href="pet.php" class="btn-primary-small">Get a Pet</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FUN FACT CARD -->
            <div class="dashboard-card funfact-card-new">
                <div class="card-header-new">
                    <h3><i class="fas fa-lightbulb"></i> My Fun Fact</h3>
                    <button class="edit-btn" onclick="openFunfactModal()">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
                <div class="card-body-new">
                    <p class="funfact-text" id="funfactDisplay"><?= e($fun_fact) ?></p>
                </div>
            </div>

            <!-- SANCTUARY INFO CARD -->
            <div class="dashboard-card sanctuary-card-new">
                <div class="card-header-new">
                    <h3><i class="fas fa-landmark"></i> <?= e($sanctuary_name) ?></h3>
                </div>
                <div class="card-body-new">
                    <p class="sanctuary-desc"><?= e($sanctuary_desc) ?></p>
                    <div class="sanctuary-stats-mini">
                        <div class="mini-stat">
                            <i class="fas fa-users"></i>
                            <span>Your Rank: #<?= $sanctuary_rank ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS CARD -->
            <div class="dashboard-card actions-card">
                <div class="card-header-new">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="card-body-new">
                    <div class="action-grid">
                        <a href="pet.php" class="action-btn">
                            <i class="fas fa-paw"></i>
                            <span>Manage Pets</span>
                        </a>
                        <a href="trapeza.php" class="action-btn">
                            <i class="fas fa-coins"></i>
                            <span>Banking</span>
                        </a>
                        <a href="class.php" class="action-btn">
                            <i class="fas fa-book-open"></i>
                            <span>Classes</span>
                        </a>
                        <a href="punishment.php" class="action-btn">
                            <i class="fas fa-scroll"></i>
                            <span>Rules</span>
                        </a>
                    </div>
                </div>
            </div>

        </main>

    </div>

    <!-- FUN FACT MODAL -->
    <div class="modal-overlay" id="funfactModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Fun Fact</h3>
                <button class="modal-close" onclick="closeFunfactModal()">&times;</button>
            </div>
            <div class="modal-body">
                <textarea id="funfactInput" placeholder="Share something interesting about yourself..."
                    maxlength="500"><?= e($fun_fact !== 'Share something interesting about yourself...' ? $fun_fact : '') ?></textarea>
                <div class="char-count"><span id="charCount">0</span>/500</div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeFunfactModal()">Cancel</button>
                <button class="btn-save" onclick="saveFunfact()">Save</button>
            </div>
        </div>
    </div>

    <!-- CSRF Token -->
    <input type="hidden" id="csrfToken" value="<?= $csrf_token ?>">

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

    <script>
        // ==================================================
        // ANIMATED COUNTER
        // ==================================================
        function animateCounter(element) {
            const target = parseInt(element.dataset.count);
            const duration = 1500;
            const step = target / (duration / 16);
            let current = 0;

            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    element.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current).toLocaleString();
                }
            }, 16);
        }

        // Animate all counters on page load
        window.addEventListener('load', () => {
            document.querySelectorAll('.stat-value[data-count]').forEach(animateCounter);
        });

        // ==================================================
        // PROFILE PHOTO UPLOAD
        // ==================================================
        function uploadProfilePhoto(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_photo', input.files[0]);
                formData.append('csrf_token', document.getElementById('csrfToken').value);

                fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('profileAvatarImg').src = data.photo_url + '?t=' + Date.now();
                            showNotification('Profile photo updated!', 'success');
                        } else {
                            showNotification(data.message || 'Upload failed', 'error');
                        }
                    })
                    .catch(() => showNotification('Upload error', 'error'));
            }
        }

        // ==================================================
        // FUN FACT MODAL
        // ==================================================
        const funfactModal = document.getElementById('funfactModal');
        const funfactInput = document.getElementById('funfactInput');
        const charCount = document.getElementById('charCount');

        function openFunfactModal() {
            funfactModal.classList.add('show');
            updateCharCount();
        }

        function closeFunfactModal() {
            funfactModal.classList.remove('show');
        }

        funfactInput.addEventListener('input', updateCharCount);

        function updateCharCount() {
            charCount.textContent = funfactInput.value.length;
        }

        function saveFunfact() {
            const funfact = funfactInput.value.trim();

            fetch('update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `fun_fact=${encodeURIComponent(funfact)}&csrf_token=${document.getElementById('csrfToken').value}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('funfactDisplay').textContent = funfact || 'Share something interesting about yourself...';
                        closeFunfactModal();
                        showNotification('Fun fact updated!', 'success');
                    } else {
                        showNotification(data.message || 'Update failed', 'error');
                    }
                })
                .catch(() => showNotification('Update error', 'error'));
        }

        // Close modal on overlay click
        funfactModal.addEventListener('click', function (e) {
            if (e.target === this) closeFunfactModal();
        });

        // ==================================================
        // NOTIFICATION SYSTEM
        // ==================================================
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => notification.classList.add('show'), 10);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>

</body>

</html>