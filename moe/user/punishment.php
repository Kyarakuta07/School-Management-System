<?php
/**
 * Punishment & Discipline Page
 * Mediterranean of Egypt - School Management System
 * 
 * - Nethera: View their own punishment history and status
 * - Anubis/Vasiki: Manage punishments for all Nethera users
 */

// ==================================================
// SETUP
// ==================================================
require_once '../core/security_config.php';
session_start();
include '../config/connection.php';
require_once '../core/Database.php';
require_once '../core/helpers.php';
require_once '../core/csrf.php';

// Initialize DB wrapper
DB::init($conn);

// Authentication check - Allow Nethera, Vasiki, and Anubis
$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['status_login']) || !in_array($role, ['Nethera', 'Vasiki', 'Anubis'])) {
    header("Location: ../index.php?pesan=gagal_akses");
    exit();
}

$user_id = $_SESSION['id_nethera'];
$user_name = htmlspecialchars($_SESSION['nama_lengkap']);
$can_manage = ($role === 'Anubis' || $role === 'Vasiki');

// Check if user came from a locked feature
$locked_feature = $_GET['locked'] ?? '';
$locked_messages = [
    'trapeza' => 'Akses ke Trapeza (Bank) dibatasi karena Anda memiliki hukuman aktif.',
    'pet' => 'Akses ke Pet System dibatasi karena Anda memiliki hukuman aktif.',
    'class' => 'Akses ke Class Schedule dibatasi karena Anda memiliki hukuman aktif.'
];
$lock_message = $locked_messages[$locked_feature] ?? '';

// Get user info with sanctuary
$user_info = DB::queryOne(
    "SELECT n.status_akun, s.nama_sanctuary, s.id_sanctuary
     FROM nethera n
     JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     WHERE n.id_nethera = ?",
    [$user_id]
);

$sanctuary_name = $user_info['nama_sanctuary'] ?? 'Unknown';
$csrf_token = generate_csrf_token();

