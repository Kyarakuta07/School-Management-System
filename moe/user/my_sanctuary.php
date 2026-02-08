<?php
/**
 * My Sanctuary - Private Member Control Room
 * Mediterranean of Egypt - School Management System
 * 
 * Exclusive member-only page for sanctuary management.
 * Features: Treasury Donation, Upgrades, Guild Buffs, Daily Rewards.
 * 
 * ACCESS: Members of the user's own sanctuary ONLY.
 */

require_once '../core/bootstrap.php';

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// ============================================
// AUTHENTICATION: Must be logged in
// ============================================
if (!Auth::isLoggedIn()) {
    redirect('../index.php?pesan=login_required');
    exit;
}

$user_id = Auth::id();

// Get user's sanctuary info
$user_sanctuary = DB::queryOne(
    "SELECT n.id_sanctuary, n.sanctuary_role, s.nama_sanctuary, s.deskripsi
     FROM nethera n
     JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     WHERE n.id_nethera = ?",
    [$user_id]
);

if (!$user_sanctuary) {
    die("Error: Sanctuary not found.");
}

$sanctuary_id = $user_sanctuary['id_sanctuary'];
$sanctuary_name = $user_sanctuary['nama_sanctuary'];
$user_role = $user_sanctuary['sanctuary_role'] ?? 'member';
$faction_slug = strtolower($sanctuary_name);

// Check if user is a leader (hosa or vizier)
$is_leader = in_array($user_role, ['hosa', 'vizier']);

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

// Fetch Member List (All members, up to 50)
$members = DB::query(
    "SELECT n.nama_lengkap, n.username, n.profile_photo, n.sanctuary_role, 
            COALESCE(cg.total_pp, 0) as total_pp
     FROM nethera n
     LEFT JOIN class_grades cg ON n.id_nethera = cg.id_nethera
     WHERE n.id_sanctuary = ? AND n.role = 'Nethera' AND n.status_akun = 'Aktif' 
           AND n.sanctuary_role NOT IN ('hosa', 'vizier')
     ORDER BY cg.total_pp DESC
     LIMIT 50",
    [$sanctuary_id]
);

// ============================================
// SANCTUARY FEATURES DATA
// ============================================

// Fetch Sanctuary Gold
$sanctuary_gold = DB::queryValue(
    "SELECT gold FROM sanctuary WHERE id_sanctuary = ?",
    [$sanctuary_id]
) ?? 0;

// Fetch User's Gold
$user_gold = DB::queryValue(
    "SELECT gold FROM nethera WHERE id_nethera = ?",
    [$user_id]
) ?? 0;

// Fetch Upgrades
$upgrades_raw = DB::query(
    "SELECT upgrade_type, level FROM sanctuary_upgrades WHERE sanctuary_id = ?",
    [$sanctuary_id]
);
$upgrades = [];
foreach ($upgrades_raw as $u) {
    $upgrades[$u['upgrade_type']] = $u['level'];
}

// Define Upgrade Costs
$upgrade_config = [
    'training_dummy' => ['name' => 'Training Dummy', 'desc' => '+5% Pet EXP from Battles', 'cost' => 50000, 'icon' => 'fa-dumbbell'],
    'beastiary' => ['name' => 'Beastiary Library', 'desc' => '+5% Shiny Pet Chance', 'cost' => 100000, 'icon' => 'fa-book-open'],
    'crystal_vault' => ['name' => 'Crystal Vault', 'desc' => '+10 Daily Gold Bonus', 'cost' => 250000, 'icon' => 'fa-gem'],
];

// Fetch Active Pet (for Daily Reward)
$active_pet = DB::queryOne(
    "SELECT up.id, up.nickname, up.level, up.evolution_stage, ps.name as species_name,
            up.hunger, up.mood
     FROM user_pets up
     JOIN pet_species ps ON up.species_id = ps.id
     WHERE up.user_id = ? AND up.is_active = 1 AND up.status = 'ALIVE'",
    [$user_id]
);

