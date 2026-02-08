<?php
/**
 * Quiz Attempt Page
 * Mediterranean of Egypt - School Management System
 * 
 * Students take quizzes here with timer and submit answers
 */

require_once '../core/bootstrap.php';

// Allow Nethera, Vasiki, Anubis, and Hakaes
$role = Auth::role();
if (!Auth::isLoggedIn() || !in_array($role, ['Nethera', 'Vasiki', 'Anubis', 'Hakaes'])) {
    redirect('../index.php?pesan=gagal_akses');
}

$user_id = Auth::id();
$user_name = Auth::name();
$can_manage = Auth::canManageGrades();

// Get quiz ID
$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) {
    redirect('class.php');
}

// Get quiz details
$quiz = DB::queryOne(
    "SELECT * FROM class_quizzes WHERE id_quiz = ?",
    [$quiz_id]
);

if (!$quiz) {
    redirect('class.php');
}

// Check if quiz is active (for students)
if (!$can_manage && $quiz['status'] !== 'active') {
    redirect('subject_detail.php?subject=' . $quiz['subject'] . '&error=quiz_not_active');
}

// Get questions
$questions = DB::query(
    "SELECT id_question, question_text, option_a, option_b, option_c, option_d, points, order_num
     FROM quiz_questions WHERE id_quiz = ? ORDER BY order_num",
    [$quiz_id]
);

// Check attempts for students
$attempts_used = 0;
if (!$can_manage) {
    $attemptCount = DB::queryOne(
        "SELECT COUNT(*) as count FROM quiz_attempts WHERE id_quiz = ? AND id_nethera = ?",
        [$quiz_id, $user_id]
    );
    $attempts_used = $attemptCount['count'] ?? 0;

    if ($attempts_used >= $quiz['max_attempts']) {
        redirect('subject_detail.php?subject=' . $quiz['subject'] . '&error=max_attempts');
    }
}

$csrf_token = generate_csrf_token();

