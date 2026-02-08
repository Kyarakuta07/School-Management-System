<?php
/**
 * Guild Hall Page
 * Mediterranean of Egypt - School Management System
 * 
 * Displays the dedicated page for a specific Sanctuary.
 * Features: Throne Room (Leaders), Treasury, Upgrade Altar, and Barracks.
 * 
 * Security: Input validation, parameterized queries, output escaping
 */

require_once '../core/bootstrap.php';

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// SECURITY: Whitelist validation for faction parameter (prevents injection)
$faction_slug = isset($_GET['faction']) ? strtolower(trim($_GET['faction'])) : '';
$valid_factions = ['horus', 'khonshu', 'osiris', 'hathor', 'ammit'];

// Strict whitelist check - only allow predefined values
if (!in_array($faction_slug, $valid_factions, true)) {
    // Log potential attack attempt
    error_log("Invalid faction access attempt: " . substr($faction_slug, 0, 50));
    redirect('../world.php');
    exit;
}

// Map slug to Sanctuary Name (MUST MATCH DATABASE EXACTLY)
// These are hardcoded to prevent any injection
$faction_name_map = [
    'horus' => 'HORUS',
    'khonshu' => 'KHONSU',
    'osiris' => 'OSIRIS',
    'hathor' => 'HATHOR',
    'ammit' => 'AMMIT'
];

$target_sanctuary_name = $faction_name_map[$faction_slug];

// SECURITY: All DB queries use prepared statements (parameterized)
$sanctuary = DB::queryOne(
    "SELECT id_sanctuary, nama_sanctuary, deskripsi FROM sanctuary WHERE nama_sanctuary = ?",
    [$target_sanctuary_name]
);

if (!$sanctuary) {
    // Generic error - don't reveal database structure
    die("Page not found.");
}

$sanctuary_id = $sanctuary['id_sanctuary'];

// Fetch Statistics
$member_count = DB::queryValue(
    "SELECT COUNT(*) FROM nethera WHERE id_sanctuary = ?",
    [$sanctuary_id]
);

$total_pp = DB::queryValue(
    "SELECT SUM(cg.total_pp) 
     FROM class_grades cg 
     JOIN nethera n ON cg.id_nethera = n.id_nethera 
     WHERE n.id_sanctuary = ?",
    [$sanctuary_id]
);

// Fetch Leadership (Hosa & Vizier)
$leaders = DB::query(
    "SELECT n.nama_lengkap, n.sanctuary_role, n.profile_photo, n.username 
     FROM nethera n 
     WHERE n.id_sanctuary = ? AND n.sanctuary_role IN ('hosa', 'vizier')
     ORDER BY FIELD(n.sanctuary_role, 'hosa', 'vizier')",
    [$sanctuary_id]
);

$hosa = null;
$viziers = [];

foreach ($leaders as $leader) {
    if ($leader['sanctuary_role'] === 'hosa') {
        $hosa = $leader;
    } else {
        $viziers[] = $leader;
    }
}

// Logic for User Access (Member vs Visitor)
$user_id = Auth::id();
$is_member = false;

if (Auth::isLoggedIn()) {
    $my_sanctuary_id = DB::queryValue(
        "SELECT id_sanctuary FROM nethera WHERE id_nethera = ?",
        [$user_id]
    );
    if ($my_sanctuary_id == $sanctuary_id) {
        $is_member = true;
    }
}

// Fetch Member List (Barracks)
$members = DB::query(
    "SELECT n.nama_lengkap, n.username, n.profile_photo, n.sanctuary_role, 
            COALESCE(cg.total_pp, 0) as total_pp
     FROM nethera n
     LEFT JOIN class_grades cg ON n.id_nethera = cg.id_nethera
     WHERE n.id_sanctuary = ? AND n.role = 'Nethera' AND n.status_akun = 'Aktif'
     ORDER BY n.sanctuary_role DESC, cg.total_pp DESC
     LIMIT 50",
    [$sanctuary_id]
);

/**
 * SECURITY: Secure profile photo path helper
 * Prevents directory traversal and validates filename
 */
function get_safe_avatar_url($photo_filename)
{
    if (empty($photo_filename)) {
        return '';
    }

    // Remove any directory traversal attempts
    $safe_filename = basename($photo_filename);

    // Only allow alphanumeric, underscore, dash, and common image extensions
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $safe_filename)) {
        return '';
    }

    // Check if file actually exists
    $file_path = __DIR__ . '/../uploads/profile/' . $safe_filename;
    if (!file_exists($file_path)) {
        return '';
    }

    return '../uploads/profile/' . $safe_filename;
}

