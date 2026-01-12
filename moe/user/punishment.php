<?php
/**
 * Punishment & Discipline Page
 * Mediterranean of Egypt - School Management System
 * 
 * Displays user's punishment history, active sanctions,
 * and sanctuary code of conduct.
 */

// ==================================================
// SETUP - Using hybrid approach like beranda.php
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

// Authentication check - Allow both Nethera and Vasiki (admin)
if (!isset($_SESSION['status_login']) || ($_SESSION['role'] != 'Nethera' && $_SESSION['role'] != 'Vasiki')) {
    header("Location: ../index.php?pesan=gagal_akses");
    exit();
}

$user_id = $_SESSION['id_nethera'];
$user_name = htmlspecialchars($_SESSION['nama_lengkap']);

// Get user info with sanctuary
$user_info = DB::queryOne(
    "SELECT n.status_akun, s.nama_sanctuary, s.id_sanctuary
     FROM nethera n
     JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     WHERE n.id_nethera = ?",
    [$user_id]
);

$sanctuary_name = $user_info['nama_sanctuary'] ?? 'Unknown';
$sanctuary_id = $user_info['id_sanctuary'] ?? 0;

// CSRF Token for future forms
$csrf_token = generate_csrf_token();

// ==================================================
// FETCH PUNISHMENT DATA
// ==================================================

// Check if punishment table exists, if not create mock data
$punishment_history = [];
$active_punishments = [];
$total_punishment_points = 0;

// Try to fetch from database (if table exists)
try {
    // Fetch user's punishment history
    $punishment_history = DB::query(
        "SELECT * FROM punishment_log 
         WHERE id_nethera = ? 
         ORDER BY tanggal_pelanggaran DESC 
         LIMIT 10",
        [$user_id]
    );

    // Fetch active punishments
    $active_punishments = DB::query(
        "SELECT * FROM punishment_log 
         WHERE id_nethera = ? 
         AND status_hukuman = 'active' 
         ORDER BY tanggal_selesai ASC",
        [$user_id]
    );

    // Calculate total points
    $points_result = DB::queryOne(
        "SELECT SUM(poin_pelanggaran) as total_points 
         FROM punishment_log 
         WHERE id_nethera = ?",
        [$user_id]
    );

    $total_punishment_points = $points_result['total_points'] ?? 0;

} catch (Exception $e) {
    // Table doesn't exist yet - use empty arrays
    $punishment_history = [];
    $active_punishments = [];
}

