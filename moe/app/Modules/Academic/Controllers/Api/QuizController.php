<?php

namespace App\Modules\Academic\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Quiz API Controller
 * 
 * Ported from legacy QuizController.php
 * 
 * Endpoints:
 *   GET  /api/quiz                → index()         (list quizzes)
 *   POST /api/quiz/create         → create()        (create quiz, Hakaes only)
 *   POST /api/quiz/add-question   → addQuestion()   (add question, Hakaes only)
 *   GET  /api/quiz/details        → details()       (get quiz with questions)
 *   POST /api/quiz/submit         → submit()        (submit attempt, Nethera only)
 *   POST /api/quiz/update-status  → updateStatus()  (toggle active, Hakaes only)
 *   POST /api/quiz/delete-question → deleteQuestion() (delete question, Hakaes only)
 *   GET  /api/quiz/history        → history()       (student quiz history)
 */
class QuizController extends BaseApiController
{
    use IdempotencyTrait;

    protected $quizModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->quizModel = new \App\Modules\Academic\Models\QuizModel();
    }



    public function index(): ResponseInterface
    {
        $subject = $this->request->getGet('subject') ?? '';

        if (!\App\Modules\Academic\Config\SubjectConfig::isValid($subject)) {
            return $this->error('Invalid subject', 400, 'VALIDATION_ERROR');
        }

        $quizzes = $this->quizModel->db->table('class_quizzes AS q')
            ->select('q.*, COUNT(qq.id_question) AS question_count, n.nama_lengkap AS creator_name')
            ->join('quiz_questions AS qq', 'qq.id_quiz = q.id_quiz', 'left')
            ->join('nethera AS n', 'n.id_nethera = q.created_by', 'left')
            ->where('q.subject', $subject)
            ->groupBy('q.id_quiz')
            ->orderBy('q.created_at', 'DESC')
            ->limit(50)
            ->get()->getResultArray();

        return $this->success(['quizzes' => $quizzes]);
    }

    public function create(): ResponseInterface
    {
        $input = $this->getInput();
        $subject = $input['subject'] ?? '';
        $title = trim($input['title'] ?? '');

        if (!\App\Modules\Academic\Config\SubjectConfig::isValid($subject))
            return $this->error('Invalid subject', 400);
        if (empty($title))
            return $this->error('Title is required', 400);

        $quizId = $this->quizModel->insert([
            'subject' => $subject,
            'title' => $title,
            'created_by' => $this->userId,
            'status' => 'inactive',
        ]);

        return $this->success(['quiz_id' => $quizId], 'Quiz created');
    }

    public function addQuestion(): ResponseInterface
    {
        $input = $this->getInput();
        $quizId = (int) ($input['quiz_id'] ?? 0);
        $question = trim($input['question'] ?? '');
        $optionA = trim($input['option_a'] ?? '');
        $optionB = trim($input['option_b'] ?? '');
        $optionC = trim($input['option_c'] ?? '');
        $optionD = trim($input['option_d'] ?? '');
        $correct = strtoupper($input['correct_answer'] ?? '');

        if (!$quizId)
            return $this->error('Quiz ID required', 400);
        if (empty($question))
            return $this->error('Question text required', 400);
        if (!in_array($correct, ['A', 'B', 'C', 'D']))
            return $this->error('Valid correct answer (A-D) required', 400);

        // A2 fix: Verify quiz ownership before adding question
        $quiz = $this->quizModel->find($quizId);
        if (!$quiz || (int) $quiz['created_by'] !== $this->userId) {
            return $this->error('Quiz not found or not owned by you', 403, 'FORBIDDEN');
        }

        $this->quizModel->db->table('quiz_questions')->insert([
            'id_quiz' => $quizId,
            'question_text' => $question,
            'option_a' => $optionA,
            'option_b' => $optionB,
            'option_c' => $optionC,
            'option_d' => $optionD,
            'correct_answer' => $correct,
        ]);

        return $this->success([], 'Question added');
    }

    public function details(): ResponseInterface
    {
        $quizId = (int) ($this->request->getGet('quiz_id') ?? 0);
        if (!$quizId)
            return $this->error('Quiz ID required', 400);

        // Security check: Only active quizzes for non-admins
        $onlyActive = (session()->get('role') === ROLE_NETHERA);
        $quiz = $this->quizModel->getQuizWithQuestions($quizId, $onlyActive);

        if (!$quiz)
            return $this->error('Quiz not found', 404);

        return $this->success(['quiz' => $quiz, 'questions' => $quiz['questions']]);
    }

    public function submit(): ResponseInterface
    {
        if (!$this->acquireIdempotencyLock('quiz_submission', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $quizId = (int) ($input['quiz_id'] ?? 0);
        $answers = $input['answers'] ?? [];

        if (!$quizId)
            return $this->error('Quiz ID required', 400);
        if (empty($answers))
            return $this->error('Answers required', 400);

        // Security check: Verify quiz is active before submission
        $quiz = $this->quizModel->getQuizWithQuestions($quizId, true);
        if (!$quiz) {
            return $this->error('Quiz not found', 404);
        }

        $correct = 0;
        $total = count($quiz['questions']);

        foreach ($quiz['questions'] as $q) {
            $userAnswer = strtoupper($answers[$q['id_question']] ?? '');
            if ($userAnswer === $q['correct_answer'])
                $correct++;
        }

        $score = $total > 0 ? round(($correct / $total) * 100) : 0;

        // Wrap all writes in a single transaction (no nested TX)
        $this->db->transBegin();

        try {
            // Record attempt
            $this->quizModel->recordAttempt([
                'quiz_id' => $quizId,
                'user_id' => $this->userId,
                'score' => $correct,
                'max_score' => $total,
                'percentage' => $score,
                'passed' => $score >= ($quiz['passing_score'] ?? 70) ? 1 : 0,
                'answers' => json_encode($answers),
            ]);

            // Update grade from quiz
            $this->updateGradeFromQuiz($this->userId, $quiz['subject'], $score);

            // Gold Reward for high scores (via raw method — no nested TX)
            $goldEarned = 0;
            if ($score >= 90) {
                $goldService = service('goldService');
                $goldEarned = ($score == 100) ? \App\Config\GameConfig::QUIZ_GOLD_PERFECT : \App\Config\GameConfig::QUIZ_GOLD_PASS;
                $goldService->addGoldRaw($this->userId, $goldEarned, 'battle_reward', "Scored {$score}% on quiz: {$quiz['title']}");
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->error('Failed to submit quiz.', 500, 'SERVER_ERROR');
        }

        return $this->success([
            'score' => $score,
            'correct' => $correct,
            'total' => $total,
            'gold_earned' => $goldEarned,
        ], "Quiz completed! Score: {$score}%" . ($goldEarned > 0 ? " You earned {$goldEarned} Gold!" : ""));
    }

    public function updateStatus(): ResponseInterface
    {
        $input = $this->getInput();
        $quizId = (int) ($input['quiz_id'] ?? 0);
        $status = ($input['status'] ?? 'inactive');

        if (!$quizId)
            return $this->error('Quiz ID required', 400);
        if (!in_array($status, ['draft', 'active', 'closed']))
            return $this->error('Invalid status', 400);

        // A2 fix: Verify quiz ownership before status change
        $quiz = $this->quizModel->find($quizId);
        if (!$quiz || (int) $quiz['created_by'] !== $this->userId) {
            return $this->error('Quiz not found or not owned by you', 403, 'FORBIDDEN');
        }

        $this->quizModel->update($quizId, ['status' => $status]);
        return $this->success([], "Quiz updated to {$status}");
    }

    public function deleteQuestion(): ResponseInterface
    {
        $input = $this->getInput();
        $questionId = (int) ($input['question_id'] ?? 0);
        if (!$questionId)
            return $this->error('Question ID required', 400);

        // A2 fix: Verify question belongs to a quiz owned by current user
        $question = $this->quizModel->db->table('quiz_questions AS qq')
            ->join('class_quizzes AS q', 'q.id_quiz = qq.id_quiz')
            ->where('qq.id_question', $questionId)
            ->where('q.created_by', $this->userId)
            ->get()->getRowArray();

        if (!$question) {
            return $this->error('Question not found or not in your quiz', 403, 'FORBIDDEN');
        }

        $this->quizModel->db->table('quiz_questions')->where('id_question', $questionId)->delete();
        return $this->success([], 'Question deleted');
    }

    public function history(): ResponseInterface
    {
        // Title and subject now included via JOIN in model (N+1 fix)
        $attempts = $this->quizModel->getUserAttempts($this->userId);

        return $this->success(['history' => $attempts]);
    }

    // ==================================================
    // PRIVATE HELPERS
    // ==================================================

    private function updateGradeFromQuiz(int $userId, string $subject, int $score): void
    {
        if (!\App\Modules\Academic\Config\SubjectConfig::isValid($subject))
            return;

        $existing = $this->db->table('class_grades')
            ->where('id_nethera', $userId)
            ->get()->getRowArray();

        if ($existing) {
            $currentGrade = (int) ($existing[$subject] ?? 0);
            $newGrade = max($currentGrade, $score);
            $this->db->table('class_grades')
                ->where('id_nethera', $userId)
                ->update([$subject => $newGrade]);
        }
    }
}