// Background Image Mapping (Placeholder for now)
$bg_image = "../assets/map/sanctuary_{$faction_slug}_bg.jpg";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($sanctuary['nama_sanctuary']) ?> - Guild Hall
    </title>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/landing-style.css">

    <style>
        :root {
            --gold: #DAA520;
            --gold-glow: rgba(218, 165, 32, 0.5);
            --dark-bg: #0a0a0a;
            --panel-bg: rgba(10, 10, 10, 0.85);
        }

        body {
            background-color: var(--dark-bg);
            color: #fff;
            font-family: 'Lato', sans-serif;
            overflow-x: hidden;
        }

        .guild-header {
            height: 70vh;
            min-height: 500px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(10, 10, 10, 0) 0%, rgba(10, 10, 10, 0.8) 80%, rgba(10, 10, 10, 1) 100%);
        }

        .guild-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, transparent 0%, rgba(10, 10, 10, 0.7) 100%);
            z-index: 1;
        }

        .guild-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.6;
            filter: blur(2px) brightness(0.8) saturate(1.1);
            z-index: 0;
        }

        .guild-title-wrapper {
            position: relative;
            z-index: 2;
        }

        .guild-emblem {
            width: 180px;
            height: auto;
            filter: drop-shadow(0 0 40px var(--gold-glow)) drop-shadow(0 0 60px rgba(218, 165, 32, 0.3));
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }

        .guild-name {
            font-family: 'Cinzel', serif;
            font-size: 4rem;
            background: linear-gradient(180deg, #FFD700 0%, #DAA520 50%, #B8860B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.8));
            margin: 0;
            letter-spacing: 4px;
        }

        .guild-stats {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 1rem;
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .stat-item i {
            color: var(--gold);
            margin-right: 5px;
        }

        /* --- SECTIONS --- */
        .guild-section {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        /* Decorative Section Divider */
        .guild-section::after {
            content: '';
            display: block;
            width: 80%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(218, 165, 32, 0.5), transparent);
            margin: 4rem auto 0;
            opacity: 0.3;
        }

        .section-title {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: 2.5rem;
            color: var(--gold);
            margin-bottom: 3.5rem;
            position: relative;
            display: table;
            margin-left: auto;
            margin-right: auto;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(218, 165, 32, 0.5);
        }

        /* Winged Header Effect */
        .section-title::before,
        .section-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold));
        }

        .section-title::before {
            right: 100%;
            margin-right: 20px;
            background: linear-gradient(90deg, transparent, var(--gold));
        }

        .section-title::after {
            left: 100%;
            margin-left: 20px;
            background: linear-gradient(90deg, var(--gold), transparent);
        }

        /* --- THRONE ROOM --- */
        .throne-room {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .leader-card {
            background: linear-gradient(160deg, rgba(20, 20, 25, 0.95), rgba(40, 35, 20, 0.9));
            border: 1px solid rgba(218, 165, 32, 0.4);
            border-radius: 12px;
            padding: 2.5rem 2rem;
            text-align: center;
            width: 320px;
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            /* Bouncy transition */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
        }

        /* Hosa Card Special Styling */
        .leader-card.hosa-card {
            transform: scale(1.05);
            border: 2px solid var(--gold);
            background: linear-gradient(160deg, rgba(30, 30, 35, 0.95), rgba(60, 50, 20, 0.9));
            box-shadow: 0 0 40px rgba(218, 165, 32, 0.15), 0 10px 30px rgba(0, 0, 0, 0.7);
            z-index: 2;
        }

        .leader-card:hover {
            transform: translateY(-8px);
            border-color: var(--gold);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7), 0 0 25px rgba(218, 165, 32, 0.3);
        }

        .leader-card.hosa-card:hover {
            transform: scale(1.05) translateY(-8px);
        }

        .role-badge {
            position: absolute;
            top: -18px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(45deg, #DAA520, #F0E68C, #DAA520);
            color: #000;
            padding: 8px 25px;
            border-radius: 4px;
            font-family: 'Cinzel', serif;
            font-weight: 900;
            font-size: 0.9rem;
            box-shadow: 0 0 20px rgba(218, 165, 32, 0.6);
            letter-spacing: 1px;
            clip-path: polygon(10% 0, 90% 0, 100% 50%, 90% 100%, 10% 100%, 0% 50%);
            /* Hexagon-ish strip */
            width: 120px;
            text-align: center;
        }

        .leader-avatar-wrapper {
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        /* Avatar Glow Ring */
        .leader-avatar-wrapper::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: linear-gradient(45deg, transparent, var(--gold), transparent);
            z-index: -1;
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .leader-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 4px solid #1a1a1a;
            object-fit: cover;
            background: #222;
        }

        .leader-avatar-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2a2a35, #1a1a25);
            color: var(--gold);
            font-size: 3rem;
        }

        .leader-name {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #fff;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.8);
            background: linear-gradient(to bottom, #fff, #ddd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .leader-username {
            color: var(--gold);
            font-style: italic;
            font-size: 0.95rem;
            opacity: 0.8;
        }

        /* --- TREASURY & UPGRADES --- */
        .treasury-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .treasury-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.01) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Shine Effect on Hover */
        .treasury-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.05), transparent);
            transform: skewX(-20deg);
            transition: 0.5s;
        }

        .treasury-card:hover::before {
            left: 150%;
        }

        .treasury-value {
            font-size: 3rem;
            font-family: 'Cinzel', serif;
            color: var(--gold);
            margin: 10px 0;
            text-shadow: 0 0 15px rgba(218, 165, 32, 0.4);
        }

        /* --- ANIMATIONS --- */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .guild-name {
                font-size: 2.5rem;
            }

            .throne-room {
                gap: 3rem;
            }

            .leader-card,
            .leader-card.hosa-card {
                width: 100%;
                max-width: 350px;
                transform: none;
            }

            .leader-card.hosa-card:hover {
                transform: translateY(-5px);
            }

            .section-title::before,
            .section-title::after {
                width: 30px;
            }

            .section-title {
                font-size: 2rem;
            }
        }

        /* Barracks Styles */
        .barracks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .member-card {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            background: linear-gradient(135deg, rgba(30, 30, 35, 0.6) 0%, rgba(20, 20, 25, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            /* Subtle border */
            padding: 1.2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            transition: all 0.3s ease;
        }

        /* Left Gold Accent */
        .member-card::before {
            content: '';
            position: absolute;
            top: 15%;
            left: 0;
            bottom: 15%;
            width: 3px;
            background: var(--gold);
            border-radius: 0 4px 4px 0;
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .member-card:hover {
            transform: translateX(5px);
            background: linear-gradient(135deg, rgba(40, 40, 45, 0.8), rgba(30, 30, 35, 0.9));
            border-color: rgba(218, 165, 32, 0.4);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        .member-card:hover::before {
            opacity: 1;
            box-shadow: 0 0 10px var(--gold);
        }

        .member-avatar-wrapper {
            position: relative;
            width: 55px;
            height: 55px;
            flex-shrink: 0;
        }

        .member-avatar {
            width: 55px;
            height: 55px;
            border-radius: 8px;
            /* Squared rounded for variety */
            object-fit: cover;
            border: 2px solid rgba(218, 165, 32, 0.2);
            transition: border-color 0.3s;
        }

        .member-card:hover .member-avatar {
            border-color: var(--gold);
        }

        .avatar-fallback {
            display: none;
            width: 55px;
            height: 55px;
            border-radius: 8px;
            background: #222;
            border: 1px dashed rgba(255, 255, 255, 0.2);
            align-items: center;
            justify-content: center;
            color: #666;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #ddd;
            font-size: 1.1rem;
            margin-bottom: 2px;
        }

        .member-username {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.4);
            font-style: italic;
        }

        .member-pp {
            font-family: 'Cinzel', serif;
            color: var(--gold);
            font-weight: bold;
            font-size: 0.95rem;
            text-shadow: 0 0 5px rgba(218, 165, 32, 0.3);
        }

        .back-link {
            display: inline-block;
            padding: 12px 30px;
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
            text-decoration: none;
            border-radius: 4px;
            font-family: 'Cinzel', serif;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .back-link:hover {
            background: var(--gold);
            color: #0d0d0d;
            box-shadow: 0 0 20px rgba(218, 165, 32, 0.4);
        }
    </style>

    </style>
</head>

<body>

    <!-- NAVBAR -->
    <?php include '../navbar.html'; ?>

    <!-- HEADER -->
    <header class="guild-header">
        <picture>
            <source media="(max-width: 768px)" srcset="../assets/sanctuhall/hallmobile_<?= $faction_slug ?>.jpeg">
            <img src="../assets/sanctuhall/halldesktop_<?= $faction_slug ?>.jpeg" alt="Background" class="guild-bg"
                onerror="this.src='../assets/map/map_moe_cleanup.jpeg'">
        </picture>

        <div class="guild-title-wrapper">
            <img src="../assets/faction emblem/faction_<?= $faction_slug ?>.png" alt="Emblem" class="guild-emblem">
            <h1 class="guild-name">
                <?= $sanctuary['nama_sanctuary'] ?>
            </h1>

            <div class="guild-stats">
                <div class="stat-item"><i class="fas fa-users"></i>
                    <?= number_format($member_count) ?> Scholars
                </div>
                <div class="stat-item"><i class="fas fa-star"></i>
                    <?= number_format($total_pp ?? 0) ?> Total PP
                </div>
            </div>

            <?php if (!$is_member): ?>
                <div style="margin-top: 1rem; color: #aaa; font-style: italic;">(Visitor View)</div>
            <?php endif; ?>
        </div>
    </header>

    <!-- THRONE ROOM -->
    <section class="guild-section">
        <h2 class="section-title">The Throne Room</h2>
        <div class="throne-room">
            <!-- HOSA (Leader) -->
            <div class="leader-card hosa-card">
                <div class="role-badge">HOSA</div>
                <?php $hosa_avatar = $hosa ? get_safe_avatar_url($hosa['profile_photo'] ?? '') : ''; ?>
                <div class="leader-avatar-wrapper">
                    <?php if ($hosa_avatar): ?>
                        <img src="<?= e($hosa_avatar) ?>" alt="Hosa" class="leader-avatar">
                    <?php else: ?>
                        <div class="leader-avatar leader-avatar-fallback">
                            <i class="fas fa-crown"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($hosa): ?>
                    <div class="leader-name"><?= e($hosa['nama_lengkap']) ?></div>
                    <div class="leader-username">@<?= e($hosa['username']) ?></div>
                <?php else: ?>
                    <div class="leader-name">Vacant</div>
                    <div class="leader-username">No leader assigned</div>
                <?php endif; ?>
            </div>

            <!-- VIZIER (Vice) -->
            <?php if (!empty($viziers)): ?>
                <?php foreach ($viziers as $vizier): ?>
                    <?php $vizier_avatar = get_safe_avatar_url($vizier['profile_photo'] ?? ''); ?>
                    <div class="leader-card">
                        <div class="role-badge" style="background: silver;">VIZIER</div>
                        <div class="leader-avatar-wrapper">
                            <?php if ($vizier_avatar): ?>
                                <img src="<?= e($vizier_avatar) ?>" alt="Vizier" class="leader-avatar">
                            <?php else: ?>
                                <div class="leader-avatar leader-avatar-fallback">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="leader-name"><?= e($vizier['nama_lengkap']) ?></div>
                        <div class="leader-username">@<?= e($vizier['username']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="leader-card">
                    <div class="role-badge" style="background: silver;">VIZIER</div>
                    <div class="leader-avatar leader-avatar-fallback">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="leader-name">Vacant</div>
                    <div class="leader-username">No vizier assigned</div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- MEMBER-ONLY ACCESS BANNER -->
    <?php if ($is_member): ?>
        <section class="guild-section" style="text-align: center; padding: 2rem;">
            <a href="my_sanctuary.php" class="back-link" style="font-size: 1rem;">
                <i class="fas fa-door-open"></i> Enter Control Room (Treasury, Upgrades & More)
            </a>
        </section>
    <?php endif; ?>

    <!-- BARRACKS (Member List) -->
    <section class="guild-section">
        <h2 class="section-title">The Nethara</h2>

        <?php if (!empty($members)): ?>
            <div class="barracks-grid">
                <?php foreach ($members as $member): ?>
                    <?php $member_avatar = get_safe_avatar_url($member['profile_photo'] ?? ''); ?>
                    <div class="member-card">
                        <div class="member-avatar-wrapper">
                            <?php if ($member_avatar): ?>
                                <img src="<?= e($member_avatar) ?>" alt="" class="member-avatar">
                            <?php else: ?>
                                <div class="avatar-fallback" style="display: flex;"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="member-info">
                            <div class="member-name">
                                <?= e($member['nama_lengkap']) ?>
                                <?php if ($member['sanctuary_role'] === 'hosa'): ?>
                                    <span class="member-role-tag hosa">👑</span>
                                <?php elseif ($member['sanctuary_role'] === 'vizier'): ?>
                                    <span class="member-role-tag vizier">⚔️</span>
                                <?php endif; ?>
                            </div>
                            <div class="member-username">@<?= e($member['username']) ?></div>
                        </div>
                        <div class="member-pp"><?= number_format($member['total_pp']) ?> PP</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #888; padding: 2rem;">
                <i class="fas fa-users-slash fa-3x" style="opacity: 0.5; margin-bottom: 1rem;"></i>
                <p>No members found in this Sanctuary.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Back Navigation -->
    <div style="text-align: center; padding: 2rem 0 4rem;">
        <a href="../world.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to World Map
        </a>
    </div>

</body>

</html>