// Subject metadata for header (unique colors)
$subjects = [
    'pop_culture' => ['icon' => 'fa-film', 'color' => '#e74c3c', 'name' => 'Pop Culture'],
    'mythology' => ['icon' => 'fa-ankh', 'color' => '#9b59b6', 'name' => 'Mythology'],
    'history_of_egypt' => ['icon' => 'fa-landmark', 'color' => '#f39c12', 'name' => 'History of Egypt'],
    'oceanology' => ['icon' => 'fa-water', 'color' => '#00bcd4', 'name' => 'Oceanology'],
    'astronomy' => ['icon' => 'fa-star', 'color' => '#2ecc71', 'name' => 'Astronomy'],
];
$current_subject = $subjects[$quiz['subject']] ?? ['icon' => 'fa-book', 'color' => '#d4af37', 'name' => 'Quiz'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($quiz['title']) ?> -
        <?= APP_NAME ?>
    </title>

    <link rel="stylesheet" href="<?= asset('assets/css/global.css', '../') ?>" />
    <link rel="stylesheet" href="<?= asset('user/css/beranda_style.css', '../') ?>" />
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

        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .quiz-header {
            background: linear-gradient(145deg, rgba(30, 30, 35, 0.95), rgba(20, 20, 25, 0.98));
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .quiz-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background:
                <?= $current_subject['color'] ?>
            ;
        }

        .quiz-info {
            flex: 1;
            min-width: 200px;
        }

        .quiz-info h1 {
            font-size: 1.4rem;
            margin-bottom: 4px;
        }

        .quiz-info p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Timer */
        .timer-box {
            background: rgba(231, 76, 60, 0.2);
            padding: 12px 20px;
            border-radius: 12px;
            text-align: center;
        }

        .timer-box .label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .timer-box .time {
            font-size: 1.8rem;
            font-weight: 700;
            color: #e74c3c;
        }

        .timer-box.warning .time {
            animation: blink 0.5s infinite;
        }

        @keyframes blink {
            50% {
                opacity: 0.5;
            }
        }

        /* Progress */
        .progress-bar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            height: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #8bc34a);
            transition: width 0.3s;
        }

        .progress-text {
            text-align: center;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 10px;
        }

        /* Questions */
        .question-card {
            background: linear-gradient(145deg, rgba(40, 40, 45, 0.9), rgba(30, 30, 35, 0.95));
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .question-number {
            display: inline-block;
            background:
                <?= $current_subject['color'] ?>
            ;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .question-text {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .question-points {
            float: right;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Options */
        .options-list {
            display: grid;
            gap: 10px;
        }

        .option-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .option-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .option-item.selected {
            background: rgba(76, 175, 80, 0.2);
            border-color: #4caf50;
        }

        .option-item input {
            display: none;
        }

        .option-letter {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .option-item.selected .option-letter {
            background: #4caf50;
        }

        .option-text {
            flex: 1;
        }

        /* Submit */
        .submit-section {
            background: linear-gradient(145deg, rgba(30, 30, 35, 0.95), rgba(20, 20, 25, 0.98));
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .submit-btn {
            padding: 16px 48px;
            background: linear-gradient(135deg, #4caf50, #388e3c);
            border: none;
            color: #fff;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .submit-warning {
            margin-top: 12px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Result Modal */
        .result-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .result-modal.active {
            display: flex;
        }

        .result-content {
            background: linear-gradient(145deg, #2a2a30, #1e1e22);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        .result-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .result-icon.passed {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .result-icon.failed {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .result-score {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .result-message {
            font-size: 1.2rem;
            margin-bottom: 24px;
        }

        .result-details {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 24px;
        }

        .result-btn {
            padding: 12px 32px;
            background:
                <?= $current_subject['color'] ?>
            ;
            border: none;
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        @media (max-width: 480px) {
            .quiz-header {
                flex-direction: column;
                text-align: center;
            }

            .timer-box {
                width: 100%;
            }

            .question-card {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

    <div class="quiz-container">

        <!-- Header -->
        <div class="quiz-header">
            <div class="quiz-icon">
                <i class="fas <?= $current_subject['icon'] ?>"></i>
            </div>
            <div class="quiz-info">
                <h1>
                    <?= e($quiz['title']) ?>
                </h1>
                <p>
                    <?= e($quiz['description']) ?>
                </p>
            </div>
            <div class="timer-box" id="timerBox">
                <div class="label">Time Remaining</div>
                <div class="time" id="timer">
                    <?= $quiz['time_limit'] ?>:00
                </div>
            </div>
        </div>

        <!-- Progress -->
        <div class="progress-text">
            <span id="answeredCount">0</span> of
            <?= count($questions) ?> answered
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 0%"></div>
        </div>

        <!-- Questions -->
        <form id="quizForm">
            <?php $qNum = 0;
            foreach ($questions as $q):
                $qNum++; ?>
                <div class="question-card" data-question="<?= $q['id_question'] ?>">
                    <span class="question-number">Question
                        <?= $qNum ?>
                    </span>
                    <span class="question-points">
                        <?= $q['points'] ?> points
                    </span>
                    <div class="question-text">
                        <?= e($q['question_text']) ?>
                    </div>

                    <div class="options-list">
                        <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                            <label class="option-item" onclick="selectOption(this, <?= $q['id_question'] ?>, '<?= $opt ?>')">
                                <input type="radio" name="q_<?= $q['id_question'] ?>" value="<?= $opt ?>">
                                <span class="option-letter">
                                    <?= strtoupper($opt) ?>
                                </span>
                                <span class="option-text">
                                    <?= e($q['option_' . $opt]) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Submit -->
            <div class="submit-section">
                <button type="button" class="submit-btn" id="submitBtn" onclick="submitQuiz()">
                    <i class="fas fa-paper-plane"></i> Submit Quiz
                </button>
                <p class="submit-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Make sure you've answered all questions. You cannot change answers after submitting.
                </p>
            </div>
        </form>

    </div>

    <!-- Result Modal -->
    <div class="result-modal" id="resultModal">
        <div class="result-content">
            <div class="result-icon" id="resultIcon">
                <i class="fas fa-check"></i>
            </div>
            <div class="result-score" id="resultScore">0%</div>
            <div class="result-message" id="resultMessage">Loading...</div>
            <div class="result-details" id="resultDetails"></div>
            <a href="subject_detail.php?subject=<?= $quiz['subject'] ?>" class="result-btn">
                Back to Subject
            </a>
        </div>
    </div>

    <script>
        const csrfToken = '<?= $csrf_token ?>';
        const quizId = <?= $quiz_id ?>;
        const timeLimit = <?= $quiz['time_limit'] ?> * 60; // seconds
        const totalQuestions = <?= count($questions) ?>;

        let timeRemaining = timeLimit;
        let answers = {};
        let submitted = false;

        // Timer
        const timerInterval = setInterval(() => {
            if (submitted) {
                clearInterval(timerInterval);
                return;
            }

            timeRemaining--;
            const mins = Math.floor(timeRemaining / 60);
            const secs = timeRemaining % 60;
            document.getElementById('timer').textContent =
                `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

            // Warning at 2 minutes
            if (timeRemaining <= 120) {
                document.getElementById('timerBox').classList.add('warning');
            }

            // Auto-submit at 0
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                submitQuiz();
            }
        }, 1000);

        // Select option
        function selectOption(el, questionId, answer) {
            const card = el.closest('.question-card');
            card.querySelectorAll('.option-item').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            el.querySelector('input').checked = true;
            answers[questionId] = answer;
            updateProgress();
        }

        // Update progress
        function updateProgress() {
            const count = Object.keys(answers).length;
            document.getElementById('answeredCount').textContent = count;
            const percent = (count / totalQuestions) * 100;
            document.getElementById('progressFill').style.width = percent + '%';
        }

        // Submit quiz
        async function submitQuiz() {
            if (submitted) return;

            const unanswered = totalQuestions - Object.keys(answers).length;
            if (unanswered > 0 && timeRemaining > 0) {
                if (!confirm(`You have ${unanswered} unanswered question(s). Submit anyway?`)) {
                    return;
                }
            }

            submitted = true;
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            try {
                const response = await fetch('api/router.php?action=submitQuiz', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: csrfToken,
                        quiz_id: quizId,
                        answers: answers
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showResult(result);
                } else {
                    alert(result.error || 'Failed to submit quiz');
                    submitted = false;
                    document.getElementById('submitBtn').disabled = false;
                    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-paper-plane"></i> Submit Quiz';
                }
            } catch (err) {
                alert('Network error. Please try again.');
                submitted = false;
                document.getElementById('submitBtn').disabled = false;
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-paper-plane"></i> Submit Quiz';
            }
        }

        // Show result
        function showResult(result) {
            const modal = document.getElementById('resultModal');
            const icon = document.getElementById('resultIcon');

            document.getElementById('resultScore').textContent = result.percentage + '%';
            document.getElementById('resultMessage').textContent = result.message;
            document.getElementById('resultDetails').innerHTML =
                `Score: ${result.score} / ${result.max_score} points<br>
                 ${result.passed ? '🎉 You earned ' + result.score + ' PP!' : ''}`;

            icon.className = 'result-icon ' + (result.passed ? 'passed' : 'failed');
            icon.innerHTML = result.passed ? '<i class="fas fa-trophy"></i>' : '<i class="fas fa-times"></i>';

            modal.classList.add('active');
        }

        // Prevent accidental navigation
        window.onbeforeunload = function () {
            if (!submitted && Object.keys(answers).length > 0) {
                return 'You have unsaved answers. Are you sure you want to leave?';
            }
        };
    </script>

    <!-- BOTTOM NAVIGATION (Mobile Only) -->
    <nav class="bottom-nav"
        style="position: fixed; bottom: 0; left: 0; right: 0; background: linear-gradient(180deg, rgba(20,20,25,0.95), rgba(10,10,12,0.98)); display: flex; justify-content: space-around; padding: 12px 0; border-top: 1px solid rgba(212,175,55,0.3); z-index: 1000;">
        <a href="beranda.php"
            style="display: flex; flex-direction: column; align-items: center; text-decoration: none; color: rgba(255,255,255,0.6); font-size: 0.7rem; gap: 4px;">
            <i class="fa-solid fa-home" style="font-size: 1.1rem;"></i>
            <span>Home</span>
        </a>
        <a href="class.php"
            style="display: flex; flex-direction: column; align-items: center; text-decoration: none; color: #d4af37; font-size: 0.7rem; gap: 4px;">
            <i class="fa-solid fa-book-open" style="font-size: 1.1rem;"></i>
            <span>Class</span>
        </a>
        <a href="pet.php"
            style="display: flex; flex-direction: column; align-items: center; text-decoration: none; color: rgba(255,255,255,0.6); font-size: 0.7rem; gap: 4px;">
            <i class="fa-solid fa-paw" style="font-size: 1.1rem;"></i>
            <span>Pet</span>
        </a>
        <a href="trapeza.php"
            style="display: flex; flex-direction: column; align-items: center; text-decoration: none; color: rgba(255,255,255,0.6); font-size: 0.7rem; gap: 4px;">
            <i class="fa-solid fa-credit-card" style="font-size: 1.1rem;"></i>
            <span>Bank</span>
        </a>
        <a href="punishment.php"
            style="display: flex; flex-direction: column; align-items: center; text-decoration: none; color: rgba(255,255,255,0.6); font-size: 0.7rem; gap: 4px;">
            <i class="fa-solid fa-gavel" style="font-size: 1.1rem;"></i>
            <span>Rules</span>
        </a>
    </nav>
</body>

</html>