<?php
/**
 * Quiz Management Page
 * Mediterranean of Egypt - School Management System
 * 
 * For Hakaes to add/edit/delete quiz questions
 */

require_once '../core/bootstrap.php';

// Only Hakaes/Vasiki can access
if (!Auth::canManageGrades()) {
    redirect('../index.php?pesan=gagal_akses');
}

$user_id = Auth::id();
$quiz_id = intval($_GET['id'] ?? 0);

if (!$quiz_id) {
    redirect('class.php');
}

// Get quiz details
$quiz = DB::queryOne("SELECT * FROM class_quizzes WHERE id_quiz = ?", [$quiz_id]);
if (!$quiz) {
    redirect('class.php');
}

// Get questions
$questions = DB::query(
    "SELECT * FROM quiz_questions WHERE id_quiz = ? ORDER BY order_num",
    [$quiz_id]
);

// Subject metadata
$subjects = [
    'history' => ['icon' => 'fa-landmark', 'color' => '#4a90d9', 'name' => 'History'],
    'herbology' => ['icon' => 'fa-leaf', 'color' => '#27ae60', 'name' => 'Herbology'],
    'oceanology' => ['icon' => 'fa-water', 'color' => '#00bcd4', 'name' => 'Oceanology'],
    'astronomy' => ['icon' => 'fa-star', 'color' => '#9b59b6', 'name' => 'Astronomy'],
];
$subj = $subjects[$quiz['subject']] ?? ['icon' => 'fa-book', 'color' => '#d4af37', 'name' => 'Quiz'];

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quiz -
        <?= e($quiz['title']) ?>
    </title>
    <link rel="stylesheet" href="../assets/css/global.css" />
    <link rel="stylesheet" href="css/beranda_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            min-height: 100vh;
            color: #fff;
            font-family: 'Inter', sans-serif;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
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
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .quiz-header {
            background: linear-gradient(145deg, rgba(30, 30, 35, 0.95), rgba(20, 20, 25, 0.98));
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .quiz-header h1 {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .quiz-meta {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 12px;
        }

        .status-badge.draft {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-badge.active {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .status-badge.closed {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        /* Add Question Form */
        .add-question-card {
            background: linear-gradient(145deg, rgba(155, 89, 182, 0.1), rgba(142, 68, 173, 0.15));
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(155, 89, 182, 0.3);
        }

        .add-question-card h2 {
            color: #9b59b6;
            margin-bottom: 16px;
            font-size: 1.2rem;
        }

        .form-row {
            display: grid;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-row.cols-2 {
            grid-template-columns: 1fr 1fr;
        }

        .form-row.cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }

        @media (max-width: 600px) {

            .form-row.cols-2,
            .form-row.cols-4 {
                grid-template-columns: 1fr;
            }
        }

        .form-group label {
            display: block;
            color: #ccc;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }

        .correct-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ccc;
        }

        .correct-label input[type="radio"] {
            width: 18px;
            height: 18px;
        }

        .btn-add {
            padding: 12px 24px;
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            border: none;
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-add:hover {
            opacity: 0.9;
        }

        /* Questions List */
        .questions-list h2 {
            color: #fff;
            margin-bottom: 16px;
            font-size: 1.2rem;
        }

        .question-card {
            background: linear-gradient(145deg, rgba(40, 40, 45, 0.9), rgba(30, 30, 35, 0.95));
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .question-number {
            display: inline-block;
            background:
                <?= $subj['color'] ?>
            ;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .question-text {
            font-size: 1rem;
            margin-bottom: 12px;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }

        @media (max-width: 480px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
        }

        .option-item {
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .option-item.correct {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .question-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .question-points {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }

        .btn-delete {
            padding: 6px 12px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .btn-delete:hover {
            background: rgba(231, 76, 60, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.5);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 12px;
            opacity: 0.3;
        }

        #formResult {
            margin-top: 16px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="subject_detail.php?subject=<?= $quiz['subject'] ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to
            <?= $subj['name'] ?>
        </a>

        <!-- Quiz Header -->
        <div class="quiz-header">
            <h1>
                <i class="fas fa-clipboard-list"></i>
                <?= e($quiz['title']) ?>
                <span class="status-badge <?= $quiz['status'] ?>">
                    <?= ucfirst($quiz['status']) ?>
                </span>
            </h1>
            <div class="quiz-meta">
                <i class="fas fa-question"></i>
                <?= count($questions) ?> questions ·
                <i class="fas fa-clock"></i>
                <?= $quiz['time_limit'] ?> min ·
                <i class="fas fa-trophy"></i> Pass:
                <?= $quiz['passing_score'] ?>%
            </div>
        </div>

        <!-- Add Question Form -->
        <div class="add-question-card">
            <h2><i class="fas fa-plus-circle"></i> Add New Question</h2>
            <form id="addQuestionForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Question</label>
                        <textarea name="question" class="form-textarea" required
                            placeholder="Enter your question..."></textarea>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Option A</label>
                        <input type="text" name="option_a" class="form-input" required placeholder="Option A">
                    </div>
                    <div class="form-group">
                        <label>Option B</label>
                        <input type="text" name="option_b" class="form-input" required placeholder="Option B">
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Option C</label>
                        <input type="text" name="option_c" class="form-input" required placeholder="Option C">
                    </div>
                    <div class="form-group">
                        <label>Option D</label>
                        <input type="text" name="option_d" class="form-input" required placeholder="Option D">
                    </div>
                </div>

                <div class="form-row cols-4">
                    <label class="correct-label">
                        <input type="radio" name="correct_answer" value="a" required> A is correct
                    </label>
                    <label class="correct-label">
                        <input type="radio" name="correct_answer" value="b"> B is correct
                    </label>
                    <label class="correct-label">
                        <input type="radio" name="correct_answer" value="c"> C is correct
                    </label>
                    <label class="correct-label">
                        <input type="radio" name="correct_answer" value="d"> D is correct
                    </label>
                </div>

                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group" style="max-width: 150px;">
                        <label>Points</label>
                        <input type="number" name="points" class="form-input" value="10" min="1" max="100">
                    </div>
                </div>

                <button type="submit" class="btn-add">
                    <i class="fas fa-plus"></i> Add Question
                </button>
                <div id="formResult"></div>
            </form>
        </div>

        <!-- Questions List -->
        <div class="questions-list">
            <h2><i class="fas fa-list"></i> Questions (
                <?= count($questions) ?>)
            </h2>

            <?php if (!empty($questions)): ?>
                <?php $qNum = 0;
                foreach ($questions as $q):
                    $qNum++; ?>
                    <div class="question-card" id="question-<?= $q['id_question'] ?>">
                        <span class="question-number">Q
                            <?= $qNum ?>
                        </span>
                        <div class="question-text">
                            <?= e($q['question_text']) ?>
                        </div>
                        <div class="options-grid">
                            <div class="option-item <?= $q['correct_answer'] === 'a' ? 'correct' : '' ?>">
                                <strong>A.</strong>
                                <?= e($q['option_a']) ?>
                                <?php if ($q['correct_answer'] === 'a'): ?><i class="fas fa-check"></i>
                                <?php endif; ?>
                            </div>
                            <div class="option-item <?= $q['correct_answer'] === 'b' ? 'correct' : '' ?>">
                                <strong>B.</strong>
                                <?= e($q['option_b']) ?>
                                <?php if ($q['correct_answer'] === 'b'): ?><i class="fas fa-check"></i>
                                <?php endif; ?>
                            </div>
                            <div class="option-item <?= $q['correct_answer'] === 'c' ? 'correct' : '' ?>">
                                <strong>C.</strong>
                                <?= e($q['option_c']) ?>
                                <?php if ($q['correct_answer'] === 'c'): ?><i class="fas fa-check"></i>
                                <?php endif; ?>
                            </div>
                            <div class="option-item <?= $q['correct_answer'] === 'd' ? 'correct' : '' ?>">
                                <strong>D.</strong>
                                <?= e($q['option_d']) ?>
                                <?php if ($q['correct_answer'] === 'd'): ?><i class="fas fa-check"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="question-footer">
                            <span class="question-points"><i class="fas fa-star"></i>
                                <?= $q['points'] ?> points
                            </span>
                            <button class="btn-delete" onclick="deleteQuestion(<?= $q['id_question'] ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-question-circle"></i>
                    <h3>No Questions Yet</h3>
                    <p>Add your first question using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const csrfToken = '<?= $csrf_token ?>';
        const quizId = <?= $quiz_id ?>;

        document.getElementById('addQuestionForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const resultDiv = document.getElementById('formResult');

            const data = {
                csrf_token: csrfToken,
                quiz_id: quizId,
                question: formData.get('question'),
                option_a: formData.get('option_a'),
                option_b: formData.get('option_b'),
                option_c: formData.get('option_c'),
                option_d: formData.get('option_d'),
                correct_answer: formData.get('correct_answer'),
                points: parseInt(formData.get('points'))
            };

            try {
                const response = await fetch('api/router.php?action=addQuestion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    resultDiv.innerHTML = '<span style="color: #4caf50;">✓ Question added!</span>';
                    setTimeout(() => location.reload(), 800);
                } else {
                    resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ ' + (result.error || 'Failed') + '</span>';
                }
            } catch (err) {
                resultDiv.innerHTML = '<span style="color: #e74c3c;">✗ Network error</span>';
            }
        });

        async function deleteQuestion(questionId) {
            if (!confirm('Delete this question?')) return;

            try {
                const response = await fetch('api/router.php?action=deleteQuestion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: csrfToken,
                        question_id: questionId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    document.getElementById('question-' + questionId).remove();
                } else {
                    alert(result.error || 'Failed to delete');
                }
            } catch (err) {
                alert('Network error');
            }
        }
    </script>
</body>

</html>