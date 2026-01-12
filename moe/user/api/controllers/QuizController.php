<?php
/**
 * QuizController - Handles quiz API endpoints
 * Mediterranean of Egypt - School Management System
 * 
 * For Hakaes to manage quizzes and for Nethera to take quizzes
 */

require_once '../../core/bootstrap.php';

class QuizController
{
    /**
     * Get quizzes for a specific subject
     */
    public function getQuizzes()
    {
        $subject = $_GET['subject'] ?? '';
        $validSubjects = ['history', 'herbology', 'oceanology', 'astronomy'];

        if (!in_array($subject, $validSubjects)) {
            return $this->json(['success' => false, 'error' => 'Invalid subject'], 400);
        }

        $canManage = Auth::canManageGrades();
        $userId = Auth::id();

        // For teachers: show all quizzes; for students: show only active
        if ($canManage) {
            $quizzes = DB::query(
                "SELECT q.*, COUNT(qq.id_question) as question_count,
                        n.nama_lengkap as creator_name
                 FROM class_quizzes q
                 LEFT JOIN quiz_questions qq ON q.id_quiz = qq.id_quiz
                 LEFT JOIN nethera n ON q.created_by = n.id_nethera
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
                [$userId, $subject]
            );
        }

        return $this->json([
            'success' => true,
            'quizzes' => $quizzes,
            'can_manage' => $canManage
        ]);
    }

    /**
     * Create a new quiz (Hakaes only)
     */
    public function createQuiz()
    {
        if (!Auth::canManageGrades()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !validate_csrf_token($input['csrf_token'])) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $subject = $input['subject'] ?? '';
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $timeLimit = intval($input['time_limit'] ?? 30);
        $passingScore = intval($input['passing_score'] ?? 70);

        $validSubjects = ['history', 'herbology', 'oceanology', 'astronomy'];

        if (!in_array($subject, $validSubjects) || empty($title)) {
            return $this->json(['success' => false, 'error' => 'Subject and title are required'], 400);
        }

        $result = DB::execute(
            "INSERT INTO class_quizzes (subject, title, description, time_limit, passing_score, created_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$subject, $title, $description, $timeLimit, $passingScore, Auth::id()]
        );

        if ($result) {
            $quizId = DB::getConnection()->insert_id;
            return $this->json([
                'success' => true,
                'message' => 'Quiz created successfully',
                'quiz_id' => $quizId
            ]);
        }

        return $this->json(['success' => false, 'error' => 'Failed to create quiz'], 500);
    }

    /**
     * Add question to a quiz (Hakaes only)
     */
    public function addQuestion()
    {
        if (!Auth::canManageGrades()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !validate_csrf_token($input['csrf_token'])) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $quizId = intval($input['quiz_id'] ?? 0);
        $question = trim($input['question'] ?? '');
        $optionA = trim($input['option_a'] ?? '');
        $optionB = trim($input['option_b'] ?? '');
        $optionC = trim($input['option_c'] ?? '');
        $optionD = trim($input['option_d'] ?? '');
        $correct = strtolower(trim($input['correct_answer'] ?? ''));
        $points = intval($input['points'] ?? 10);

        if (!$quizId || empty($question) || !in_array($correct, ['a', 'b', 'c', 'd'])) {
            return $this->json(['success' => false, 'error' => 'Invalid question data'], 400);
        }

        // Get next order number
        $lastOrder = DB::queryOne(
            "SELECT MAX(order_num) as max_order FROM quiz_questions WHERE id_quiz = ?",
            [$quizId]
        );
        $orderNum = ($lastOrder['max_order'] ?? 0) + 1;

        $result = DB::execute(
            "INSERT INTO quiz_questions (id_quiz, question_text, option_a, option_b, option_c, option_d, correct_answer, points, order_num)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$quizId, $question, $optionA, $optionB, $optionC, $optionD, $correct, $points, $orderNum]
        );

        if ($result) {
            return $this->json(['success' => true, 'message' => 'Question added']);
        }

        return $this->json(['success' => false, 'error' => 'Failed to add question'], 500);
    }

    /**
     * Get quiz details with questions (for taking quiz)
     */
    public function getQuizDetails()
    {
        $quizId = intval($_GET['quiz_id'] ?? 0);

        if (!$quizId) {
            return $this->json(['success' => false, 'error' => 'Quiz ID required'], 400);
        }

        $quiz = DB::queryOne(
            "SELECT * FROM class_quizzes WHERE id_quiz = ?",
            [$quizId]
        );

        if (!$quiz) {
            return $this->json(['success' => false, 'error' => 'Quiz not found'], 404);
        }

        // Check if user can access
        $canManage = Auth::canManageGrades();
        if (!$canManage && $quiz['status'] !== 'active') {
            return $this->json(['success' => false, 'error' => 'Quiz not available'], 403);
        }

        // Get questions (hide correct answers for students)
        $questions = DB::query(
            "SELECT id_question, question_text, option_a, option_b, option_c, option_d, points, order_num" .
            ($canManage ? ", correct_answer" : "") .
            " FROM quiz_questions WHERE id_quiz = ? ORDER BY order_num",
            [$quizId]
        );

        // Check remaining attempts for student
        $attemptsUsed = 0;
        if (!$canManage) {
            $attemptCount = DB::queryOne(
                "SELECT COUNT(*) as count FROM quiz_attempts WHERE id_quiz = ? AND id_nethera = ?",
                [$quizId, Auth::id()]
            );
            $attemptsUsed = $attemptCount['count'] ?? 0;
        }

        return $this->json([
            'success' => true,
            'quiz' => $quiz,
            'questions' => $questions,
            'attempts_used' => $attemptsUsed,
            'can_take' => !$canManage && $attemptsUsed < $quiz['max_attempts']
        ]);
    }

