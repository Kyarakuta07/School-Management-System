<?php

namespace App\Modules\Academic\Models;

use CodeIgniter\Model;

class QuizModel extends Model
{
    protected $table = 'class_quizzes';
    protected $primaryKey = 'id_quiz';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'title',
        'description',
        'subject',
        'time_limit',
        'passing_score',
        'max_attempts',
        'status',
        'created_by',
        'created_at'
    ];

    protected $useTimestamps = false;

    /**
     * Get quiz with questions
     */
    public function getQuizWithQuestions(int $quizId, bool $onlyActive = true)
    {
        $builder = $this->where('id_quiz', $quizId);
        if ($onlyActive) {
            $builder->where('status', 'active');
        }
        $quiz = $builder->first();

        if (!$quiz)
            return null;

        $quiz['questions'] = $this->db->table('quiz_questions')
            ->where('id_quiz', $quizId)
            ->get()
            ->getResultArray();

        return $quiz;
    }

    /**
     * Record a quiz attempt
     */
    public function recordAttempt(array $data)
    {
        $insertData = [
            'id_quiz' => $data['quiz_id'] ?? ($data['id_quiz'] ?? 0),
            'id_nethera' => $data['user_id'] ?? ($data['id_nethera'] ?? 0),
            'score' => $data['score'] ?? 0,
            'max_score' => $data['max_score'] ?? 0,
            'percentage' => $data['percentage'] ?? 0,
            'passed' => $data['passed'] ?? 0,
            'answers' => $data['answers'] ?? null,
            'started_at' => $data['started_at'] ?? date('Y-m-d H:i:s'),
            'completed_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->table('quiz_attempts')->insert($insertData);
    }

    /**
     * Get user attempts with quiz title and subject (single JOIN query).
     */
    public function getUserAttempts(int $userId, ?int $quizId = null)
    {
        $builder = $this->db->table('quiz_attempts AS qa')
            ->select('qa.*, q.title, q.subject')
            ->join('class_quizzes AS q', 'q.id_quiz = qa.id_quiz', 'left')
            ->where('qa.id_nethera', $userId);

        if ($quizId) {
            $builder->where('qa.id_quiz', $quizId);
        }

        return $builder->orderBy('qa.id_attempt', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get per-subject quiz progress for a student.
     * Fixes N+1: uses 2 aggregate queries instead of 2*N.
     *
     * @return array<string, array{total: int, completed: int, percentage: int}>
     */
    public function getStudentProgress(int $userId, array $subjectKeys): array
    {
        // 1. Total active quizzes per subject (1 query)
        $totals = $this->db->table('class_quizzes')
            ->select('subject, COUNT(*) AS cnt')
            ->where('status', 'active')
            ->whereIn('subject', $subjectKeys)
            ->groupBy('subject')
            ->get()
            ->getResultArray();

        $totalMap = [];
        foreach ($totals as $row) {
            $totalMap[$row['subject']] = (int) $row['cnt'];
        }

        // 2. Completed quizzes per subject for this user (1 query)
        $completed = $this->db->table('quiz_attempts qa')
            ->select('q.subject, COUNT(DISTINCT qa.id_quiz) AS cnt')
            ->join('class_quizzes q', 'qa.id_quiz = q.id_quiz')
            ->where('qa.id_nethera', $userId)
            ->whereIn('q.subject', $subjectKeys)
            ->groupBy('q.subject')
            ->get()
            ->getResultArray();

        $completedMap = [];
        foreach ($completed as $row) {
            $completedMap[$row['subject']] = (int) $row['cnt'];
        }

        // 3. Merge
        $progress = [];
        foreach ($subjectKeys as $subj) {
            $t = $totalMap[$subj] ?? 0;
            $c = $completedMap[$subj] ?? 0;
            $progress[$subj] = [
                'total' => $t,
                'completed' => $c,
                'percentage' => $t > 0 ? (int) round(($c / $t) * 100) : 0,
            ];
        }

        return $progress;
    }

    /**
     * Get questions for a specific quiz.
     * @param bool $includeAnswers Whether to include correct_answer column.
     */
    public function getQuestions(int $quizId, bool $includeAnswers = false): array
    {
        $select = 'id_question, question_text, option_a, option_b, option_c, option_d, points, order_num';
        if ($includeAnswers) {
            $select .= ', correct_answer';
        }
        return $this->db->table('quiz_questions')
            ->select($select)
            ->where('id_quiz', $quizId)
            ->orderBy('order_num', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Count how many attempts a user has made on a quiz.
     */
    public function countAttempts(int $quizId, int $userId): int
    {
        return $this->db->table('quiz_attempts')
            ->where('id_quiz', $quizId)
            ->where('id_nethera', $userId)
            ->countAllResults();
    }
}
