<?php
/**
 * Nethera Class Page
 * Mediterranean of Egypt - School Management System
 * 
 * View class schedule and personal grades
 */

require_once '../includes/bootstrap.php';

Auth::requireNethera();

$user_id = Auth::id();
$user_name = Auth::name();

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

// Subject icons and colors
$subjects = [
    'english' => ['icon' => 'fa-language', 'color' => '#4a90d9', 'name' => 'English'],
    'herbology' => ['icon' => 'fa-leaf', 'color' => '#27ae60', 'name' => 'Herbology'],
    'oceanology' => ['icon' => 'fa-water', 'color' => '#00bcd4', 'name' => 'Oceanology'],
    'astronomy' => ['icon' => 'fa-star', 'color' => '#9b59b6', 'name' => 'Astronomy'],
];

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - <?= APP_NAME ?></title>

    <link rel="stylesheet" href="../assets/css/global.css" />
    <link rel="stylesheet" href="../assets/css/landing-style.css" />
    <link rel="stylesheet" href="css/beranda_style.css" />
    <link rel="stylesheet" href="css/class_style.css" />
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
            <a href="punishment.php" class="nav-btn"><i class="fa-solid fa-gavel"></i><span>Punishment</span></a>
            <a href="../logout.php" class="logout-btn-header"><i
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

                        <!-- English -->
                        <div class="subject-card english">
                            <div class="subject-icon"><i class="fa-solid fa-language"></i></div>
                            <h4>English Studies</h4>
                            <p>Master the language of scholars, diplomats, and ancient texts.</p>
                        </div>

                        <!-- Herbology -->
                        <div class="subject-card herbology">
                            <div class="subject-icon"><i class="fa-solid fa-leaf"></i></div>
                            <h4>Herbology</h4>
                            <p>Master the ancient art of healing, poisons, and magical flora of Egypt.</p>
                        </div>

                        <!-- Oceanology -->
                        <div class="subject-card oceanology">
                            <div class="subject-icon"><i class="fa-solid fa-water"></i></div>
                            <h4>Oceanology</h4>
                            <p>Study the secrets of the Nile and the mystic depths of the Mediterranean Sea.</p>
                        </div>

                        <!-- Astronomy -->
                        <div class="subject-card astronomy">
                            <div class="subject-icon"><i class="fa-solid fa-star-and-crescent"></i></div>
                            <h4>Astronomy</h4>
                            <p>Read the stars, navigate the desert sands, and predict the empire's fate.</p>
                        </div>

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

</body>

</html>