// ==================================================
// HANDLE ANUBIS ACTIONS (POST)
// ==================================================
$action_message = '';
$action_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $action_error = 'Invalid CSRF token';
    } else {
        $action = $_POST['action'] ?? '';

        // ADD PUNISHMENT
        if ($action === 'add_punishment') {
            $target_id = (int) ($_POST['target_id'] ?? 0);
            $jenis_pelanggaran = trim($_POST['jenis_pelanggaran'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            $jenis_hukuman = trim($_POST['jenis_hukuman'] ?? '');
            $poin = (int) ($_POST['poin'] ?? 0);

            if ($target_id && $jenis_pelanggaran && $jenis_hukuman) {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO punishment_log (id_nethera, jenis_pelanggaran, deskripsi_pelanggaran, jenis_hukuman, poin_pelanggaran, status_hukuman, given_by) 
                     VALUES (?, ?, ?, ?, ?, 'active', ?)"
                );
                mysqli_stmt_bind_param($stmt, "isssis", $target_id, $jenis_pelanggaran, $deskripsi, $jenis_hukuman, $poin, $user_id);

                if (mysqli_stmt_execute($stmt)) {
                    $action_message = 'Punishment berhasil ditambahkan!';
                } else {
                    $action_error = 'Gagal menambah punishment.';
                }
                mysqli_stmt_close($stmt);
            }
        }

        // RELEASE PUNISHMENT
        if ($action === 'release_punishment') {
            $punishment_id = (int) ($_POST['punishment_id'] ?? 0);

            if ($punishment_id) {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE punishment_log SET status_hukuman = 'completed', tanggal_selesai = NOW(), released_by = ? WHERE id_punishment = ?"
                );
                mysqli_stmt_bind_param($stmt, "ii", $user_id, $punishment_id);

                if (mysqli_stmt_execute($stmt)) {
                    $action_message = 'Punishment berhasil dilepas!';
                } else {
                    $action_error = 'Gagal melepas punishment.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// ==================================================
// FETCH PUNISHMENT DATA
// ==================================================
$punishment_history = [];
$active_punishments = [];
$total_punishment_points = 0;
$all_nethera = []; // For Anubis dropdown

try {
    if ($can_manage) {
        // Anubis/Vasiki sees ALL punishments
        $punishment_history = DB::query(
            "SELECT p.*, n.nama_lengkap as user_name 
             FROM punishment_log p
             JOIN nethera n ON p.id_nethera = n.id_nethera
             ORDER BY p.tanggal_pelanggaran DESC LIMIT 50"
        );

        $active_punishments = DB::query(
            "SELECT p.*, n.nama_lengkap as user_name 
             FROM punishment_log p
             JOIN nethera n ON p.id_nethera = n.id_nethera
             WHERE p.status_hukuman = 'active'
             ORDER BY p.tanggal_pelanggaran DESC"
        );

        // Get all Nethera users for dropdown
        $all_nethera = DB::query("SELECT id_nethera, nama_lengkap FROM nethera WHERE role = 'Nethera' ORDER BY nama_lengkap");

    } else {
        // Nethera sees only their own
        $punishment_history = DB::query(
            "SELECT * FROM punishment_log WHERE id_nethera = ? ORDER BY tanggal_pelanggaran DESC LIMIT 10",
            [$user_id]
        );

        $active_punishments = DB::query(
            "SELECT * FROM punishment_log WHERE id_nethera = ? AND status_hukuman = 'active' ORDER BY tanggal_pelanggaran DESC",
            [$user_id]
        );
    }

    // Calculate total points for current user
    $points_result = DB::queryOne(
        "SELECT COALESCE(SUM(poin_pelanggaran), 0) as total_points FROM punishment_log WHERE id_nethera = ?",
        [$user_id]
    );
    $total_punishment_points = $points_result['total_points'] ?? 0;

} catch (Exception $e) {
    error_log("Punishment query error: " . $e->getMessage());
}

// Violation types for dropdown
$violation_types = [
    'Academic Dishonesty' => 'Ketidakjujuran Akademik',
    'Disrespect' => 'Tidak Hormat',
    'Attendance Issue' => 'Masalah Kehadiran',
    'Property Damage' => 'Kerusakan Properti',
    'Safety Violation' => 'Pelanggaran Keamanan',
    'Other' => 'Lainnya'
];

$punishment_types = [
    'Warning' => 'Peringatan',
    'Feature Lock' => 'Penguncian Fitur',
    'Suspension' => 'Skorsing',
    'Probation' => 'Masa Percobaan'
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

    <style>
        /* Additional styles for Anubis panel */
        .anubis-panel {
            background: rgba(139, 69, 19, 0.2);
            border: 1px solid #DAA520;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .anubis-panel h3 {
            color: #FFD700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            color: #ccc;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #444;
            border-radius: 8px;
            padding: 10px;
            color: #fff;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: #FFD700;
            outline: none;
        }

        .btn-anubis {
            background: linear-gradient(135deg, #8B4513, #DAA520);
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }

        .btn-anubis:hover {
            transform: translateY(-2px);
        }

        .btn-release {
            background: #2ecc71;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .alert-lock {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid #e74c3c;
            color: #ff6b6b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid #2ecc71;
            color: #2ecc71;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .role-badge {
            background: linear-gradient(135deg, #8B4513, #DAA520);
            color: #000;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <div class="main-dashboard-wrapper">

        <header class="top-user-header">
            <h1 class="main-h1 cinzel-title">PUNISHMENT & DISCIPLINE</h1>
            <p class="main-h2">
                <?= e($sanctuary_name) ?> Sanctuary
                <?php if ($can_manage): ?>
                    <span class="role-badge"><i class="fas fa-shield-alt"></i> <?= e($role) ?> Mode</span>
                <?php endif; ?>
            </p>
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

                <?php if ($lock_message): ?>
                    <div class="alert-lock">
                        <i class="fas fa-lock"></i>
                        <span><?= e($lock_message) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($action_message): ?>
                    <div class="alert-success"><i class="fas fa-check"></i> <?= e($action_message) ?></div>
                <?php endif; ?>

                <?php if ($action_error): ?>
                    <div class="alert-error"><i class="fas fa-times"></i> <?= e($action_error) ?></div>
                <?php endif; ?>

                <!-- Stats Card -->
                <div class="punishment-card stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> <?= $can_manage ? 'System Stats' : 'Your Status' ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <span class="stat-label"><?= $can_manage ? 'Active Cases' : 'Total Points' ?></span>
                            <span
                                class="stat-value <?= $total_punishment_points > 20 ? 'danger' : ($total_punishment_points > 10 ? 'warning' : 'safe') ?>">
                                <?= $can_manage ? count($active_punishments) : $total_punishment_points ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label"><?= $can_manage ? 'Total Records' : 'Active Sanctions' ?></span>
                            <span
                                class="stat-value"><?= $can_manage ? count($punishment_history) : count($active_punishments) ?></span>
                        </div>

                        <?php if (!$can_manage && $total_punishment_points == 0): ?>
                            <div class="clean-record-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Clean Record</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ANUBIS: Add Punishment Form -->
                <?php if ($can_manage): ?>
                    <div class="anubis-panel">
                        <h3><i class="fas fa-plus-circle"></i> Add Punishment</h3>
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="add_punishment">

                            <div class="form-group">
                                <label>Select Nethera</label>
                                <select name="target_id" class="form-control" required>
                                    <option value="">-- Pilih User --</option>
                                    <?php foreach ($all_nethera as $nethera): ?>
                                        <option value="<?= $nethera['id_nethera'] ?>"><?= e($nethera['nama_lengkap']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Jenis Pelanggaran</label>
                                <select name="jenis_pelanggaran" class="form-control" required>
                                    <?php foreach ($violation_types as $en => $id): ?>
                                        <option value="<?= $en ?>"><?= $id ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="2"
                                    placeholder="Detail pelanggaran..."></textarea>
                            </div>

                            <div class="form-group">
                                <label>Jenis Hukuman</label>
                                <select name="jenis_hukuman" class="form-control" required>
                                    <?php foreach ($punishment_types as $en => $id): ?>
                                        <option value="<?= $en ?>"><?= $id ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Poin Pelanggaran</label>
                                <input type="number" name="poin" class="form-control" value="5" min="1" max="100">
                            </div>

                            <button type="submit" class="btn-anubis">
                                <i class="fas fa-gavel"></i> Tambah Punishment
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Active Punishments Card -->
                <?php if (count($active_punishments) > 0): ?>
                    <div class="punishment-card active-punishment-card">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Active Sanctions</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($active_punishments as $punishment): ?>
                                <div class="active-punishment-item">
                                    <?php if ($can_manage): ?>
                                        <div class="punishment-user"><?= e($punishment['user_name'] ?? 'Unknown') ?></div>
                                    <?php endif; ?>
                                    <div class="punishment-title"><?= e($punishment['jenis_pelanggaran']) ?></div>
                                    <div class="punishment-points"><?= $punishment['poin_pelanggaran'] ?> pts</div>

                                    <?php if ($can_manage): ?>
                                        <form action="" method="POST" style="margin-top: 5px;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="action" value="release_punishment">
                                            <input type="hidden" name="punishment_id" value="<?= $punishment['id_punishment'] ?>">
                                            <button type="submit" class="btn-release">
                                                <i class="fas fa-unlock"></i> Release
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="punishment-card no-active-card">
                        <div class="card-body text-center">
                            <i class="fas fa-smile fa-3x" style="color: var(--gold); margin-bottom: 10px;"></i>
                            <p><?= $can_manage ? 'No active punishments' : 'No active sanctions' ?></p>
                            <small><?= $can_manage ? 'All users are in good standing!' : 'Keep up the good behavior!' ?></small>
                        </div>
                    </div>
                <?php endif; ?>

            </aside>

            <!-- MAIN CONTENT: History -->
            <div class="punishment-main">

                <!-- Punishment History Section -->
                <section class="punishment-card history-card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i>
                            <?= $can_manage ? 'All Records' : 'Your Punishment History' ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($punishment_history) > 0): ?>
                            <div class="history-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <?php if ($can_manage): ?>
                                                <th>User</th><?php endif; ?>
                                            <th>Date</th>
                                            <th>Violation</th>
                                            <th>Sanction</th>
                                            <th>Points</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($punishment_history as $record): ?>
                                            <tr>
                                                <?php if ($can_manage): ?>
                                                    <td><?= e($record['user_name'] ?? 'Unknown') ?></td>
                                                <?php endif; ?>
                                                <td><?= date('d M Y', strtotime($record['tanggal_pelanggaran'])) ?></td>
                                                <td><?= e($record['jenis_pelanggaran']) ?></td>
                                                <td><?= e($record['jenis_hukuman']) ?></td>
                                                <td><span class="points-badge"><?= $record['poin_pelanggaran'] ?></span></td>
                                                <td>
                                                    <span class="status-badge <?= $record['status_hukuman'] ?>">
                                                        <?= ucfirst($record['status_hukuman']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-check fa-4x"></i>
                                <h3><?= $can_manage ? 'No Records' : 'Perfect Record!' ?></h3>
                                <p><?= $can_manage ? 'No punishment records in the system.' : 'You have no punishment history. Keep maintaining excellent conduct!' ?>
                                </p>
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