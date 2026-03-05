<!-- quiz_manage.php â€” CI4 View -->
<?php // Quiz Management View ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quiz -
        <?= esc($quiz['title']) ?>
    </title>
    <link rel="stylesheet" href="<?= asset_v('css/shared/global.css') ?>">
    <link rel="stylesheet" href="<?= base_url('user/css/user/beranda_style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= csrf_meta() ?>
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
                <?= esc($subj['color']) ?>
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
    <div class="container" data-csrf-token="<?= csrf_hash() ?>" data-quiz-id="<?= $quizId ?>"
        data-api-base="<?= base_url('api/') ?>">
        <a href="<?= base_url('subject?subject=' . esc($quiz['subject'])) ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to
            <?= esc($subj['name']) ?>
        </a>

        <!-- Quiz Header -->
        <?= view('App\Modules\Academic\Views\partials\quiz\_header_manage') ?>

        <!-- Add Question Form -->
        <?= view('App\Modules\Academic\Views\partials\quiz\_form_question') ?>

        <!-- Questions List -->
        <?= view('App\Modules\Academic\Views\partials\quiz\_question_list') ?>
    </div>

    <script src="<?= asset_v('js/academic/quiz_manage.js') ?>"></script>
</body>

</html>