    /**
     * Submit quiz attempt (Nethera only)
     */
    public function submitQuiz()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !validate_csrf_token($input['csrf_token'])) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $quizId = intval($input['quiz_id'] ?? 0);
        $answers = $input['answers'] ?? [];

        if (!$quizId || empty($answers)) {
            return $this->json(['success' => false, 'error' => 'Quiz ID and answers required'], 400);
        }

        $userId = Auth::id();

        // Check quiz exists and is active
        $quiz = DB::queryOne(
            "SELECT * FROM class_quizzes WHERE id_quiz = ? AND status = 'active'",
            [$quizId]
        );

        if (!$quiz) {
            return $this->json(['success' => false, 'error' => 'Quiz not available'], 404);
        }

        // Check attempts
        $attemptCount = DB::queryOne(
            "SELECT COUNT(*) as count FROM quiz_attempts WHERE id_quiz = ? AND id_nethera = ?",
            [$quizId, $userId]
        );

        if (($attemptCount['count'] ?? 0) >= $quiz['max_attempts']) {
            return $this->json(['success' => false, 'error' => 'Maximum attempts reached'], 403);
        }

        // Get correct answers and calculate score
        $questions = DB::query(
            "SELECT id_question, correct_answer, points FROM quiz_questions WHERE id_quiz = ?",
            [$quizId]
        );

        $score = 0;
        $maxScore = 0;

        foreach ($questions as $q) {
            $maxScore += $q['points'];
            $userAnswer = $answers[$q['id_question']] ?? '';
            if (strtolower($userAnswer) === strtolower($q['correct_answer'])) {
                $score += $q['points'];
            }
        }

        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;
        $passed = $percentage >= $quiz['passing_score'];

        // Save attempt
        $result = DB::execute(
            "INSERT INTO quiz_attempts (id_quiz, id_nethera, score, max_score, percentage, passed, answers, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [$quizId, $userId, $score, $maxScore, $percentage, $passed, json_encode($answers)]
        );

        if ($result) {
            // Update class_grades with quiz score (add to subject PP)
            $this->updateGradeFromQuiz($userId, $quiz['subject'], $score);

            return $this->json([
                'success' => true,
                'score' => $score,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passed' => $passed,
                'message' => $passed ? 'Congratulations! You passed!' : 'Better luck next time!'
            ]);
        }

        return $this->json(['success' => false, 'error' => 'Failed to submit quiz'], 500);
    }

    /**
     * Update quiz status (Hakaes only)
     */
    public function updateQuizStatus()
    {
        if (!Auth::canManageGrades()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !validate_csrf_token($input['csrf_token'])) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $quizId = intval($input['quiz_id'] ?? 0);
        $status = $input['status'] ?? '';

        if (!$quizId || !in_array($status, ['draft', 'active', 'closed'])) {
            return $this->json(['success' => false, 'error' => 'Invalid data'], 400);
        }

        $result = DB::execute(
            "UPDATE class_quizzes SET status = ? WHERE id_quiz = ?",
            [$status, $quizId]
        );

        if ($result) {
            return $this->json(['success' => true, 'message' => 'Status updated']);
        }

        return $this->json(['success' => false, 'error' => 'Failed to update'], 500);
    }

    /**
     * Delete question (Hakaes only)
     */
    public function deleteQuestion()
    {
        if (!Auth::canManageGrades()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !validate_csrf_token($input['csrf_token'])) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $questionId = intval($input['question_id'] ?? 0);

        if (!$questionId) {
            return $this->json(['success' => false, 'error' => 'Question ID required'], 400);
        }

        $result = DB::execute(
            "DELETE FROM quiz_questions WHERE id_question = ?",
            [$questionId]
        );

        if ($result) {
            return $this->json(['success' => true, 'message' => 'Question deleted']);
        }

        return $this->json(['success' => false, 'error' => 'Failed to delete'], 500);
    }

    /**
     * Update class_grades with quiz score
     */
    private function updateGradeFromQuiz($userId, $subject, $score)
    {
        // Check if grade record exists
        $existingGrade = DB::queryOne(
            "SELECT * FROM class_grades WHERE id_nethera = ?",
            [$userId]
        );

        if ($existingGrade) {
            // Add quiz score to existing subject grade
            $currentSubjectScore = $existingGrade[$subject] ?? 0;
            $newScore = $currentSubjectScore + $score;
            $newTotal = $existingGrade['total_pp'] + $score;

            DB::execute(
                "UPDATE class_grades SET $subject = ?, total_pp = ? WHERE id_nethera = ?",
                [$newScore, $newTotal, $userId]
            );
        } else {
            // Create new grade record
            DB::execute(
                "INSERT INTO class_grades (id_nethera, class_name, $subject, total_pp)
                 VALUES (?, 'Default Class', ?, ?)",
                [$userId, $score, $score]
            );
        }
    }

    /**
     * Get student's quiz history
     */
    public function getAttemptHistory()
    {
        $quizId = intval($_GET['quiz_id'] ?? 0);
        $userId = Auth::id();

        $attempts = DB::query(
            "SELECT * FROM quiz_attempts WHERE id_quiz = ? AND id_nethera = ? ORDER BY completed_at DESC",
            [$quizId, $userId]
        );

        return $this->json([
            'success' => true,
            'attempts' => $attempts
        ]);
    }

    /**
     * Helper to return JSON response
     */
    private function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