// Fetch Last Claim
$last_claim = DB::queryOne(
    "SELECT last_claim FROM sanctuary_daily_claims WHERE user_id = ? AND sanctuary_id = ?",
    [$user_id, $sanctuary_id]
);
$can_claim_daily = true;
$next_claim_time = null;
if ($last_claim) {
    $last_claim_time = strtotime($last_claim['last_claim']);
    $next_claim_time = $last_claim_time + (24 * 60 * 60); // 24 hours
    if (time() < $next_claim_time) {
        $can_claim_daily = false;
    }
}

// Check if pet is at level cap
require_once __DIR__ . '/pet/logic/evolution.php';
$pet_at_cap = false;
$pet_stage_name = 'Baby';
if ($active_pet) {
    $current_stage = $active_pet['evolution_stage'] ?? 'egg';
    $level_cap = getLevelCapForStage($current_stage);
    $pet_at_cap = ($active_pet['level'] >= $level_cap);
    $pet_stage_name = getStageName($current_stage);
}

// ============================================
// HANDLE POST ACTIONS
// ============================================
$action_message = '';
$action_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $action_message = 'Invalid security token. Please refresh and try again.';
    } else {
        $action = $_POST['action'];

        // DONATE GOLD
        if ($action === 'donate') {
            $donate_amount = isset($_POST['amount']) ? (int) $_POST['amount'] : 0;
            if ($donate_amount < 10) {
                $action_message = 'Minimum donation is 10 Gold.';
            } elseif ($donate_amount > $user_gold) {
                $action_message = 'Not enough gold!';
            } else {
                DB::execute("UPDATE nethera SET gold = gold - ? WHERE id_nethera = ?", [$donate_amount, $user_id]);
                DB::execute("UPDATE sanctuary SET gold = gold + ? WHERE id_sanctuary = ?", [$donate_amount, $sanctuary_id]);
                $action_message = "Donated $donate_amount Gold to the Treasury!";
                $action_success = true;
                // Refresh data
                $user_gold -= $donate_amount;
                $sanctuary_gold += $donate_amount;

                // Log Transaction (Trapeza History)
                DB::execute(
                    "INSERT INTO trapeza_transactions (sender_id, receiver_id, amount, transaction_type, description, status) VALUES (?, 0, ?, 'donation', 'Sanctuary Donation', 'completed')",
                    [$user_id, $donate_amount]
                );
            }
        }

        // PURCHASE UPGRADE (Leaders Only)
        if ($action === 'upgrade' && $is_leader) {
            $upgrade_type = $_POST['upgrade_type'] ?? '';
            if (isset($upgrade_config[$upgrade_type]) && !isset($upgrades[$upgrade_type])) {
                $cost = $upgrade_config[$upgrade_type]['cost'];
                if ($sanctuary_gold >= $cost) {
                    DB::execute("UPDATE sanctuary SET gold = gold - ? WHERE id_sanctuary = ?", [$cost, $sanctuary_id]);
                    DB::execute("INSERT INTO sanctuary_upgrades (sanctuary_id, upgrade_type, level) VALUES (?, ?, 1)", [$sanctuary_id, $upgrade_type]);
                    $action_message = "Purchased " . $upgrade_config[$upgrade_type]['name'] . "!";
                    $action_success = true;
                    $sanctuary_gold -= $cost;
                    $upgrades[$upgrade_type] = 1;
                } else {
                    $action_message = 'Not enough Treasury gold!';
                }
            } else {
                $action_message = 'Invalid upgrade or already purchased.';
            }
        }

        // CLAIM DAILY REWARD
        if ($action === 'daily_claim' && $can_claim_daily) {
            $base_gold = 50;
            $bonus_gold = 0;
            $exp_reward = 10;
            $exp_granted = false;
            $happy_bonus = false;

            // Crystal Vault Bonus
            if (isset($upgrades['crystal_vault'])) {
                $bonus_gold = 10;
            }

            // Happy Bonus: If active pet has >80% hunger and mood
            if ($active_pet && $active_pet['hunger'] > 80 && $active_pet['mood'] > 80) {
                $bonus_gold += 20;
                $happy_bonus = true;
            }

            $total_gold = $base_gold + $bonus_gold;
            DB::execute("UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?", [$total_gold, $user_id]);
            $user_gold += $total_gold;

            // Log Transaction (Trapeza History)
            DB::execute(
                "INSERT INTO trapeza_transactions (sender_id, receiver_id, amount, transaction_type, description, status) VALUES (0, ?, ?, 'daily_reward', 'Sanctuary Daily Reward', 'completed')",
                [$user_id, $total_gold]
            );

            // Grant EXP to active pet (if not at cap)
            if ($active_pet && !$pet_at_cap) {
                require_once __DIR__ . '/pet/logic/evolution.php';
                addExpToPet($GLOBALS['conn'], $active_pet['id'], $exp_reward);
                $exp_granted = true;
            }

            // Update or insert claim record
            if ($last_claim) {
                DB::execute("UPDATE sanctuary_daily_claims SET last_claim = NOW() WHERE user_id = ? AND sanctuary_id = ?", [$user_id, $sanctuary_id]);
            } else {
                DB::execute("INSERT INTO sanctuary_daily_claims (user_id, sanctuary_id, last_claim) VALUES (?, ?, NOW())", [$user_id, $sanctuary_id]);
            }

            $action_message = "Claimed $total_gold Gold!";
            if ($exp_granted) {
                $action_message .= " +$exp_reward Pet EXP!";
            } elseif ($active_pet && $pet_at_cap) {
                $action_message .= " (Pet at cap, evolve to gain EXP)";
            } elseif (!$active_pet) {
                $action_message .= " (No active pet for EXP)";
            }
            if ($happy_bonus) {
                $action_message .= " 🌟 Happy Bonus!";
            }
            $action_success = true;
            $can_claim_daily = false;
            $next_claim_time = time() + (24 * 60 * 60);
        }
    }
}

