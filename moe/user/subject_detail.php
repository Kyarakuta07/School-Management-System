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

    <link rel="stylesheet" href="css/subject_detail_style.css" />
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
                                    <?php if ($material['material_type'] === 'text'): ?>
                                        <button class="modal-close"
                                            onclick="openEditModal(<?= $material['id_material'] ?>, '<?= e(addslashes($material['title'])) ?>', '<?= e(addslashes(strip_tags($material['content']))) ?>')"
                                            title="Edit" style="color: #d4af37;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="modal-close" onclick="deleteMaterial(<?= $material['id_material'] ?>)"
                                        title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="material-meta">
                                <i class="fas fa-user"></i>
                                <?= e($material['creator_name']) ?> Â·
                                <i class="fas fa-clock"></i>
                                <?= date('d M Y', strtotime($material['created_at'])) ?>
                            </div>
                            <div class="material-content">
                                <?php if ($material['material_type'] === 'text'): ?>
                                    <div class="text-content-wrapper" data-material-id="<?= $material['id_material'] ?>"
                                        data-title="<?= e($material['title']) ?>">
                                        <div class="text-content" id="content-<?= $material['id_material'] ?>">
                                            <?= $material['content'] ?>
                                        </div>
                                        <button class="read-more-btn" onclick="openContentModal(this)">
                                            <i class="fas fa-expand"></i> Read More
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
                                <i class="fas fa-question"></i> <?= $quiz['question_count'] ?> questions Â·
                                <i class="fas fa-clock"></i> <?= $quiz['time_limit'] ?> min Â·
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

    <!-- Content Read More Modal -->
    <div class="content-modal" id="contentModal">
        <div class="content-modal-inner">
            <div class="content-modal-header">
                <h3 id="contentModalTitle">Material</h3>
                <button class="content-modal-close" onclick="closeContentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="content-modal-body" id="contentModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="content-modal-footer">
                <button onclick="closeContentModal()">
                    <i class="fas fa-chevron-up"></i> Show Less
                </button>
            </div>
        </div>
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

        <!-- Edit Material Modal -->
        <div class="modal-overlay" id="editModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-edit"></i> Edit Material</h2>
                    <button class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editMaterialForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="id_material" id="editMaterialId">

                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" id="editTitle" class="form-input" required
                                placeholder="Material title...">
                        </div>

                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" id="editContent" class="form-textarea"
                                placeholder="Write your material content here..." style="min-height: 200px;"></textarea>
                        </div>

                        <button type="submit" class="btn-submit"
                            style="background: linear-gradient(135deg, #d4af37, #b8860b);">
                            <i class="fas fa-save"></i> Update Material
                        </button>
                    </form>
                    <div id="editFormResult" style="margin-top: 16px; text-align: center;"></div>
                </div>
            </div>
        </div>

        <script>
            const csrfToken = '<?= $csrf_token ?>';
            const currentSubject = '<?= $subject ?>';

            // Open content in modal popup
            function openContentModal(btn) {
                const wrapper = btn.closest('.text-content-wrapper');
                const content = wrapper.querySelector('.text-content');
                const title = wrapper.dataset.title || 'Material';

                document.getElementById('contentModalTitle').textContent = title;
                document.getElementById('contentModalBody').innerHTML = content.innerHTML;
                document.getElementById('contentModal').classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
            }

            // Close content modal
            function closeContentModal() {
                document.getElementById('contentModal').classList.remove('active');
                document.body.style.overflow = ''; // Restore scroll
            }

            // Close modal on outside click
            document.getElementById('contentModal').addEventListener('click', function (e) {
                if (e.target === this) closeContentModal();
            });

            // Close modal on Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeContentModal();
            });

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

            // Edit Material Modal Functions
            function openEditModal(id, title, content) {
                document.getElementById('editMaterialId').value = id;
                document.getElementById('editTitle').value = title;
                document.getElementById('editContent').value = content;
                document.getElementById('editModal').classList.add('active');
            }

            function closeEditModal() {
                document.getElementById('editModal').classList.remove('active');
                document.getElementById('editFormResult').innerHTML = '';
            }

            // Edit Modal Form Submit
            document.getElementById('editMaterialForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const resultDiv = document.getElementById('editFormResult');
                resultDiv.innerHTML = '<span style="color: #d4af37;">Updating...</span>';

                try {
                    const response = await fetch('api/router.php?action=updateMaterial', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            csrf_token: csrfToken,
                            id_material: document.getElementById('editMaterialId').value,
                            title: document.getElementById('editTitle').value,
                            content: document.getElementById('editContent').value
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        resultDiv.innerHTML = '<span style="color: #4caf50;">✓ Material updated!</span>';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ ' + (result.error || 'Update failed') + '</span>';
                    }
                } catch (err) {
                    resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ Network error</span>';
                }
            });

            // Close edit modal on outside click
            document.getElementById('editModal').addEventListener('click', function (e) {
                if (e.target === this) closeEditModal();
            });

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
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— Please select a PDF file</span>';
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
                            resultDiv.innerHTML = '<span style="color: #4caf50;">âœ“ PDF uploaded successfully!</span>';
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— ' + (result.error || 'Failed to upload') + '</span>';
                        }
                    } catch (err) {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— Network error</span>';
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
                        resultDiv.innerHTML = '<span style="color: #4caf50;">âœ“ Material added successfully!</span>';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— ' + (result.error || 'Failed to add') + '</span>';
                    }
                } catch (err) {
                    resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— Network error</span>';
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
                        resultDiv.innerHTML = '<span style="color: #4caf50;">âœ“ Quiz created! Redirecting to add questions...</span>';
                        setTimeout(() => {
                            window.location.href = 'quiz_manage.php?id=' + result.quiz_id;
                        }, 1000);
                    } else {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— ' + (result.error || 'Failed to create') + '</span>';
                    }
                } catch (err) {
                    resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— Network error</span>';
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
                        resultDiv.innerHTML = '<span style="color: #4caf50;">âœ“ Quiz created! Redirecting...</span>';
                        setTimeout(() => {
                            window.location.href = 'quiz_manage.php?id=' + result.quiz_id;
                        }, 1000);
                    } else {
                        resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— ' + (result.error || 'Failed to create') + '</span>';
                    }
                } catch (err) {
                    resultDiv.innerHTML = '<span style="color: #e74c3c;">âœ— Network error</span>';
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