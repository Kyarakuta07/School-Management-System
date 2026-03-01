<?php

namespace App\Modules\Academic\Controllers;

use App\Kernel\BaseController;
use App\Modules\Academic\Config\SubjectConfig;

/**
 * SubjectPageController
 * Handles subject detail pages — materials & quizzes for each subject.
 * Ported from legacy moe/user/subject_detail.php
 */
class SubjectPageController extends BaseController
{

    /**
     * Subject Detail Page — displays materials and quizzes for a given subject.
     */
    public function index()
    {
        $userId = session()->get('id_nethera');
        $role = session()->get('role');
        $db = \Config\Database::connect();

        $subject = $this->request->getGet('subject') ?? '';

        if (!SubjectConfig::isValid($subject)) {
            return redirect()->to(base_url('class'));
        }

        $currentSubject = SubjectConfig::getSubject($subject);
        $canManage = can('manage_quizzes', $role);

        // Fetch materials
        $materials = $db->query(
            "SELECT m.*, n.nama_lengkap as creator_name
             FROM class_materials m
             LEFT JOIN nethera n ON m.created_by = n.id_nethera
             WHERE m.subject = ? AND m.is_active = 1
             ORDER BY m.created_at DESC",
            [$subject]
        )->getResultArray();

        // Fetch quizzes
        if ($canManage) {
            $quizzes = $db->query(
                "SELECT q.*, COUNT(qq.id_question) as question_count
                 FROM class_quizzes q
                 LEFT JOIN quiz_questions qq ON q.id_quiz = qq.id_quiz
                 WHERE q.subject = ?
                 GROUP BY q.id_quiz
                 ORDER BY q.created_at DESC",
                [$subject]
            )->getResultArray();
        } else {
            $quizzes = $db->query(
                "SELECT q.*, COUNT(qq.id_question) as question_count,
                        (SELECT COUNT(*) FROM quiz_attempts WHERE id_quiz = q.id_quiz AND id_nethera = ?) as attempts_used
                 FROM class_quizzes q
                 LEFT JOIN quiz_questions qq ON q.id_quiz = qq.id_quiz
                 WHERE q.subject = ? AND q.status = 'active'
                 GROUP BY q.id_quiz
                 ORDER BY q.created_at DESC",
                [$userId, $subject]
            )->getResultArray();
        }

        return view('App\Modules\Academic\Views\subject_detail', [
            'subject' => $subject,
            'currentSubject' => $currentSubject,
            'canManage' => $canManage,
            'materials' => $materials,
            'quizzes' => $quizzes,
        ]);
    }
}