// Sanctuary Code of Conduct (hardcoded for now)
$code_of_conduct = [
    [
        'category' => 'Academic Integrity',
        'rules' => [
            'No cheating during examinations or assignments',
            'Properly cite all sources in academic work',
            'Do not plagiarize or copy others\' work'
        ],
        'severity' => 'High',
        'points' => '10-20'
    ],
    [
        'category' => 'Respect & Conduct',
        'rules' => [
            'Treat all members with respect and dignity',
            'No bullying, harassment, or discrimination',
            'Maintain appropriate language in all communications'
        ],
        'severity' => 'Medium',
        'points' => '5-15'
    ],
    [
        'category' => 'Attendance & Punctuality',
        'rules' => [
            'Attend all scheduled classes and activities',
            'Arrive on time for all sessions',
            'Notify in advance if unable to attend'
        ],
        'severity' => 'Low',
        'points' => '2-5'
    ],
    [
        'category' => 'Property & Resources',
        'rules' => [
            'Respect sanctuary property and resources',
            'Do not vandalize or damage facilities',
            'Return borrowed items in good condition'
        ],
        'severity' => 'Medium',
        'points' => '5-10'
    ],
    [
        'category' => 'Safety & Security',
        'rules' => [
            'Follow all safety protocols and guidelines',
            'Report any security concerns immediately',
            'Do not bring prohibited items to sanctuary'
        ],
        'severity' => 'High',
        'points' => '15-25'
    ]
];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punishment & Discipline - <?= e($sanctuary_name) ?> Sanctuary</title>

    <link rel="stylesheet" href="../assets/css/global.css" />
    <link rel="stylesheet" href="../assets/css/landing-style.css" />
    <link rel="stylesheet" href="css/beranda_style.css" />
    <link rel="stylesheet" href="css/punishment_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <div class="main-dashboard-wrapper">

        <header class="top-user-header">
            <h1 class="main-h1 cinzel-title">PUNISHMENT & DISCIPLINE</h1>
            <p class="main-h2"><?= e($sanctuary_name) ?> Sanctuary - Code of Conduct</p>
        </header>

        <nav class="top-nav-menu">
            <a href="beranda.php" class="nav-btn"><i class="fa-solid fa-home"></i><span>Home</span></a>
            <a href="class.php" class="nav-btn"><i class="fa-solid fa-book-open"></i><span>Class</span></a>
            <a href="pet.php" class="nav-btn"><i class="fa-solid fa-paw"></i><span>Pet</span></a>
            <a href="trapeza.php" class="nav-btn"><i class="fa-solid fa-credit-card"></i><span>Trapeza</span></a>
            <a href="punishment.php" class="nav-btn active"><i class="fa-solid fa-gavel"></i><span>Punishment</span></a>
            <a href="../auth/handlers/logout.php" class="logout-btn-header"><i
                    class="fa-solid fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>

        <main class="punishment-main-content">

            <!-- LEFT SIDEBAR: Stats & Active Punishments -->
            <aside class="punishment-sidebar">

                <!-- Stats Card -->
                <div class="punishment-card stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Your Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <span class="stat-label">Total Points</span>
                            <span
                                class="stat-value <?= $total_punishment_points > 20 ? 'danger' : ($total_punishment_points > 10 ? 'warning' : 'safe') ?>">
                                <?= $total_punishment_points ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Active Sanctions</span>
                            <span class="stat-value"><?= count($active_punishments) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Total Violations</span>
                            <span class="stat-value"><?= count($punishment_history) ?></span>
                        </div>

                        <?php if ($total_punishment_points == 0): ?>
                            <div class="clean-record-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Clean Record</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Active Punishments Card -->
                <?php if (count($active_punishments) > 0): ?>
                    <div class="punishment-card active-punishment-card">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Active Sanctions</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($active_punishments as $punishment): ?>
                                <div class="active-punishment-item">
                                    <div class="punishment-title"><?= e($punishment['jenis_pelanggaran']) ?></div>
                                    <div class="punishment-points"><?= $punishment['poin_pelanggaran'] ?> pts</div>
                                    <div class="punishment-end">Ends:
                                        <?= date('d M Y', strtotime($punishment['tanggal_selesai'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="punishment-card no-active-card">
                        <div class="card-body text-center">
                            <i class="fas fa-smile fa-3x" style="color: var(--gold); margin-bottom: 10px;"></i>
                            <p>No active sanctions</p>
                            <small>Keep up the good behavior!</small>
                        </div>
                    </div>
                <?php endif; ?>

            </aside>

            <!-- MAIN CONTENT: Code of Conduct & History -->
            <div class="punishment-main">

                <!-- Code of Conduct Section -->
                <section class="punishment-card code-card">
                    <div class="card-header">
                        <h2><i class="fas fa-scroll"></i> Sanctuary Code of Conduct</h2>
                        <p class="header-subtitle">Rules and regulations for all members</p>
                    </div>
                    <div class="card-body">
                        <div class="code-grid">
                            <?php foreach ($code_of_conduct as $code): ?>
                                <div class="code-category">
                                    <div class="code-header">
                                        <h4><?= e($code['category']) ?></h4>
                                        <span class="severity-badge <?= strtolower($code['severity']) ?>">
                                            <?= e($code['severity']) ?> Risk
                                        </span>
                                    </div>
                                    <ul class="code-rules">
                                        <?php foreach ($code['rules'] as $rule): ?>
                                            <li><i class="fas fa-check-circle"></i> <?= e($rule) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="code-points">
                                        <i class="fas fa-exclamation-circle"></i>
                                        Violation: <strong><?= e($code['points']) ?> points</strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Punishment History Section -->
                <section class="punishment-card history-card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Your Punishment History</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($punishment_history) > 0): ?>
                            <div class="history-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Violation</th>
                                            <th>Sanction</th>
                                            <th>Points</th>
                                            <th>Status</th>
                                            <th>End Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($punishment_history as $record): ?>
                                            <tr>
                                                <td><?= date('d M Y', strtotime($record['tanggal_pelanggaran'])) ?></td>
                                                <td><?= e($record['jenis_pelanggaran']) ?></td>
                                                <td><?= e($record['jenis_hukuman']) ?></td>
                                                <td><span class="points-badge"><?= $record['poin_pelanggaran'] ?></span></td>
                                                <td>
                                                    <span class="status-badge <?= $record['status_hukuman'] ?>">
                                                        <?= ucfirst($record['status_hukuman']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $record['tanggal_selesai'] ? date('d M Y', strtotime($record['tanggal_selesai'])) : '-' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-check fa-4x"></i>
                                <h3>Perfect Record!</h3>
                                <p>You have no punishment history. Keep maintaining excellent conduct!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

            </div>

        </main>

    </div>

    <!-- BOTTOM NAVIGATION (Mobile Only) -->
    <nav class="bottom-nav">
        <a href="beranda.php" class="bottom-nav-item">
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
        <a href="punishment.php" class="bottom-nav-item active">
            <i class="fa-solid fa-gavel"></i>
            <span>Rules</span>
        </a>
    </nav>

</body>

</html>