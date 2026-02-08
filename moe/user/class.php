<?php
/**
 * Nethera Class Page
 * Mediterranean of Egypt - School Management System
 * 
 * View class schedule and personal grades
 */

require_once '../core/bootstrap.php';

// Allow Nethera, Vasiki, Anubis, and Hakaes (teacher)
$role = Auth::role();
if (!Auth::isLoggedIn() || !in_array($role, ['Nethera', 'Vasiki', 'Anubis', 'Hakaes'])) {
    redirect('../index.php?pesan=gagal_akses');
}

$user_id = Auth::id();
$user_name = Auth::name();

// Check if user can access admin dashboard
$can_access_admin = in_array($role, ['Vasiki', 'Anubis', 'Hakaes']);

// Check for active punishment (only for Nethera role)
if ($role === 'Nethera') {
    $conn = DB::getConnection();
    if (is_feature_locked($conn, $user_id, 'class')) {
        redirect('punishment.php?locked=class');
    }
}

// ==================================================
// FETCH DATA
// ==================================================

// Get all class schedules
$schedules = DB::query(
    "SELECT * FROM class_schedule ORDER BY 
     FIELD(schedule_day, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'),
     schedule_time ASC"
);

// Get user's grades
$user_grades = DB::queryOne(
    "SELECT cg.*, s.nama_sanctuary 
     FROM class_grades cg
     JOIN nethera n ON cg.id_nethera = n.id_nethera
     LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     WHERE cg.id_nethera = ?
     ORDER BY cg.id_grade DESC
     LIMIT 1",
    [$user_id]
);

// Get sanctuary leaderboard
$sanctuary_ranking = DB::query(
    "SELECT s.nama_sanctuary, SUM(cg.total_pp) as total_points, COUNT(DISTINCT cg.id_nethera) as member_count
     FROM class_grades cg
     JOIN nethera n ON cg.id_nethera = n.id_nethera
     JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     GROUP BY s.id_sanctuary
     ORDER BY total_points DESC"
);

// Get top 5 individual scholars by total PP
$top_scholars = DB::query(
    "SELECT n.id_nethera, n.nama_lengkap, s.nama_sanctuary, s.id_sanctuary, cg.total_pp
     FROM class_grades cg
     JOIN nethera n ON cg.id_nethera = n.id_nethera
     LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     WHERE n.role = 'Nethera' AND n.status_akun = 'Aktif' AND cg.total_pp > 0
     ORDER BY cg.total_pp DESC
     LIMIT 5"
);

// Get all sanctuaries for filter dropdown
$all_sanctuaries = DB::query("SELECT id_sanctuary, nama_sanctuary FROM sanctuary ORDER BY nama_sanctuary");

// Get user's sanctuary info
$user_info = DB::queryOne(
    "SELECT n.*, s.nama_sanctuary 
     FROM nethera n
     JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
     WHERE n.id_nethera = ?",
    [$user_id]
);

$user_sanctuary = $user_info['nama_sanctuary'] ?? 'Unknown';

// Calculate user's rank
$user_rank = 0;
if ($user_grades) {
    $rank_result = DB::queryValue(
        "SELECT COUNT(*) + 1 FROM class_grades WHERE total_pp > ?",
        [$user_grades['total_pp']]
    );
    $user_rank = (int) $rank_result;
}

// Subject icons and colors (unique colors for each)
$subjects = [
    'pop_culture' => ['icon' => 'fa-film', 'color' => '#e74c3c', 'name' => 'Pop Culture'],
    'mythology' => ['icon' => 'fa-ankh', 'color' => '#9b59b6', 'name' => 'Mythology'],
    'history_of_egypt' => ['icon' => 'fa-landmark', 'color' => '#f39c12', 'name' => 'History of Egypt'],
    'oceanology' => ['icon' => 'fa-water', 'color' => '#00bcd4', 'name' => 'Oceanology'],
    'astronomy' => ['icon' => 'fa-star', 'color' => '#2ecc71', 'name' => 'Astronomy'],
];

// Student Progress Tracking (for Nethera users)
$student_progress = [];
if ($role === 'Nethera') {
    foreach (array_keys($subjects) as $subj) {
        // Get total active quizzes for this subject
        $total_quizzes = DB::queryValue(
            "SELECT COUNT(*) FROM class_quizzes WHERE subject = ? AND status = 'active'",
            [$subj]
        );

        // Get completed quiz attempts for this student
        $completed = DB::queryValue(
            "SELECT COUNT(DISTINCT qa.id_quiz) 
             FROM quiz_attempts qa
             JOIN class_quizzes q ON qa.id_quiz = q.id_quiz
             WHERE qa.id_nethera = ? AND q.subject = ? AND qa.completed_at IS NOT NULL",
            [$user_id, $subj]
        );

        // Get passed quizzes
        $passed = DB::queryValue(
            "SELECT COUNT(DISTINCT qa.id_quiz) 
             FROM quiz_attempts qa
             JOIN class_quizzes q ON qa.id_quiz = q.id_quiz
             WHERE qa.id_nethera = ? AND q.subject = ? AND qa.passed = 1",
            [$user_id, $subj]
        );

        $student_progress[$subj] = [
            'total' => (int) $total_quizzes,
            'completed' => (int) $completed,
            'passed' => (int) $passed,
            'percentage' => $total_quizzes > 0 ? round(($completed / $total_quizzes) * 100) : 0
        ];
    }
}

// Check if user can manage grades (Hakaes or Vasiki)
$can_manage_grades = Auth::canManageGrades();

// For Hakaes/Vasiki: Get all Nethera students for dropdown
$all_students = [];
if ($can_manage_grades) {
    $all_students = DB::query(
        "SELECT n.id_nethera, n.nama_lengkap, n.username, s.nama_sanctuary,
                COALESCE(cg.total_pp, 0) as total_pp
         FROM nethera n
         LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
         LEFT JOIN class_grades cg ON n.id_nethera = cg.id_nethera
         WHERE n.role = 'Nethera'
         ORDER BY n.nama_lengkap ASC"
    );
}

// For Hakaes: Get their assigned subject from class_schedule
$hakaes_subject = null;
$hakaes_subject_name = null;
if (Auth::role() === 'Hakaes') {
    $schedule = DB::queryOne(
        "SELECT class_name FROM class_schedule WHERE id_hakaes = ?",
        [$user_id]
    );
    if ($schedule) {
        // Map class_name to subject key (lowercase)
        $hakaes_subject_name = $schedule['class_name'];
        $hakaes_subject = strtolower($schedule['class_name']);
    }
}

// Vasiki (admin) can edit all subjects
$is_vasiki = Auth::role() === 'Vasiki';

// For Hakaes/Vasiki: Get all grades for table view
$all_grades = [];
if ($can_manage_grades) {
    $all_grades = DB::query(
        "SELECT n.id_nethera, n.nama_lengkap, n.username, s.nama_sanctuary,
                COALESCE(cg.pop_culture, 0) as pop_culture,
                COALESCE(cg.mythology, 0) as mythology,
                COALESCE(cg.history_of_egypt, 0) as history_of_egypt,
                COALESCE(cg.oceanology, 0) as oceanology,
                COALESCE(cg.astronomy, 0) as astronomy,
                COALESCE(cg.total_pp, 0) as total_pp,
                cg.class_name
         FROM nethera n
         LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
         LEFT JOIN class_grades cg ON n.id_nethera = cg.id_nethera
         WHERE n.role = 'Nethera'
         ORDER BY cg.total_pp DESC, n.nama_lengkap ASC"
    );
}

// For Hakaes/Vasiki: Get quiz attempts for monitoring
$quiz_results = [];
if ($can_manage_grades) {
    $subject_filter = ($hakaes_subject && !$is_vasiki) ? "AND q.subject = ?" : "";
    $params = ($hakaes_subject && !$is_vasiki) ? [$hakaes_subject] : [];

    $quiz_results = DB::query(
        "SELECT qa.id_attempt, qa.score, qa.max_score, qa.percentage, qa.passed, qa.completed_at,
                q.title as quiz_title, q.subject,
                n.nama_lengkap, n.username,
                s.nama_sanctuary
         FROM quiz_attempts qa
         JOIN class_quizzes q ON qa.id_quiz = q.id_quiz
         JOIN nethera n ON qa.id_nethera = n.id_nethera
         LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
         WHERE qa.completed_at IS NOT NULL $subject_filter
         ORDER BY qa.completed_at DESC
         LIMIT 50",
        $params
    );

    // Quiz Analytics Statistics
    $quiz_stats = DB::queryOne(
        "SELECT 
            COUNT(*) as total_attempts,
            ROUND(AVG(qa.percentage), 1) as avg_score,
            SUM(CASE WHEN qa.passed = 1 THEN 1 ELSE 0 END) as passed_count,
            ROUND(SUM(CASE WHEN qa.passed = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as pass_rate
         FROM quiz_attempts qa
         JOIN class_quizzes q ON qa.id_quiz = q.id_quiz
         WHERE qa.completed_at IS NOT NULL $subject_filter",
        $params
    );

    // Best performing quiz
    $best_quiz = DB::queryOne(
        "SELECT q.title, ROUND(AVG(qa.percentage), 1) as avg_score, COUNT(*) as attempts
         FROM quiz_attempts qa
         JOIN class_quizzes q ON qa.id_quiz = q.id_quiz
         WHERE qa.completed_at IS NOT NULL $subject_filter
         GROUP BY q.id_quiz
         HAVING COUNT(*) >= 3
         ORDER BY AVG(qa.percentage) DESC
         LIMIT 1",
        $params
    );
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - <?= APP_NAME ?></title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="View your class schedule, grades, and quizzes at MOE. Track your academic progress across History, Herbology, Oceanology, and Astronomy.">
    <meta name="keywords" content="MOE classes, school schedule, grades, quizzes, academic progress, student portal">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Classes - MOE School System">
    <meta property="og:description" content="Track your academic progress and class schedule.">
    <meta property="og:image" content="../assets/landing/logo.png">

    <!-- Theme Color -->
    <meta name="theme-color" content="#0a0a0a">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/landing/logo.png">
    <link rel="apple-touch-icon" href="../assets/landing/logo.png">

    <link rel="stylesheet" href="<?= asset('assets/css/global.css', '../') ?>" />
    <link rel="stylesheet" href="<?= asset('assets/css/landing-style.css', '../') ?>" />
    <link rel="stylesheet" href="<?= asset('user/css/beranda_style.css', '../') ?>" />
    <link rel="stylesheet" href="<?= asset('user/css/class_style.css', '../') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <div class="main-dashboard-wrapper">

        <header class="top-user-header">
            <h1 class="main-h1 cinzel-title">ANCIENT KNOWLEDGE</h1>
            <p class="main-h2">Unlock the mysteries of the Mediterranean</p>
        </header>

        <nav class="top-nav-menu">
            <a href="beranda.php" class="nav-btn"><i class="fa-solid fa-home"></i><span>Home</span></a>
            <a href="class.php" class="nav-btn active"><i class="fa-solid fa-book-open"></i><span>Class</span></a>
            <a href="pet.php" class="nav-btn"><i class="fa-solid fa-paw"></i><span>Pet</span></a>
            <a href="trapeza.php" class="nav-btn"><i class="fa-solid fa-credit-card"></i><span>Trapeza</span></a>
            <a href="punishment.php" class="nav-btn"><i class="fa-solid fa-gavel"></i><span>Rules</span></a>
            <a href="../auth/handlers/logout.php" class="logout-btn-header"><i
                    class="fa-solid fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>

        <main class="class-main-content">

            <!-- LEFT COLUMN: My Grades & Ranking -->
            <section class="class-sidebar">

                <!-- MY GRADES CARD -->
                <div class="class-card grades-card">
                    <h3 class="card-title"><i class="fa-solid fa-scroll"></i> MY GRADES</h3>

                    <?php if ($user_grades): ?>
                        <div class="grades-summary">
                            <div class="total-pp">
                                <span class="pp-value"><?= number_format($user_grades['total_pp']) ?></span>
                                <span class="pp-label">Prestige Points</span>
                            </div>
                            <?php if ($user_rank > 0): ?>
                                <div class="rank-badge">
                                    <i class="fa-solid fa-medal"></i>
                                    <span>Rank #<?= $user_rank ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="grades-grid">
                            <?php foreach ($subjects as $key => $subject): ?>
                                <div class="grade-item" style="--subject-color: <?= $subject['color'] ?>">
                                    <i class="fa-solid <?= $subject['icon'] ?>"></i>
                                    <div class="grade-info">
                                        <span class="grade-name"><?= $subject['name'] ?></span>
                                        <span class="grade-value"><?= $user_grades[$key] ?? 0 ?> PP</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-grades">
                            <i class="fa-solid fa-question-circle"></i>
                            <p>Belum ada data nilai.</p>
                            <small>Nilai akan muncul setelah mengikuti kelas.</small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TOP SCHOLARS LEADERBOARD -->
                <div class="class-card top-scholars-card">
                    <div class="card-header-row">
                        <h3 class="card-title"><i class="fa-solid fa-trophy"></i> TOP SCHOLARS</h3>
                        <select id="sanctuary-filter" class="sanctuary-filter" onchange="filterScholars(this.value)">
                            <option value="">All Sanctuaries</option>
                            <?php foreach ($all_sanctuaries as $sanctuary): ?>
                                <option value="<?= $sanctuary['id_sanctuary'] ?>">
                                    <?= htmlspecialchars($sanctuary['nama_sanctuary']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="scholars-list" id="scholars-list">
                        <?php if (!empty($top_scholars)): ?>
                            <?php $rank = 1;
                            foreach ($top_scholars as $scholar): ?>
                                <div class="scholar-row rank-<?= $rank ?>" data-sanctuary="<?= $scholar['id_sanctuary'] ?>">
                                    <div class="scholar-rank">
                                        <?php if ($rank === 1): ?>
                                            <span class="rank-icon gold">👑</span>
                                        <?php elseif ($rank === 2): ?>
                                            <span class="rank-icon silver">🥈</span>
                                        <?php elseif ($rank === 3): ?>
                                            <span class="rank-icon bronze">🥉</span>
                                        <?php else: ?>
                                            <span class="rank-number">#<?= $rank ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="scholar-info">
                                        <span class="scholar-name"><?= htmlspecialchars($scholar['nama_lengkap']) ?></span>
                                        <span class="scholar-sanctuary">
                                            <i class="fa-solid fa-shield-halved"></i>
                                            <?= htmlspecialchars($scholar['nama_sanctuary'] ?? 'Unknown') ?>
                                        </span>
                                    </div>
                                    <div class="scholar-pp">
                                        <span class="pp-value"><?= number_format($scholar['total_pp']) ?></span>
                                        <span class="pp-label">PP</span>
                                    </div>
                                </div>
                                <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <div class="no-scholars">
                                <i class="fa-solid fa-users-slash"></i>
                                <p>No rankings available yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($role === 'Nethera' && !empty($student_progress)): ?>
                    <!-- STUDENT PROGRESS -->
                    <div class="class-card" style="border-color: rgba(52, 152, 219, 0.4);">
                        <h3 class="card-title" style="color: #3498db;">
                            <i class="fa-solid fa-tasks"></i> MY QUIZ PROGRESS
                        </h3>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($subjects as $key => $subject):
                                $progress = $student_progress[$key] ?? ['total' => 0, 'completed' => 0, 'passed' => 0, 'percentage' => 0];
                                ?>
                                <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px;">
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fa-solid <?= $subject['icon'] ?>"
                                                style="color: <?= $subject['color'] ?>;"></i>
                                            <span style="font-weight: 500; color: #fff;"><?= $subject['name'] ?></span>
                                        </div>
                                        <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">
                                            <?= $progress['completed'] ?>/<?= $progress['total'] ?> quizzes
                                        </span>
                                    </div>
                                    <div
                                        style="background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; overflow: hidden;">
                                        <div
                                            style="width: <?= $progress['percentage'] ?>%; height: 100%; background: <?= $subject['color'] ?>; transition: width 0.5s;">
                                        </div>
                                    </div>
                                    <?php if ($progress['passed'] > 0): ?>
                                        <div style="font-size: 0.7rem; color: #2ecc71; margin-top: 6px;">
                                            <i class="fa-solid fa-check-circle"></i> <?= $progress['passed'] ?> passed
                                        </div>
                                    <?php elseif ($progress['total'] > 0 && $progress['completed'] == 0): ?>
                                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-top: 6px;">
                                            <i class="fa-solid fa-clock"></i> Not started
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- SANCTUARY RANKING -->
                <div class="class-card ranking-card">
                    <h3 class="card-title"><i class="fa-solid fa-trophy"></i> SANCTUARY RANKING</h3>

                    <div class="ranking-list">
                        <?php
                        $rank = 0;
                        foreach ($sanctuary_ranking as $sanctuary):
                            $rank++;
                            $is_mine = ($sanctuary['nama_sanctuary'] === $user_sanctuary);
                            ?>
                            <div class="ranking-item <?= $is_mine ? 'my-sanctuary' : '' ?>">
                                <span class="rank-position rank-<?= $rank ?>">#<?= $rank ?></span>
                                <div class="rank-info">
                                    <span class="rank-name"><?= e($sanctuary['nama_sanctuary']) ?></span>
                                    <span class="rank-members"><?= $sanctuary['member_count'] ?> members</span>
                                </div>
                                <span class="rank-points"><?= number_format($sanctuary['total_points']) ?> PP</span>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($sanctuary_ranking)): ?>
                            <p class="no-data">Belum ada data ranking.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($can_manage_grades): ?>
                    <!-- HAKAES: Grade Management Panel -->
                    <div class="class-card hakaes-panel">
                        <h3 class="card-title"><i class="fa-solid fa-chalkboard-teacher"></i> GRADE MANAGEMENT</h3>

                        <div class="hakaes-form">
                            <div class="form-group">
                                <label for="student-select">Select Student</label>
                                <select id="student-select" class="form-control">
                                    <option value="">-- Pilih Siswa --</option>
                                    <?php foreach ($all_students as $student): ?>
                                        <option value="<?= $student['id_nethera'] ?>"
                                            data-name="<?= e($student['nama_lengkap']) ?>"
                                            data-sanctuary="<?= e($student['nama_sanctuary']) ?>">
                                            <?= e($student['nama_lengkap']) ?> (@<?= e($student['username']) ?>) -
                                            <?= $student['total_pp'] ?> PP
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="grade-form" class="grade-form hidden">
                                <div class="student-info-display">
                                    <span id="selected-student-name">-</span>
                                    <span id="selected-student-sanctuary" class="sanctuary-tag">-</span>
                                </div>

                                <div class="grade-inputs">
                                    <?php foreach ($subjects as $key => $subject): ?>
                                        <?php
                                        // Skip if Hakaes and not their assigned subject
                                        if (!$is_vasiki && $hakaes_subject !== null && $key !== $hakaes_subject)
                                            continue;
                                        ?>
                                        <div class="grade-input-item" style="--subject-color: <?= $subject['color'] ?>">
                                            <label><i class="fa-solid <?= $subject['icon'] ?>"></i>
                                                <?= $subject['name'] ?></label>
                                            <input type="number" id="grade-<?= $key ?>" name="<?= $key ?>" min="0" max="100"
                                                placeholder="PP" class="grade-input">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="total-preview">
                                    <span>Total PP:</span>
                                    <span id="total-pp-preview" class="pp-value">0</span>
                                </div>

                                <button type="button" id="save-grades-btn" class="btn-hakaes">
                                    <i class="fa-solid fa-save"></i> Save Grades
                                </button>
                            </div>

                            <div id="grade-result" class="grade-result hidden"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($can_manage_grades && !empty($all_grades)): ?>
                    <!-- ALL CLASS GRADES TABLE -->
                    <div class="class-card grades-table-card">
                        <h3 class="card-title">
                            <i class="fa-solid fa-table"></i>
                            <?= $hakaes_subject_name ? strtoupper($hakaes_subject_name) . ' GRADES' : 'ALL CLASS GRADES' ?>
                        </h3>

                        <div class="grades-table-wrapper">
                            <table class="grades-table">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Sanctuary</th>
                                        <?php if ($is_vasiki || $hakaes_subject === 'pop_culture'): ?>
                                            <th>Pop Culture</th>
                                        <?php endif; ?>
                                        <?php if ($is_vasiki || $hakaes_subject === 'mythology'): ?>
                                            <th>Mythology</th>
                                        <?php endif; ?>
                                        <?php if ($is_vasiki || $hakaes_subject === 'history_of_egypt'): ?>
                                            <th>History of Egypt</th>
                                        <?php endif; ?>
                                        <?php if ($is_vasiki || $hakaes_subject === 'oceanology'): ?>
                                            <th>Oceanology</th>
                                        <?php endif; ?>
                                        <?php if ($is_vasiki || $hakaes_subject === 'astronomy'): ?>
                                            <th>Astronomy</th>
                                        <?php endif; ?>
                                        <th>Total PP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_grades as $grade): ?>
                                        <tr>
                                            <td data-label="Nama"><?= e($grade['nama_lengkap']) ?></td>
                                            <td data-label="Sanctuary"><?= e($grade['nama_sanctuary'] ?? '-') ?></td>
                                            <?php if ($is_vasiki || $hakaes_subject === 'pop_culture'): ?>
                                                <td data-label="Pop Culture"><?= $grade['pop_culture'] ?></td>
                                            <?php endif; ?>
                                            <?php if ($is_vasiki || $hakaes_subject === 'mythology'): ?>
                                                <td data-label="Mythology"><?= $grade['mythology'] ?></td>
                                            <?php endif; ?>
                                            <?php if ($is_vasiki || $hakaes_subject === 'history_of_egypt'): ?>
                                                <td data-label="History of Egypt"><?= $grade['history_of_egypt'] ?></td>
                                            <?php endif; ?>
                                            <?php if ($is_vasiki || $hakaes_subject === 'oceanology'): ?>
                                                <td data-label="Oceanology"><?= $grade['oceanology'] ?></td>
                                            <?php endif; ?>
                                            <?php if ($is_vasiki || $hakaes_subject === 'astronomy'): ?>
                                                <td data-label="Astronomy"><?= $grade['astronomy'] ?></td>
                                            <?php endif; ?>
                                            <td data-label="Total PP"><strong><?= $grade['total_pp'] ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <p class="grades-count"><?= count($all_grades) ?> siswa</p>
                    </div>
                <?php endif; ?>

                <?php if ($can_manage_grades && isset($quiz_stats) && $quiz_stats['total_attempts'] > 0): ?>
                    <!-- QUIZ ANALYTICS DASHBOARD -->
                    <div class="class-card" style="border-color: rgba(155, 89, 182, 0.4);">
                        <h3 class="card-title" style="color: #9b59b6;">
                            <i class="fa-solid fa-chart-line"></i>
                            <?= $hakaes_subject_name ? strtoupper($hakaes_subject_name) . ' ANALYTICS' : 'QUIZ ANALYTICS' ?>
                        </h3>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 16px;">
                            <!-- Total Attempts -->
                            <div
                                style="background: linear-gradient(145deg, rgba(155, 89, 182, 0.15), rgba(155, 89, 182, 0.05)); padding: 16px; border-radius: 12px; text-align: center; border: 1px solid rgba(155, 89, 182, 0.2);">
                                <div style="font-size: 1.8rem; font-weight: 700; color: #9b59b6;">
                                    <?= $quiz_stats['total_attempts'] ?>
                                </div>
                                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Total
                                    Attempts</div>
                            </div>

                            <!-- Average Score -->
                            <div
                                style="background: linear-gradient(145deg, rgba(52, 152, 219, 0.15), rgba(52, 152, 219, 0.05)); padding: 16px; border-radius: 12px; text-align: center; border: 1px solid rgba(52, 152, 219, 0.2);">
                                <div style="font-size: 1.8rem; font-weight: 700; color: #3498db;">
                                    <?= $quiz_stats['avg_score'] ?? 0 ?>%
                                </div>
                                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Avg Score
                                </div>
                            </div>

                            <!-- Pass Rate -->
                            <div
                                style="background: linear-gradient(145deg, rgba(46, 204, 113, 0.15), rgba(46, 204, 113, 0.05)); padding: 16px; border-radius: 12px; text-align: center; border: 1px solid rgba(46, 204, 113, 0.2);">
                                <div style="font-size: 1.8rem; font-weight: 700; color: #2ecc71;">
                                    <?= $quiz_stats['pass_rate'] ?? 0 ?>%
                                </div>
                                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Pass Rate
                                </div>
                            </div>

                            <!-- Passed Count -->
                            <div
                                style="background: linear-gradient(145deg, rgba(212, 175, 55, 0.15), rgba(212, 175, 55, 0.05)); padding: 16px; border-radius: 12px; text-align: center; border: 1px solid rgba(212, 175, 55, 0.2);">
                                <div style="font-size: 1.8rem; font-weight: 700; color: #d4af37;">
                                    <?= $quiz_stats['passed_count'] ?? 0 ?>
                                </div>
                                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Passed</div>
                            </div>
                        </div>

                        <?php if (isset($best_quiz) && $best_quiz): ?>
                            <div
                                style="background: rgba(46, 204, 113, 0.1); padding: 12px 16px; border-radius: 8px; border-left: 3px solid #2ecc71;">
                                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-bottom: 4px;">🏆 Best
                                    Performing Quiz</div>
                                <div style="color: #fff; font-weight: 600;"><?= e($best_quiz['title']) ?></div>
                                <div style="font-size: 0.8rem; color: #2ecc71; margin-top: 4px;">
                                    Avg: <?= $best_quiz['avg_score'] ?>% (<?= $best_quiz['attempts'] ?> attempts)
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($can_manage_grades && !empty($quiz_results)): ?>
                    <!-- QUIZ RESULTS TABLE -->
                    <div class="class-card quiz-results-card">
                        <h3 class="card-title">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <?= $hakaes_subject_name ? strtoupper($hakaes_subject_name) . ' QUIZ RESULTS' : 'ALL QUIZ RESULTS' ?>
                        </h3>

                        <div class="grades-table-wrapper">
                            <table class="grades-table quiz-results-table">
                                <thead>
                                    <tr>
                                        <th>Siswa</th>
                                        <?php if ($is_vasiki): ?>
                                            <th>Subject</th>
                                        <?php endif; ?>
                                        <th>Quiz</th>
                                        <th>Score</th>
                                        <th>%</th>
                                        <th>Status</th>
                                        <th>Selesai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quiz_results as $result): ?>
                                        <tr>
                                            <td data-label="Siswa"><?= e($result['nama_lengkap']) ?></td>
                                            <?php if ($is_vasiki): ?>
                                                <td data-label="Subject"><?= ucfirst($result['subject']) ?></td>
                                            <?php endif; ?>
                                            <td data-label="Quiz"><?= e($result['quiz_title']) ?></td>
                                            <td data-label="Score"><?= $result['score'] ?>/<?= $result['max_score'] ?></td>
                                            <td data-label="Persen"><?= number_format($result['percentage'], 0) ?>%</td>
                                            <td data-label="Status">
                                                <?php if ($result['passed']): ?>
                                                    <span class="status-badge passed">✓ Pass</span>
                                                <?php else: ?>
                                                    <span class="status-badge failed">✗ Fail</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Selesai"><?= date('d M, H:i', strtotime($result['completed_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <p class="grades-count"><?= count($quiz_results) ?> hasil quiz</p>
                    </div>
                <?php endif; ?>

            </section>

            <!-- RIGHT COLUMN: Schedule & Subjects -->
            <section class="class-content">

                <!-- CLASS SCHEDULE -->
                <div class="class-card schedule-card">
                    <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> CLASS SCHEDULE</h3>

                    <?php if (!empty($schedules)): ?>
                        <div class="schedule-grid">
                            <?php foreach ($schedules as $schedule): ?>
                                <div class="schedule-item">
                                    <div class="schedule-day">
                                        <span class="day-name"><?= e($schedule['schedule_day']) ?></span>
                                        <span class="day-time"><?= e($schedule['schedule_time']) ?></span>
                                    </div>
                                    <div class="schedule-details">
                                        <span class="class-name"><?= e($schedule['class_name']) ?></span>
                                        <span class="teacher-name">
                                            <i class="fa-solid fa-user-graduate"></i>
                                            <?= e($schedule['hakaes_name']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-schedule">
                            <i class="fa-solid fa-calendar-xmark"></i>
                            <p>Jadwal kelas belum tersedia.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SUBJECT CARDS -->
                <div class="class-card subjects-card">
                    <h3 class="card-title"><i class="fa-solid fa-books"></i> SUBJECTS</h3>

                    <div class="subjects-grid">

                        <!-- Pop Culture -->
                        <a href="subject_detail.php?subject=pop_culture" class="subject-card pop_culture">
                            <div class="subject-icon"><i class="fa-solid fa-film"></i></div>
                            <h4>Pop Culture</h4>
                            <p>Explore modern Egyptian influence in movies, games, music, and global media.</p>
                        </a>

                        <!-- Mythology -->
                        <a href="subject_detail.php?subject=mythology" class="subject-card mythology">
                            <div class="subject-icon"><i class="fa-solid fa-ankh"></i></div>
                            <h4>Mythology</h4>
                            <p>Discover the stories of Ra, Osiris, Isis, and the ancient Egyptian pantheon.</p>
                        </a>

                        <!-- History of Egypt -->
                        <a href="subject_detail.php?subject=history_of_egypt" class="subject-card history_of_egypt">
                            <div class="subject-icon"><i class="fa-solid fa-landmark"></i></div>
                            <h4>History of Egypt</h4>
                            <p>Journey through the ages of Pharaohs, pyramids, and the rise of civilization.</p>
                        </a>

                        <!-- Oceanology -->
                        <a href="subject_detail.php?subject=oceanology" class="subject-card oceanology">
                            <div class="subject-icon"><i class="fa-solid fa-water"></i></div>
                            <h4>Oceanology</h4>
                            <p>Study the secrets of the Nile and the mystic depths of the Mediterranean Sea.</p>
                        </a>

                        <!-- Astronomy -->
                        <a href="subject_detail.php?subject=astronomy" class="subject-card astronomy">
                            <div class="subject-icon"><i class="fa-solid fa-star-and-crescent"></i></div>
                            <h4>Astronomy</h4>
                            <p>Read the stars, navigate the desert sands, and predict the empire's fate.</p>
                        </a>

                    </div>
                </div>

            </section>

        </main>
    </div>

    <!-- BOTTOM NAVIGATION (Mobile Only) -->
    <nav class="bottom-nav">
        <a href="beranda.php" class="bottom-nav-item">
            <i class="fa-solid fa-home"></i>
            <span>Home</span>
        </a>
        <a href="class.php" class="bottom-nav-item active">
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

    <?php if ($can_manage_grades): ?>
        <!-- Hakaes Panel Styles -->
        <style>
            .hakaes-panel {
                border: 2px solid rgba(76, 175, 80, 0.3);
                background: linear-gradient(145deg, rgba(76, 175, 80, 0.1), rgba(30, 30, 35, 0.95));
            }

            .hakaes-panel .card-title {
                color: #4caf50;
            }

            .hakaes-form {
                padding: 16px 0;
            }

            .form-group {
                margin-bottom: 16px;
            }

            .form-group label {
                display: block;
                margin-bottom: 6px;
                color: #ccc;
                font-size: 0.85rem;
            }

            .form-control {
                width: 100%;
                padding: 12px;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                color: #fff;
                font-size: 0.95rem;
            }

            .form-control:focus {
                outline: none;
                border-color: #4caf50;
                background: rgba(255, 255, 255, 0.08);
            }

            .hidden {
                display: none !important;
            }

            .student-info-display {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 12px;
                background: rgba(76, 175, 80, 0.15);
                border-radius: 8px;
                margin-bottom: 16px;
            }

            #selected-student-name {
                font-weight: 600;
                color: #fff;
            }

            .sanctuary-tag {
                padding: 4px 10px;
                background: rgba(212, 175, 55, 0.2);
                border-radius: 12px;
                font-size: 0.75rem;
                color: #d4af37;
            }

            .grade-inputs {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                margin-bottom: 16px;
            }

            .grade-input-item {
                padding: 10px;
                background: rgba(255, 255, 255, 0.03);
                border-left: 3px solid var(--subject-color, #d4af37);
                border-radius: 6px;
            }

            .grade-input-item label {
                font-size: 0.8rem;
                color: var(--subject-color);
                margin-bottom: 6px;
            }

            .grade-input {
                width: 100%;
                padding: 8px;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 4px;
                color: #fff;
                font-size: 1rem;
                text-align: center;
            }

            .grade-input:focus {
                border-color: var(--subject-color);
                outline: none;
            }

            .total-preview {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px;
                background: rgba(212, 175, 55, 0.1);
                border-radius: 8px;
                margin-bottom: 16px;
            }

            .total-preview .pp-value {
                font-size: 1.4rem;
                font-weight: 700;
                color: #d4af37;
            }

            .btn-hakaes {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #4caf50, #388e3c);
                border: none;
                color: #fff;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all 0.3s;
            }

            .btn-hakaes:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
            }

            .grade-result {
                padding: 12px;
                border-radius: 8px;
                margin-top: 12px;
                text-align: center;
            }

            .grade-result.success {
                background: rgba(76, 175, 80, 0.2);
                color: #4caf50;
            }

            .grade-result.error {
                background: rgba(231, 76, 60, 0.2);
                color: #e74c3c;
            }

            @media (max-width: 480px) {
                .grade-inputs {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <!-- Hakaes JavaScript -->
        <script>
            const csrfToken = '<?= $csrf_token ?>';
            const studentSelect = document.getElementById('student-select');
            const gradeForm = document.getElementById('grade-form');
            const saveBtn = document.getElementById('save-grades-btn');
            const resultDiv = document.getElementById('grade-result');
            const gradeInputs = document.querySelectorAll('.grade-input');

            // Calculate and update total PP
            function updateTotalPP() {
                let total = 0;
                gradeInputs.forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                document.getElementById('total-pp-preview').textContent = total;
            }

            // Listen for input changes
            gradeInputs.forEach(input => {
                input.addEventListener('input', updateTotalPP);
            });

            // Student selection
            studentSelect.addEventListener('change', async function () {
                const studentId = this.value;
                if (!studentId) {
                    gradeForm.classList.add('hidden');
                    return;
                }

                const selected = this.options[this.selectedIndex];
                document.getElementById('selected-student-name').textContent = selected.dataset.name;
                document.getElementById('selected-student-sanctuary').textContent = selected.dataset.sanctuary;

                // Fetch existing grades
                try {
                    const response = await fetch(`api/router.php?controller=class&action=getGrades&student_id=${studentId}`);
                    const data = await response.json();

                    if (data.success && data.grades) {
                        document.getElementById('grade-history').value = data.grades.history || 0;
                        document.getElementById('grade-pop_culture').value = data.grades.pop_culture || 0;
                        document.getElementById('grade-mythology').value = data.grades.mythology || 0;
                        document.getElementById('grade-history_of_egypt').value = data.grades.history_of_egypt || 0;
                        document.getElementById('grade-oceanology').value = data.grades.oceanology || 0;
                        document.getElementById('grade-astronomy').value = data.grades.astronomy || 0;
                    } else {
                        gradeInputs.forEach(input => input.value = 0);
                    }
                    updateTotalPP();
                } catch (err) {
                    console.error('Failed to fetch grades:', err);
                    gradeInputs.forEach(input => input.value = 0);
                }

                gradeForm.classList.remove('hidden');
                resultDiv.classList.add('hidden');
            });

            // Save grades
            saveBtn.addEventListener('click', async function () {
                const studentId = studentSelect.value;
                if (!studentId) return;

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

                const grades = {
                    history: parseInt(document.getElementById('grade-history').value) || 0,
                    pop_culture: parseInt(document.getElementById('grade-pop_culture').value) || 0,
                    mythology: parseInt(document.getElementById('grade-mythology').value) || 0,
                    history_of_egypt: parseInt(document.getElementById('grade-history_of_egypt').value) || 0,
                    oceanology: parseInt(document.getElementById('grade-oceanology').value) || 0,
                    astronomy: parseInt(document.getElementById('grade-astronomy').value) || 0
                };

                try {
                    const response = await fetch('api/router.php?controller=class&action=updateGrades', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            csrf_token: csrfToken,
                            student_id: studentId,
                            grades: grades
                        })
                    });

                    const data = await response.json();
                    resultDiv.classList.remove('hidden', 'success', 'error');
                    resultDiv.classList.add(data.success ? 'success' : 'error');
                    resultDiv.textContent = data.message || (data.success ? 'Grades saved!' : 'Failed to save');
                } catch (err) {
                    resultDiv.classList.remove('hidden', 'success');
                    resultDiv.classList.add('error');
                    resultDiv.textContent = 'Network error. Please try again.';
                }

                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fa-solid fa-save"></i> Save Grades';
            });
        </script>
    <?php endif; ?>

    <!-- Top Scholars Filter Script -->
    <script>
        // Store original scholars HTML for reset
        const originalScholarsHTML = document.getElementById('scholars-list')?.innerHTML || '';

        function filterScholars(sanctuaryId) {
            const scholarsList = document.getElementById('scholars-list');
            if (!scholarsList) return;

            // Reset to original if no filter
            if (!sanctuaryId) {
                scholarsList.innerHTML = originalScholarsHTML;
                return;
            }

            const rows = scholarsList.querySelectorAll('.scholar-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const rowSanctuary = row.getAttribute('data-sanctuary');
                if (rowSanctuary === sanctuaryId) {
                    row.style.display = 'flex';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show message if no results
            if (visibleCount === 0) {
                const existingMsg = scholarsList.querySelector('.no-scholars');
                if (!existingMsg) {
                    const msg = document.createElement('div');
                    msg.className = 'no-scholars filter-msg';
                    msg.innerHTML = '<i class="fa-solid fa-filter"></i><p>No scholars from this sanctuary.</p>';
                    scholarsList.appendChild(msg);
                }
            } else {
                const filterMsg = scholarsList.querySelector('.filter-msg');
                if (filterMsg) filterMsg.remove();
            }
        }
    </script>

</body>

</html>