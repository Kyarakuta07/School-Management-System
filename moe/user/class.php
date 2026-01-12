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
                        <a href="subject_detail.php?subject=english" class="subject-card english">
                            <div class="subject-icon"><i class="fa-solid fa-language"></i></div>
                            <h4>English Studies</h4>
                            <p>Master the language of scholars, diplomats, and ancient texts.</p>
                        </a>

                        <!-- Herbology -->
                        <a href="subject_detail.php?subject=herbology" class="subject-card herbology">
                            <div class="subject-icon"><i class="fa-solid fa-leaf"></i></div>
                            <h4>Herbology</h4>
                            <p>Master the ancient art of healing, poisons, and magical flora of Egypt.</p>
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
                        document.getElementById('grade-english').value = data.grades.english || 0;
                        document.getElementById('grade-herbology').value = data.grades.herbology || 0;
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
                    english: parseInt(document.getElementById('grade-english').value) || 0,
                    herbology: parseInt(document.getElementById('grade-herbology').value) || 0,
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

</body>

</html>