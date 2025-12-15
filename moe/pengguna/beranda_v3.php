<?php
/**
 * Nethera Dashboard (Beranda) - V3 CREATIVE REDESIGN
 * Mediterranean of Egypt - School Management System
 * 
 * Premium modern dashboard with merged cards and optimal layout
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
    <link rel="stylesheet" href="css/beranda_v3_style.css" />
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

        <!-- MAIN CONTENT GRID - V3 DESIGN -->
        <main class="dashboard-grid-v3">

            <!-- PROFILE + FUNFACT COMBINED -->
            <div class="profile-funfact-combined">
                <div class="combined-header">
                    <h3><i class="fas fa-user-circle"></i> My Profile</h3>
                </div>
                <div class="combined-body">
                    <!-- Avatar -->
                    <div class="avatar-section" onclick="document.getElementById('profilePhotoInput').click()">
                        <?php if ($profile_photo): ?>
                            <img src="../uploads/profiles/<?= e($profile_photo) ?>" alt="" class="avatar-v3" id="profileAvatarImg">
                        <?php else: ?>
                            <div class="avatar-placeholder" id="profileAvatarImg"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                        <div class="avatar-hover"><i class="fas fa-camera"></i></div>
                    </div>
                    <input type="file" id="profilePhotoInput" accept="image/*" style="display: none;"
                        onchange="uploadProfilePhoto(this)">

                    <h4 class="name-v3"><?= e($user_name) ?></h4>
                    <p class="role-v3"><i class="fas fa-user-shield"></i> Nethera</p>

                    <div class="divider-v3"></div>

                    <!-- Fun Fact Inline -->
                    <div class="funfact-inline">
                        <div class="funfact-title">
                            <i class="fas fa-lightbulb"></i>
                            <span>My Fun Fact</span>
                            <button class="edit-mini" onclick="openFunfactModal()">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <p class="funfact-content" id="funfactDisplay"><?= e($fun_fact) ?></p>
                    </div>
                </div>
            </div>

            <!-- ACTIVE COMPANION - LARGE -->
            <div class="companion-large">
                <div class="card-header-v3">
                    <h3><i class="fas fa-dragon"></i> Active Companion</h3>
                    <a href="pet.php" class="link-v3">View All →</a>
                </div>
                <div class="card-body-v3">
                    <?php if ($active_pet): ?>
                        <div class="pet-display-v3">
                            <div class="pet-visual">
                                <img src="<?= e($pet_image) ?>" alt="<?= e($pet_display_name) ?>" class="pet-img-v3">
                                <div class="element-badge-v3 <?= strtolower($pet_element) ?>">
                                    <?= e($pet_element) ?>
                                </div>
                            </div>
                            <div class="pet-stats-v3">
                                <h4><?= e($pet_display_name) ?></h4>
                                <div class="level-v3">Level <?= $pet_level ?></div>
                                <?php if ($pet_buff_text): ?>
                                    <p class="buff-v3"><i class="fas fa-sparkles"></i> <?= e($pet_buff_text) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-pet-v3">
                            <i class="fas fa-egg"></i>
                            <p>No companion yet</p>
                            <a href="pet.php" class="btn-v3">Get Pet</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SANCTUARY - FULL WIDTH -->
            <div class="sanctuary-full">
                <div class="card-header-v3">
                    <h3><i class="fas fa-ankh"></i> About My Sanctuary</h3>
                </div>
                <div class="card-body-v3">
                    <div class="sanctuary-flex">
                        <div class="sanctuary-icon-v3">
                            <i class="fas fa-ankh"></i>
                        </div>
                        <div class="sanctuary-info-v3">
                            <h4><?= e($sanctuary_name) ?> Sanctuary</h4>
                            <p>
                                <?php if (!empty($sanctuary_desc)): ?>
                                    <?= e($sanctuary_desc) ?>
                                <?php else: ?>
                                    Sanctuary Ammit, the fourth sanctuary "Sanctu #4" was forged for Nethara, bearer of
                                    Ammit's divine blood. It shelters children chosen for their sense of justice, clarity of
                                    judgment, iron strong hearts, and wandering spirits destined for greater paths.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NEWS & EVENTS -->
            <div class="news-events-v3">
                <div class="card-header-v3">
                    <h3><i class="fas fa-newspaper"></i> News & Events</h3>
                </div>
                <div class="card-body-v3">
                    <div class="news-icon-v3">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <p class="news-text-v3">Latest announcements will appear here</p>
                    <div class="news-actions-v3">
                        <a href="class.php" class="action-v3">
                            <i class="fas fa-calendar-alt"></i>
                            Class Schedule
                        </a>
                        <a href="punishment.php" class="action-v3">
                            <i class="fas fa-scroll"></i>
                            Code of Conduct
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
        // Profile Photo Upload
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

        // Fun Fact Modal
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

        funfactModal.addEventListener('click', function (e) {
            if (e.target === this) closeFunfactModal();
        });

        // Notification System
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