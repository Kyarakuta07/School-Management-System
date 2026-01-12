<?php
/**
 * Subject Detail Page
 * Mediterranean of Egypt - School Management System
 * 
 * View materials for a specific subject (History, Herbology, Oceanology, Astronomy)
 */

require_once '../core/bootstrap.php';

// Allow Nethera, Vasiki, Anubis, and Hakaes
$role = Auth::role();
if (!Auth::isLoggedIn() || !in_array($role, ['Nethera', 'Vasiki', 'Anubis', 'Hakaes'])) {
    redirect('../index.php?pesan=gagal_akses');
}

$user_id = Auth::id();
$user_name = Auth::name();
$can_manage = Auth::canManageGrades(); // Hakaes or Vasiki

// Get subject from URL
$subject = $_GET['subject'] ?? '';
$valid_subjects = ['history', 'herbology', 'oceanology', 'astronomy'];

if (!in_array($subject, $valid_subjects)) {
    redirect('class.php');
}

// Subject metadata
$subjects = [
    'history' => ['icon' => 'fa-landmark', 'color' => '#4a90d9', 'name' => 'History', 'desc' => 'Explore the chronicles of ancient Egypt, pharaohs, and the rise of civilizations.'],
    'herbology' => ['icon' => 'fa-leaf', 'color' => '#27ae60', 'name' => 'Herbology', 'desc' => 'Master the ancient art of healing, poisons, and magical flora of Egypt.'],
    'oceanology' => ['icon' => 'fa-water', 'color' => '#00bcd4', 'name' => 'Oceanology', 'desc' => 'Study the secrets of the Nile and the mystic depths of the Mediterranean Sea.'],
    'astronomy' => ['icon' => 'fa-star', 'color' => '#9b59b6', 'name' => 'Astronomy', 'desc' => 'Read the stars, navigate the desert sands, and predict the empire\'s fate.'],
];

$current_subject = $subjects[$subject];

// Fetch materials for this subject
$materials = DB::query(
    "SELECT m.*, n.nama_lengkap as creator_name 
     FROM class_materials m
     LEFT JOIN nethera n ON m.created_by = n.id_nethera
     WHERE m.subject = ? AND m.is_active = 1
     ORDER BY m.created_at DESC",
    [$subject]
);

