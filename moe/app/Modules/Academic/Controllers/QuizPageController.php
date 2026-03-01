<?php

namespace App\Modules\Academic\Controllers;

use CodeIgniter\Controller;
use App\Modules\Academic\Config\SubjectConfig;

/**
 * QuizPageController
 * Handles quiz management (for Hakaes) and quiz attempt (for students).
 * Ported from legacy moe/user/quiz_manage.php & quiz_attempt.php
 */
class QuizPageController extends Controller
{
    protected $quizModel;

    public function __construct()
    {
        $this->quizModel = new \App\Modules\Academic\Models\QuizModel();
    }

    /**
     * Subject metadata — sourced from centralized SubjectConfig.
     */
    private static function getSubjectMeta(string $key): array
    {
        return SubjectConfig::$subjects[$key] ?? ['icon' => 'fa-book', 'color' => '#d4af37', 'name' => 'Quiz'];
    }

    /**
     * Quiz Management Page — Hakaes/Vasiki add/edit/delete quiz questions.
     */
    public function manage()
    {
        $role = session()->get('role');
        if (cannot('manage_quizzes', $role)) {
            return redirect()->to(base_url('beranda'));
        }

        $quizId = (int) ($this->request->getGet('id') ?? 0);

        if (!$quizId) {
            return redirect()->to(base_url('class'));
        }

        $quiz = $this->quizModel->find($quizId);
        if (!$quiz) {
            return redirect()->to(base_url('class'));
        }

        $questions = $this->quizModel->getQuestions($quizId, true);

        $subj = self::getSubjectMeta($quiz['subject']);

        return view('App\Modules\Academic\Views\quiz_manage', [
            'quiz' => $quiz,
            'questions' => $questions,
            'subj' => $subj,
            'quizId' => $quizId,
        ]);
    }

    /**
     * Quiz Attempt Page — Students take a quiz with a timer.
     */
    public function attempt()
    {
        $userId = (int) session()->get('id_nethera');
        $role = session()->get('role');

        $canManage = can('manage_quizzes', $role);

        $quizId = (int) ($this->request->getGet('id') ?? 0);
        if (!$quizId) {
            return redirect()->to(base_url('class'));
        }

        $quiz = $this->quizModel->find($quizId);
        if (!$quiz) {
            return redirect()->to(base_url('class'));
        }

        // Students can only take active quizzes
        if (!$canManage && $quiz['status'] !== 'active') {
            return redirect()->to(base_url('subject?subject=' . $quiz['subject'] . '&error=quiz_not_active'));
        }

        $questions = $this->quizModel->getQuestions($quizId, false);

        // Check attempt limit for students
        $attemptsUsed = 0;
        if (!$canManage) {
            $attemptsUsed = $this->quizModel->countAttempts($quizId, $userId);

            if ($attemptsUsed >= $quiz['max_attempts']) {
                return redirect()->to(base_url('subject?subject=' . $quiz['subject'] . '&error=max_attempts'));
            }
        }

        $currentSubject = self::getSubjectMeta($quiz['subject']);

        return view('App\Modules\Academic\Views\quiz_attempt', [
            'quiz' => $quiz,
            'questions' => $questions,
            'quizId' => $quizId,
            'currentSubject' => $currentSubject,
            'attemptsUsed' => $attemptsUsed,
        ]);
    }
}
