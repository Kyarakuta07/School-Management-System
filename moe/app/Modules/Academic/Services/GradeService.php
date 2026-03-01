<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\GradeModel;
use App\Modules\Academic\Repositories\GradeRepository;

/**
 * GradeService — Handles class/grade related operations.
 *
 * Refactored: complex read queries delegated to GradeRepository.
 * Single-table reads via GradeModel. Writes remain in service.
 */
class GradeService
{
    protected GradeModel $gradeModel;
    protected GradeRepository $gradeRepo;

    public function __construct()
    {
        $this->gradeModel = new GradeModel();
        $this->gradeRepo = new GradeRepository();
    }

    // ── Read (delegated to Repository) ──

    public function getGradesWithCounts(): array
    {
        return $this->gradeRepo->getGradesWithCounts();
    }

    public function getStudentsWithGrades(): array
    {
        return $this->gradeRepo->getStudentsWithGrades();
    }

    // ── Read (simple — delegated to Model) ──

    public function getStudentGrades(int $studentId): ?array
    {
        return $this->gradeModel->getUserGrades($studentId);
    }

    // ── Read (direct — no JOIN needed) ──

    public function getStudents(int $classId): array
    {
        return \Config\Database::connect()->table('nethera')
            ->select('id_nethera, username, nama_lengkap, avatar, gold')
            ->where('class_id', $classId)
            ->where('status', 'nethera')
            ->orderBy('nama_lengkap')
            ->get()
            ->getResultArray();
    }

    public function findStudent(int $studentId): ?array
    {
        return \Config\Database::connect()->table('nethera')
            ->select('id_nethera, nama_lengkap')
            ->where('id_nethera', $studentId)
            ->where('role', ROLE_NETHERA)
            ->get()->getRowArray();
    }

    // ── Write Operations ──

    public function updateGrades(array $grades): bool
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            foreach ($grades as $grade) {
                $db->table('student_grades')
                    ->where('id', $grade['id'])
                    ->update([
                        'value' => $grade['value'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[GradeService] updateGrades failed: ' . $e->getMessage());
            return false;
        }
    }

    public function upsertStudentGrades(int $studentId, array $values): void
    {
        $db = \Config\Database::connect();
        $existing = $db->table('class_grades')
            ->where('id_nethera', $studentId)
            ->get()->getRowArray();

        if ($existing) {
            $db->table('class_grades')
                ->where('id_nethera', $studentId)
                ->update($values);
        } else {
            $values['id_nethera'] = $studentId;
            $values['class_name'] = 'Default Class';
            $db->table('class_grades')->insert($values);
        }
    }
}