// Fetch quizzes for this subject
if ($can_manage) {
    $quizzes = DB::query(
        "SELECT q.*, COUNT(qq.id_question) as question_count
         FROM class_quizzes q
         LEFT JOIN quiz_questions qq ON q.id_quiz = qq.id_quiz
         WHERE q.subject = ?
         GROUP BY q.id_quiz
         ORDER BY q.created_at DESC",
        [$subject]
    );
} else {
    $quizzes = DB::query(
        "SELECT q.*, COUNT(qq.id_question) as question_count,
                (SELECT COUNT(*) FROM quiz_attempts WHERE id_quiz = q.id_quiz AND id_nethera = ?) as attempts_used
         FROM class_quizzes q
         LEFT JOIN quiz_questions qq ON q.id_quiz = qq.id_quiz
         WHERE q.subject = ? AND q.status = 'active'
         GROUP BY q.id_quiz
         ORDER BY q.created_at DESC",
        [$user_id, $subject]
    );
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>
        <?= $current_subject['name'] ?> -
        <?= APP_NAME ?>
    </title>

    <link rel="stylesheet" href="../assets/css/global.css" />
    <link rel="stylesheet" href="../assets/css/landing-style.css" />
    <link rel="stylesheet" href="css/beranda_style.css" />
    <link rel="stylesheet" href="css/class_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .subject-header {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(145deg, rgba(30, 30, 35, 0.95), rgba(20, 20, 25, 0.98));
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .subject-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 2rem;
            color: #fff;
        }

        .subject-header h1 {
            font-size: 1.6rem;
            color: #fff;
            margin-bottom: 8px;
        }

        .subject-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.95rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Materials Grid */
        .materials-grid {
            display: grid;
            gap: 16px;
        }

        .material-card {
            background: linear-gradient(145deg, rgba(40, 40, 45, 0.9), rgba(30, 30, 35, 0.95));
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }

        .material-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .material-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .material-type-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .material-type-icon.text {
            background: rgba(212, 175, 55, 0.2);
            color: #d4af37;
        }

        .material-type-icon.youtube {
            background: rgba(255, 0, 0, 0.2);
            color: #ff0000;
        }

        .material-type-icon.pdf {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .material-title {
            font-weight: 600;
            color: #fff;
            font-size: 1.1rem;
            flex: 1;
        }

        .material-meta {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 12px;
        }

        .material-content {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

        .material-content p {
            margin-bottom: 12px;
        }

        /* Collapsible text content */
        .text-content-wrapper {
            position: relative;
        }

        .text-content {
            max-height: 150px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .text-content.expanded {
            max-height: none;
        }

        .text-content-wrapper.has-overflow::after {
            content: '';
            position: absolute;
            bottom: 40px;
            left: 0;
            right: 0;
            height: 50px;
            background: linear-gradient(transparent, rgba(40, 40, 45, 1));
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .text-content-wrapper.expanded::after {
            opacity: 0;
        }

        .read-more-btn {
            display: none;
            width: 100%;
            padding: 10px;
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: #d4af37;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-top: 8px;
            transition: all 0.3s;
        }

        .read-more-btn:hover {
            background: rgba(212, 175, 55, 0.2);
        }

        .text-content-wrapper.has-overflow .read-more-btn {
            display: block;
        }

        /* YouTube Embed */
        .youtube-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 8px;
            margin-top: 12px;
        }

        .youtube-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        /* PDF Actions */
        .pdf-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-top: 12px;
        }

        .pdf-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .pdf-btn.view {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .pdf-btn.view:hover {
            background: rgba(231, 76, 60, 0.4);
        }

        .pdf-btn.download {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .pdf-btn.download:hover {
            background: rgba(76, 175, 80, 0.4);
        }

        .pdf-filename {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            flex-basis: 100%;
        }

        /* Empty State */
        .empty-materials {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.5);
        }

        .empty-materials i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        /* Hakaes Add Button */
        .add-material-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #4caf50, #388e3c);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .add-material-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }

        /* Add Material Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(145deg, #2a2a30, #1e1e22);
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            color: #fff;
            font-size: 1.3rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            color: #ccc;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4caf50, #388e3c);
            border: none;
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        @media (max-width: 480px) {
            .subject-header {
                padding: 20px 15px;
            }

            .subject-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .subject-header h1 {
                font-size: 1.3rem;
            }

            .material-card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

    <div class="bg-fixed"></div>
    <div class="bg-overlay"></div>

    <div class="main-dashboard-wrapper">

        <main class="class-main-content" style="max-width: 900px; margin: 0 auto; padding: 20px;">

            <a href="class.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Classes
            </a>

            <!-- Subject Header -->
            <div class="subject-header">
                <div class="subject-icon" style="background: <?= $current_subject['color'] ?>;">
                    <i class="fas <?= $current_subject['icon'] ?>"></i>
                </div>
                <h1>
                    <?= $current_subject['name'] ?>
                </h1>
                <p>
                    <?= $current_subject['desc'] ?>
                </p>
            </div>

            <!-- Hakaes: Add Material Button -->
            <?php if ($can_manage): ?>
                <button class="add-material-btn" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Material
                </button>
            <?php endif; ?>

            <!-- Materials List -->
            <?php if (!empty($materials)): ?>
                <div class="materials-grid">
                    <?php foreach ($materials as $material): ?>
                        <div class="material-card">
                            <div class="material-header">
                                <div class="material-type-icon <?= $material['material_type'] ?>">
                                    <?php if ($material['material_type'] === 'text'): ?>
                                        <i class="fas fa-file-alt"></i>
                                    <?php elseif ($material['material_type'] === 'youtube'): ?>
                                        <i class="fab fa-youtube"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file-pdf"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="material-title">
                                    <?= e($material['title']) ?>
                                </span>
                                <?php if ($can_manage): ?>
                                    <button class="modal-close" onclick="deleteMaterial(<?= $material['id_material'] ?>)"
                                        title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="material-meta">
                                <i class="fas fa-user"></i>
                                <?= e($material['creator_name']) ?> ·
                                <i class="fas fa-clock"></i>
                                <?= date('d M Y', strtotime($material['created_at'])) ?>
                            </div>
                            <div class="material-content">
                                <?php if ($material['material_type'] === 'text'): ?>
                                    <div class="text-content-wrapper" data-material-id="<?= $material['id_material'] ?>">
                                        <div class="text-content">
                                            <?= $material['content'] ?>
                                        </div>
                                        <button class="read-more-btn" onclick="toggleContent(this)">
                                            <i class="fas fa-chevron-down"></i> Read More
                                        </button>
                                    </div>
                                <?php elseif ($material['material_type'] === 'youtube'): ?>
                                    <?php
                                    // Extract YouTube video ID
                                    $videoId = $material['content'];
                                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $material['content'], $matches)) {
                                        $videoId = $matches[1];
                                    }
                                    ?>
                                    <div class="youtube-container">
                                        <iframe src="https://www.youtube.com/embed/<?= e($videoId) ?>"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen></iframe>
                                    </div>
                                <?php elseif ($material['material_type'] === 'pdf'): ?>
                                    <div class="pdf-actions">
                                        <a href="../<?= e($material['file_path']) ?>" target="_blank" class="pdf-btn view">
                                            <i class="fas fa-eye"></i> View PDF
                                        </a>
                                        <a href="../<?= e($material['file_path']) ?>" download class="pdf-btn download">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <span class="pdf-filename"><?= e($material['content']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-materials">
                    <i class="fas fa-book-open"></i>
                    <h3>No Materials Yet</h3>
                    <p>Materials will appear here once the teacher adds them.</p>
                </div>
            <?php endif; ?>

            <!-- QUIZ SECTION -->
            <h2 style="color: #d4af37; margin: 32px 0 16px; font-size: 1.3rem;">
                <i class="fas fa-question-circle"></i> Quizzes & Exams
            </h2>

            <?php if ($can_manage): ?>
                <button class="add-material-btn" onclick="openQuizModal()"
                    style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-plus"></i> Create Quiz
                </button>
            <?php endif; ?>

            <?php if (!empty($quizzes)): ?>
                <div class="materials-grid">
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="material-card">
                            <div class="material-header">
                                <div class="material-type-icon" style="background: rgba(155, 89, 182, 0.2); color: #9b59b6;">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <span class="material-title"><?= e($quiz['title']) ?></span>
                                <?php if ($can_manage): ?>
                                    <span class="status-badge <?= $quiz['status'] ?>"
                                        style="padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; background: <?= $quiz['status'] === 'active' ? 'rgba(76,175,80,0.2)' : ($quiz['status'] === 'draft' ? 'rgba(255,193,7,0.2)' : 'rgba(231,76,60,0.2)') ?>; color: <?= $quiz['status'] === 'active' ? '#4caf50' : ($quiz['status'] === 'draft' ? '#ffc107' : '#e74c3c') ?>;">
                                        <?= ucfirst($quiz['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="material-meta">
                                <i class="fas fa-question"></i> <?= $quiz['question_count'] ?> questions ·
                                <i class="fas fa-clock"></i> <?= $quiz['time_limit'] ?> min ·
                                <i class="fas fa-trophy"></i> Pass: <?= $quiz['passing_score'] ?>%
                            </div>
                            <p style="color: rgba(255,255,255,0.7); margin: 12px 0; font-size: 0.9rem;">
                                <?= e($quiz['description'] ?: 'No description') ?>
                            </p>

                            <?php if ($can_manage): ?>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <a href="quiz_manage.php?id=<?= $quiz['id_quiz'] ?>" class="pdf-btn view"
                                        style="background: rgba(155, 89, 182, 0.2); color: #9b59b6;">
                                        <i class="fas fa-cog"></i> Manage
                                    </a>
                                    <button
                                        onclick="updateQuizStatus(<?= $quiz['id_quiz'] ?>, '<?= $quiz['status'] === 'active' ? 'closed' : 'active' ?>')"
                                        class="pdf-btn download" style="border: none; cursor: pointer;">
                                        <i class="fas fa-<?= $quiz['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                        <?= $quiz['status'] === 'active' ? 'Close' : 'Activate' ?>
                                    </button>
                                </div>
                            <?php else: ?>
                                <?php
                                $can_take = ($quiz['attempts_used'] ?? 0) < $quiz['max_attempts'] && $quiz['question_count'] > 0;
                                ?>
                                <?php if ($can_take): ?>
                                    <a href="quiz_attempt.php?id=<?= $quiz['id_quiz'] ?>" class="pdf-btn view"
                                        style="background: rgba(76, 175, 80, 0.2); color: #4caf50;">
                                        <i class="fas fa-play"></i> Take Quiz
                                    </a>
                                    <span style="font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-left: 12px;">
                                        Attempts: <?= $quiz['attempts_used'] ?? 0 ?>/<?= $quiz['max_attempts'] ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">
                                        <i class="fas fa-check-circle"></i> Completed
                                        (<?= $quiz['attempts_used'] ?? 0 ?>/<?= $quiz['max_attempts'] ?> attempts used)
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-materials">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Quizzes Yet</h3>
                    <p>Quizzes will appear here once the teacher creates them.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Add Material Modal -->
    <?php if ($can_manage): ?>
        <div class="modal-overlay" id="addModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-plus"></i> Add Material</h2>
                    <button class="modal-close" onclick="closeAddModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="addMaterialForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="subject" value="<?= $subject ?>">

                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-input" required placeholder="Material title...">
                        </div>

                        <div class="form-group">
                            <label>Type</label>
                            <select name="material_type" class="form-select" id="typeSelect"
                                onchange="toggleContentField()">
                                <option value="text">Text / Article</option>
                                <option value="youtube">YouTube Video</option>
                                <option value="pdf">PDF Document</option>
                            </select>
                        </div>

                        <div class="form-group" id="textContentGroup">
                            <label>Content</label>
                            <textarea name="text_content" class="form-textarea"
                                placeholder="Write your material content here..."></textarea>
                        </div>

                        <div class="form-group" id="youtubeContentGroup" style="display: none;">
                            <label>YouTube URL or Video ID</label>
                            <input type="text" name="youtube_content" class="form-input"
                                placeholder="https://youtube.com/watch?v=... or video ID">
                        </div>

                        <div class="form-group" id="pdfContentGroup" style="display: none;">
                            <label>Upload PDF (Max 5MB)</label>
                            <input type="file" name="pdf_file" id="pdfFileInput" class="form-input" accept=".pdf">
                            <small style="color: rgba(255,255,255,0.5);">Only PDF files are allowed</small>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Save Material
                        </button>
                    </form>
                    <div id="formResult" style="margin-top: 16px; text-align: center;"></div>
                </div>
            </div>
        </div>

        <script>
            const csrfToken = '<?= $csrf_token ?>';
            const currentSubject = '<?= $subject ?>';

            // Toggle Read More/Show Less for text content
            function toggleContent(btn) {
                const wrapper = btn.closest('.text-content-wrapper');
                const content = wrapper.querySelector('.text-content');
                const isExpanded = wrapper.classList.toggle('expanded');
                content.classList.toggle('expanded');

                btn.innerHTML = isExpanded
                    ? '<i class="fas fa-chevron-up"></i> Show Less'
                    : '<i class="fas fa-chevron-down"></i> Read More';
            }

            // Detect overflow and show Read More button only if needed
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.text-content-wrapper').forEach(wrapper => {
                    const content = wrapper.querySelector('.text-content');
                    if (content.scrollHeight > content.clientHeight + 10) {
                        wrapper.classList.add('has-overflow');
                    }
                });
            });

            function openAddModal() {
                document.getElementById('addModal').classList.add('active');
            }

            function closeAddModal() {
                document.getElementById('addModal').classList.remove('active');
            }

            function toggleContentField() {
                const type = document.getElementById('typeSelect').value;
                document.getElementById('textContentGroup').style.display = type === 'text' ? 'block' : 'none';
                document.getElementById('youtubeContentGroup').style.display = type === 'youtube' ? 'block' : 'none';
                document.getElementById('pdfContentGroup').style.display = type === 'pdf' ? 'block' : 'none';
            }

            document.getElementById('addMaterialForm').addEventListener('submit', async function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const type = formData.get('material_type');
                const resultDiv = document.getElementById('formResult');

                // Handle PDF upload separately (multipart form)
                if (type === 'pdf') {
                    const pdfFile = document.getElementById('pdfFileInput').files[0];
                    if (!pdfFile) {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ Please select a PDF file</span>';
                        return;
                    }

                    const pdfFormData = new FormData();
                    pdfFormData.append('csrf_token', csrfToken);
                    pdfFormData.append('subject', currentSubject);
                    pdfFormData.append('title', formData.get('title'));
                    pdfFormData.append('pdf_file', pdfFile);

                    try {
                        const response = await fetch('api/router.php?action=uploadPdf', {
                            method: 'POST',
                            body: pdfFormData
                        });

                        const result = await response.json();
                        if (result.success) {
                            resultDiv.innerHTML = '<span style="color: #4caf50;">✓ PDF uploaded successfully!</span>';
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ ' + (result.error || 'Failed to upload') + '</span>';
                        }
                    } catch (err) {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ Network error</span>';
                    }
                    return;
                }

                // Text/YouTube handling
                const content = type === 'text' ? formData.get('text_content') : formData.get('youtube_content');

                const data = {
                    csrf_token: csrfToken,
                    subject: currentSubject,
                    title: formData.get('title'),
                    material_type: type,
                    content: content
                };

                try {
                    const response = await fetch('api/router.php?action=addMaterial', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        resultDiv.innerHTML = '<span style="color: #4caf50;">✓ Material added successfully!</span>';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ ' + (result.error || 'Failed to add') + '</span>';
                    }
                } catch (err) {
                    resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ Network error</span>';
                }
            });

            async function deleteMaterial(id) {
                if (!confirm('Delete this material?')) return;

                try {
                    const response = await fetch('api/router.php?action=deleteMaterial', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ csrf_token: csrfToken, id_material: id })
                    });

                    const result = await response.json();
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.error || 'Failed to delete');
                    }
                } catch (err) {
                    alert('Network error');
                }
            }

            // Close modal on outside click
            document.getElementById('addModal').addEventListener('click', function (e) {
                if (e.target === this) closeAddModal();
            });

            // Quiz functions
            function openQuizModal() {
                document.getElementById('quizModal').classList.add('active');
            }

            function closeQuizModal() {
                document.getElementById('quizModal').classList.remove('active');
            }

            document.getElementById('createQuizForm').addEventListener('submit', async function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const resultDiv = document.getElementById('quizFormResult');

                const data = {
                    csrf_token: csrfToken,
                    subject: currentSubject,
                    title: formData.get('quiz_title'),
                    description: formData.get('quiz_description'),
                    time_limit: parseInt(formData.get('time_limit')),
                    passing_score: parseInt(formData.get('passing_score'))
                };

                try {
                    const response = await fetch('api/router.php?action=createQuiz', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    if (result.success) {
                        resultDiv.innerHTML = '<span style="color: #4caf50;">✓ Quiz created! Redirecting to add questions...</span>';
                        setTimeout(() => {
                            window.location.href = 'quiz_manage.php?id=' + result.quiz_id;
                        }, 1000);
                    } else {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ ' + (result.error || 'Failed to create') + '</span>';
                    }
                } catch (err) {
                    resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ Network error</span>';
                }
            });

            async function updateQuizStatus(quizId, newStatus) {
                if (!confirm(`Change quiz status to "${newStatus}"?`)) return;

                try {
                    const response = await fetch('api/router.php?action=updateQuizStatus', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            csrf_token: csrfToken,
                            quiz_id: quizId,
                            status: newStatus
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.error || 'Failed to update');
                    }
                } catch (err) {
                    alert('Network error');
                }
            }

            // Close quiz modal on outside click
            document.getElementById('quizModal').addEventListener('click', function (e) {
                if (e.target === this) closeQuizModal();
            });
        </script>
    <?php endif; ?>

    <!-- Quiz Create Modal (outside php block so it loads with page) -->
    <?php if ($can_manage): ?>
        <div class="modal-overlay" id="quizModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-clipboard-list"></i> Create Quiz</h2>
                    <button class="modal-close" onclick="closeQuizModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="createQuizForm" onsubmit="submitQuizForm(event)">
                        <div class="form-group">
                            <label>Quiz Title</label>
                            <input type="text" name="quiz_title" class="form-input" required
                                placeholder="e.g., Chapter 1 Quiz">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="quiz_description" class="form-textarea" rows="3"
                                placeholder="Brief description of the quiz..."></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label>Time Limit (minutes)</label>
                                <input type="number" name="time_limit" class="form-input" value="30" min="5" max="120">
                            </div>
                            <div class="form-group">
                                <label>Passing Score (%)</label>
                                <input type="number" name="passing_score" class="form-input" value="70" min="0" max="100">
                            </div>
                        </div>
                        <button type="submit" class="btn-submit"
                            style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                            <i class="fas fa-plus"></i> Create & Add Questions
                        </button>
                    </form>
                    <div id="quizFormResult" style="margin-top: 16px; text-align: center;"></div>
                </div>
            </div>
        </div>

        <script>
            // Quiz form submit handler (defined after DOM element exists)
            async function submitQuizForm(e) {
                e.preventDefault();

                const form = document.getElementById('createQuizForm');
                const formData = new FormData(form);
                const resultDiv = document.getElementById('quizFormResult');

                const data = {
                    csrf_token: '<?= $csrf_token ?>',
                    subject: '<?= $subject ?>',
                    title: formData.get('quiz_title'),
                    description: formData.get('quiz_description'),
                    time_limit: parseInt(formData.get('time_limit')),
                    passing_score: parseInt(formData.get('passing_score'))
                };

                try {
                    const response = await fetch('api/router.php?action=createQuiz', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    if (result.success) {
                        resultDiv.innerHTML = '<span style="color: #4caf50;">✓ Quiz created! Redirecting...</span>';
                        setTimeout(() => {
                            window.location.href = 'quiz_manage.php?id=' + result.quiz_id;
                        }, 1000);
                    } else {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ ' + (result.error || 'Failed to create') + '</span>';
                    }
                } catch (err) {
                    resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ Network error</span>';
                }
            }

            // Close quiz modal on outside click
            document.getElementById('quizModal').addEventListener('click', function (e) {
                if (e.target === this) closeQuizModal();
            });
        </script>
    <?php endif; ?>

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