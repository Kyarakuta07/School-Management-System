<?php

namespace App\Modules\Academic\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Modules\Academic\Services\GradeService;
use App\Modules\Academic\Config\SubjectConfig;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Class (Grades) API Controller
 * 
 * Ported from legacy ClassController.php
 * Note: This uses a different auth filter (requires Hakaes/Vasiki role)
 * 
 * Endpoints:
 *   GET  /api/class/grades   → grades()
 *   POST /api/class/grades   → updateGrades()
 *   GET  /api/class/students → students()
 */
class ClassController extends BaseApiController
{
    protected GradeService $gradeService;

    public function __construct()
    {
        $this->gradeService = new GradeService();
    }

    public function grades(): ResponseInterface
    {
        $studentId = (int) ($this->request->getGet('student_id') ?? 0);

        if (!$studentId) {
            return $this->error('Student ID required', 400, 'VALIDATION_ERROR');
        }

        // A1 fix: Nethera can only view their own grades
        $role = session()->get('role');
        if ($role === ROLE_NETHERA && $studentId !== $this->userId) {
            return $this->error('Access denied', 403, 'FORBIDDEN');
        }

        $grades = $this->gradeService->getStudentGrades($studentId);

        return $this->success([
            'grades' => $grades ?: [
                'pop_culture' => 0,
                'mythology' => 0,
                'history_of_egypt' => 0,
                'oceanology' => 0,
                'astronomy' => 0,
                'total_pp' => 0,
            ],
        ]);
    }

    public function updateGrades(): ResponseInterface
    {
        // A1 fix: Only Vasiki and Hakaes can update grades
        $role = session()->get('role');
        if (cannot('manage_grades')) {
            return $this->error('Access denied — teachers only', 403, 'FORBIDDEN');
        }

        $input = $this->getInput();
        $studentId = (int) ($input['student_id'] ?? 0);
        $grades = $input['grades'] ?? null;

        if (!$studentId || !$grades) {
            return $this->error('Student ID and grades required', 400, 'VALIDATION_ERROR');
        }

        // Get existing grades for merge
        $existing = $this->gradeService->getStudentGrades($studentId);

        // Calculate new grades (Vasiki can update all)
        $values = [];
        foreach (SubjectConfig::getSubjectKeys() as $sub) {
            $values[$sub] = max(0, min(100, (int) ($grades[$sub] ?? ($existing[$sub] ?? 0))));
        }
        $totalPP = array_sum($values);
        $values['total_pp'] = $totalPP;

        // Verify student exists
        $student = $this->gradeService->findStudent($studentId);
        if (!$student) {
            return $this->error('Student not found', 404, 'NOT_FOUND');
        }

        // Upsert grades via service
        $this->gradeService->upsertStudentGrades($studentId, $values);

        return $this->success([
            'total_pp' => $totalPP,
        ], "Grades updated for {$student['nama_lengkap']}");
    }

    public function students(): ResponseInterface
    {
        // A1 fix: Only Vasiki and Hakaes can list all students
        $role = session()->get('role');
        if (cannot('manage_grades')) {
            return $this->error('Access denied — teachers only', 403, 'FORBIDDEN');
        }

        $students = $this->gradeService->getStudentsWithGrades();
        return $this->success(['students' => $students]);
    }
}