/**
 * SECURITY: Secure profile photo path helper
 * Returns a safe avatar URL, or empty string if invalid/empty.
 * Note: We skip file_exists() to support production where files are on a different server.
 */
function get_safe_avatar_url($photo_filename)
{
    if (empty($photo_filename)) {
        return '';
    }
    $safe_filename = basename($photo_filename);
    // Validate filename format (alphanumeric, underscore, dash, valid image extension)
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $safe_filename)) {
        return '';
    }
    // Return the path - matches update_profile.php upload directory
    return '../assets/uploads/profiles/' . $safe_filename;
}

// CSRF Token
$csrf_token = generate_csrf_token();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sanctuary -
        <?= e($sanctuary_name) ?> | MOE
    </title>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="css/beranda_style.css">

    <style>
        :root {
            --gold: #DAA520;
            --gold-glow: rgba(218, 165, 32, 0.5);
            --dark-bg: #0a0a0a;
            --panel-bg: rgba(15, 15, 18, 0.95);
            --card-bg: rgba(25, 25, 30, 0.9);
        }

        body {
            background-color: var(--dark-bg);
            color: #fff;
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Main Layout */
        .sanctuary-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 40px 60px;
        }

        /* Header */
        .sanctuary-header {
            background: linear-gradient(135deg, var(--panel-bg), rgba(30, 30, 35, 0.9));
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            position: relative;
            overflow: hidden;
        }

        .sanctuary-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .header-emblem {
            width: 100px;
            height: 100px;
            object-fit: contain;
            filter: drop-shadow(0 0 20px var(--gold-glow));
            flex-shrink: 0;
        }

        .header-info {
            flex: 1;
        }

        .header-title {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            color: var(--gold);
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header-subtitle {
            color: #aaa;
            font-size: 1rem;
            margin: 0;
        }

        .header-stats {
            display: flex;
            gap: 30px;
        }

        .stat-box {
            text-align: center;
            padding: 15px 25px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid rgba(218, 165, 32, 0.2);
        }

        .stat-value {
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            color: var(--gold);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        /* Cards */
        .control-card {
            background: var(--card-bg);
            border: 1px solid rgba(218, 165, 32, 0.2);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .control-card:hover {
            border-color: rgba(218, 165, 32, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .card-header {
            padding: 20px 24px;
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), transparent);
            border-bottom: 1px solid rgba(218, 165, 32, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header i {
            color: var(--gold);
            font-size: 1.2rem;
        }

        .card-header h3 {
            font-family: 'Cinzel', serif;
            color: var(--gold);
            margin: 0;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 24px;
        }

        /* Treasury Card */
        .treasury-display {
            text-align: center;
            margin-bottom: 20px;
        }

        .treasury-amount {
            font-family: 'Cinzel', serif;
            font-size: 3rem;
            color: var(--gold);
            text-shadow: 0 0 20px var(--gold-glow);
        }

        .treasury-label {
            color: #888;
            font-size: 0.9rem;
        }

        .donate-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--gold), #B8860B);
            border: none;
            border-radius: 8px;
            color: #000;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .donate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--gold-glow);
        }

        .donate-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Upgrade Card */
        .upgrade-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .upgrade-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.2), transparent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--gold);
            font-size: 1.5rem;
        }

        .upgrade-info {
            flex: 1;
        }

        .upgrade-name {
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }

        .upgrade-desc {
            font-size: 0.85rem;
            color: #888;
        }

        .upgrade-cost {
            font-family: 'Cinzel', serif;
            color: var(--gold);
            font-weight: 700;
        }

        /* Leadership Quick View */
        .leader-row {
            display: flex;
            align-items: center;
            padding: 12px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .leader-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            margin-right: 15px;
            object-fit: cover;
            background: #222;
        }

        .leader-avatar-fallback {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            margin-right: 15px;
            background: linear-gradient(135deg, #2a2a35, #1a1a25);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
        }

        .leader-details {
            flex: 1;
        }

        .leader-name {
            font-weight: 700;
            color: #fff;
        }

        .leader-role {
            font-size: 0.8rem;
            color: var(--gold);
            text-transform: uppercase;
        }

        /* Member Preview */
        .member-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .member-mini {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid rgba(218, 165, 32, 0.3);
            object-fit: cover;
            background: #222;
            transition: all 0.3s ease;
        }

        .member-mini:hover {
            border-color: var(--gold);
            transform: scale(1.1);
        }

        .member-more {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: rgba(218, 165, 32, 0.1);
            border: 1px dashed rgba(218, 165, 32, 0.3);
        }

        /* Barracks Grid (Full Member List) */
        .barracks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .member-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: linear-gradient(135deg, rgba(30, 30, 35, 0.6), rgba(20, 20, 25, 0.8));
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            position: relative;
            transition: all 0.3s ease;
        }

        .member-card::before {
            content: '';
            position: absolute;
            top: 15%;
            left: 0;
            bottom: 15%;
            width: 3px;
            background: var(--gold);
            border-radius: 0 4px 4px 0;
            opacity: 0.4;
            transition: opacity 0.3s;
        }

        .member-card:hover {
            transform: translateX(5px);
            background: linear-gradient(135deg, rgba(40, 40, 45, 0.8), rgba(30, 30, 35, 0.9));
            border-color: rgba(218, 165, 32, 0.4);
        }

        .member-card:hover::before {
            opacity: 1;
        }

        .member-avatar {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid rgba(218, 165, 32, 0.2);
            background: #222;
        }

        .member-avatar-fallback {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: #222;
            border: 1px dashed rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 700;
            color: #ddd;
            font-size: 1rem;
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
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* Full Width Card */
        .full-width {
            grid-column: span 2;
        }

        /* Back Link */
        .back-nav {
            margin-top: 30px;
            text-align: center;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
            text-decoration: none;
            border-radius: 8px;
            font-family: 'Cinzel', serif;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: var(--gold);
            color: #000;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .sanctuary-header {
                flex-direction: column;
                text-align: center;
            }

            .header-stats {
                justify-content: center;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }
        }

        @media (max-width: 600px) {
            .sanctuary-wrapper {
                padding: 16px;
            }

            .header-stats {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <div class="sanctuary-wrapper">

        <!-- HEADER -->
        <header class="sanctuary-header">
            <img src="../assets/faction emblem/faction_<?= e($faction_slug) ?>.png" alt="Emblem" class="header-emblem"
                onerror="this.style.display='none'">

            <div class="header-info">
                <h1 class="header-title">
                    <?= e($sanctuary_name) ?> Sanctuary
                </h1>
                <p class="header-subtitle">
                    <i class="fas fa-shield-alt"></i> Your role:
                    <strong style="color: var(--gold);">
                        <?= e(ucfirst($user_role)) ?>
                    </strong>
                </p>
            </div>

            <div class="header-stats">
                <div class="stat-box">
                    <span class="stat-value">
                        <?= number_format($member_count) ?>
                    </span>
                    <span class="stat-label">Members</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value">
                        <?= number_format($total_pp ?? 0) ?>
                    </span>
                    <span class="stat-label">Total PP</span>
                </div>
            </div>
        </header>

        <!-- DASHBOARD GRID -->
        <div class="dashboard-grid">

            <!-- ACTION MESSAGE -->
            <?php if ($action_message): ?>
                <div class="control-card full-width"
                    style="background: <?= $action_success ? 'rgba(50, 180, 50, 0.2)' : 'rgba(180, 50, 50, 0.2)'; ?>; border-color: <?= $action_success ? '#4a4' : '#a44'; ?>;">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <i class="fas <?= $action_success ? 'fa-check-circle' : 'fa-times-circle'; ?>"
                            style="color: <?= $action_success ? '#4a4' : '#a44'; ?>; font-size: 1.5rem;"></i>
                        <span style="margin-left: 10px;"><?= e($action_message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TREASURY -->
            <div class="control-card">
                <div class="card-header">
                    <i class="fas fa-coins"></i>
                    <h3>Treasury</h3>
                </div>
                <div class="card-body">
                    <div class="treasury-display">
                        <div class="treasury-amount"><?= number_format($sanctuary_gold) ?> G</div>
                        <div class="treasury-label">Guild Gold Reserve</div>
                    </div>
                    <p style="color: #888; font-size: 0.85rem; text-align: center; margin-bottom: 15px;">
                        Your Gold: <strong style="color: var(--gold);"><?= number_format($user_gold) ?> G</strong>
                    </p>
                    <form method="POST" style="display: flex; gap: 10px;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="donate">
                        <input type="number" name="amount" min="10" max="<?= $user_gold ?>" value="100"
                            style="flex: 1; padding: 12px; border-radius: 8px; border: 1px solid rgba(218,165,32,0.3); background: rgba(0,0,0,0.3); color: #fff; font-size: 1rem;">
                        <button type="submit" class="donate-btn"
                            style="flex: 0 0 auto; width: auto; padding: 12px 20px;" <?= $user_gold < 10 ? 'disabled' : '' ?>>
                            <i class="fas fa-hand-holding-usd"></i> Donate
                        </button>
                    </form>
                </div>
            </div>

            <!-- UPGRADES -->
            <div class="control-card">
                <div class="card-header">
                    <i class="fas fa-arrow-up"></i>
                    <h3>Sanctuary Upgrades</h3>
                    <?php if (!$is_leader): ?>
                        <span style="margin-left: auto; font-size: 0.75rem; color: #888;">(Leaders Only)</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php foreach ($upgrade_config as $type => $config): ?>
                        <?php
                        $is_purchased = isset($upgrades[$type]);
                        $can_afford = $sanctuary_gold >= $config['cost'];
                        ?>
                        <div class="upgrade-item" style="<?= !$is_purchased && !$can_afford ? 'opacity: 0.5;' : '' ?>">
                            <div class="upgrade-icon"><i class="fas <?= $config['icon'] ?>"></i></div>
                            <div class="upgrade-info">
                                <div class="upgrade-name"><?= e($config['name']) ?></div>
                                <div class="upgrade-desc"><?= e($config['desc']) ?></div>
                            </div>
                            <?php if ($is_purchased): ?>
                                <span style="color: #4a4; font-weight: 700;"><i class="fas fa-check"></i> ACTIVE</span>
                            <?php elseif ($is_leader): ?>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="action" value="upgrade">
                                    <input type="hidden" name="upgrade_type" value="<?= $type ?>">
                                    <button type="submit" class="donate-btn"
                                        style="padding: 8px 15px; font-size: 0.85rem; background: <?= $can_afford ? 'linear-gradient(135deg, var(--gold), #B8860B)' : '#444' ?>;"
                                        <?= !$can_afford ? 'disabled' : '' ?>>
                                        <?= number_format($config['cost']) ?> G
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="upgrade-cost"><?= number_format($config['cost']) ?> G</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- LEADERSHIP -->
            <div class="control-card">
                <div class="card-header">
                    <i class="fas fa-crown"></i>
                    <h3>Leadership</h3>
                </div>
                <div class="card-body">
                    <!-- Hosa -->
                    <div class="leader-row">
                        <?php $hosa_avatar = $hosa ? get_safe_avatar_url($hosa['profile_photo'] ?? '') : ''; ?>
                        <?php if ($hosa_avatar): ?>
                            <img src="<?= e($hosa_avatar) ?>" alt="Hosa" class="leader-avatar">
                        <?php else: ?>
                            <div class="leader-avatar-fallback"><i class="fas fa-crown"></i></div>
                        <?php endif; ?>
                        <div class="leader-details">
                            <div class="leader-name">
                                <?= $hosa ? e($hosa['nama_lengkap']) : 'Vacant' ?>
                            </div>
                            <div class="leader-role">Hosa (Leader)</div>
                        </div>
                    </div>
                    <!-- Vizier -->
                    <?php if (!empty($viziers)): ?>
                        <?php foreach ($viziers as $vizier): ?>
                            <?php $vizier_avatar = get_safe_avatar_url($vizier['profile_photo'] ?? ''); ?>
                            <div class="leader-row">
                                <?php if ($vizier_avatar): ?>
                                    <img src="<?= e($vizier_avatar) ?>" alt="Vizier" class="leader-avatar">
                                <?php else: ?>
                                    <div class="leader-avatar-fallback"><i class="fas fa-shield-alt"></i></div>
                                <?php endif; ?>
                                <div class="leader-details">
                                    <div class="leader-name">
                                        <?= e($vizier['nama_lengkap']) ?>
                                    </div>
                                    <div class="leader-role">Vizier</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="leader-row" style="opacity: 0.5;">
                            <div class="leader-avatar-fallback"><i class="fas fa-user-slash"></i></div>
                            <div class="leader-details">
                                <div class="leader-name">Vacant</div>
                                <div class="leader-role">Vizier</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FULL MEMBER LIST (BARRACKS) -->
            <div class="control-card full-width">
                <div class="card-header">
                    <i class="fas fa-users"></i>
                    <h3>The Nethara (<?= number_format($member_count) ?> Members)</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($members)): ?>
                        <div class="barracks-grid">
                            <?php foreach ($members as $member): ?>
                                <?php $avatar = get_safe_avatar_url($member['profile_photo'] ?? ''); ?>
                                <div class="member-card">
                                    <?php if ($avatar): ?>
                                        <img src="<?= e($avatar) ?>" alt="" class="member-avatar">
                                    <?php else: ?>
                                        <div class="member-avatar-fallback"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                    <div class="member-info">
                                        <div class="member-name"><?= e($member['nama_lengkap']) ?></div>
                                        <div class="member-username">@<?= e($member['username']) ?></div>
                                    </div>
                                    <div class="member-pp"><?= number_format($member['total_pp']) ?> PP</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #888; text-align: center;">No members found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DAILY REWARD (Full Width) -->
            <div class="control-card full-width">
                <div class="card-header">
                    <i class="fas fa-gift"></i>
                    <h3>Daily Sanctuary Reward</h3>
                </div>
                <div class="card-body" style="text-align: center;">
                    <!-- Reward Preview -->
                    <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px;">
                        <div>
                            <div style="font-size: 2rem; color: var(--gold);">+50 G</div>
                            <div style="color: #888; font-size: 0.85rem;">Base Gold</div>
                        </div>
                        <?php if (isset($upgrades['crystal_vault'])): ?>
                            <div>
                                <div style="font-size: 2rem; color: #4a4;">+10 G</div>
                                <div style="color: #888; font-size: 0.85rem;">Crystal Vault</div>
                            </div>
                        <?php endif; ?>
                        <?php if ($active_pet): ?>
                            <div>
                                <div style="font-size: 2rem; color: #88f;">+10 EXP</div>
                                <div style="color: #888; font-size: 0.85rem;">Pet EXP</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Active Pet Status -->
                    <?php if ($active_pet): ?>
                        <div
                            style="background: rgba(0,0,0,0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px; display: inline-block;">
                            <p style="color: #aaa; margin: 0 0 5px 0; font-size: 0.85rem;">Your Active Pet:</p>
                            <p style="color: #fff; margin: 0; font-weight: 700;">
                                <?= e($active_pet['nickname'] ?? $active_pet['species_name']) ?> (Lv.
                                <?= $active_pet['level'] ?>     <?= $pet_stage_name ?>)
                            </p>
                            <?php if ($pet_at_cap): ?>
                                <p style="color: #f80; margin: 5px 0 0 0; font-size: 0.8rem;">
                                    <i class="fas fa-exclamation-triangle"></i> At level cap! Evolve to gain EXP.
                                </p>
                            <?php endif; ?>
                            <?php
                            $is_happy = ($active_pet['hunger'] > 80 && $active_pet['mood'] > 80);
                            if ($is_happy): ?>
                                <p style="color: #4a4; margin: 5px 0 0 0; font-size: 0.8rem;">
                                    🌟 Happy Bonus: +20 Gold! (Pet is well cared for)
                                </p>
                            <?php else: ?>
                                <p style="color: #888; margin: 5px 0 0 0; font-size: 0.8rem;">
                                    <i class="fas fa-info-circle"></i> Keep Hunger & Mood > 80% for +20 Gold bonus!
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #888; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> No active pet. Get a pet from the Gacha to earn EXP!
                        </p>
                    <?php endif; ?>

                    <!-- Claim Button or Timer -->
                    <?php if ($can_claim_daily): ?>
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="daily_claim">
                            <button type="submit" class="donate-btn" style="max-width: 300px;">
                                <i class="fas fa-calendar-check"></i> Claim Daily Reward
                            </button>
                        </form>
                    <?php else: ?>
                        <div
                            style="background: rgba(0,0,0,0.3); border-radius: 10px; padding: 15px; display: inline-block;">
                            <p style="color: #888; margin: 0 0 5px 0; font-size: 0.85rem;">Next claim available in:</p>
                            <div id="daily-timer"
                                style="font-family: 'Cinzel', serif; font-size: 1.5rem; color: var(--gold);">
                                <?php
                                $remaining = $next_claim_time - time();
                                $hours = floor($remaining / 3600);
                                $minutes = floor(($remaining % 3600) / 60);
                                echo sprintf('%02d:%02d', $hours, $minutes);
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- BACK NAVIGATION -->
        <div class="back-nav">
            <a href="beranda.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

    </div>

    <!-- CSRF Token -->
    <input type="hidden" id="csrfToken" value="<?= $csrf_token ?>">

</body>

</html>