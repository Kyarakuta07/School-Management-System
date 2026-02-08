<?php
/**
 * ClassController - Handles class and grade API endpoints
 * Mediterranean of Egypt - School Management System
 * 
 * For Hakaes (teacher) role to manage student grades
 */

require_once '../../core/bootstrap.php';

class ClassController
{
    /**
     * Get grades for a specific student
     */
    public function getGrades()
    {
        // Require Hakaes or Vasiki role
        if (!Auth::canManageGrades()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }

        $studentId = $_GET['student_id'] ?? null;

        if (!$studentId) {
            return $this->json(['success' => false, 'error' => 'Student ID required'], 400);
        }

        $grades = DB::queryOne(
            "SELECT pop_culture, mythology, history_of_egypt, oceanology, astronomy, total_pp 
             FROM class_grades 
             WHERE id_nethera = ?
             ORDER BY id_grade DESC
             LIMIT 1",
            [$studentId]
        );

        return $this->json([
            'success' => true,
            'grades' => $grades ?: [
                'pop_culture' => 0,
                'mythology' => 0,
                'history_of_egypt' => 0,
                'oceanology' => 0,
                'astronomy' => 0,
                'total_pp' => 0
            ]
        ]);
    }

    /**
     * Update grades for a specific student
     */
    public function updateGrades()
    {
        // Require Hakaes or Vasiki role
        if (!Auth::canManageGrades()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        // CSRF validation
        if (!isset($input['csrf_token']) || !validate_csrf_token($input['csrf_token'])) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $studentId = $input['student_id'] ?? null;
        $grades = $input['grades'] ?? null;

        if (!$studentId || !$grades) {
            return $this->json(['success' => false, 'error' => 'Student ID and grades required'], 400);
        }

        // Get current user role and their assigned subject (for Hakaes)
        $currentRole = Auth::role();
        $currentUserId = Auth::id();
        $hakaesSubject = null;

        if ($currentRole === 'Hakaes') {
            // Get Hakaes assigned subject from class_schedule
            $schedule = DB::queryOne(
                "SELECT class_name FROM class_schedule WHERE id_hakaes = ?",
                [$currentUserId]
            );
            if ($schedule) {
                $hakaesSubject = strtolower($schedule['class_name']);
            } else {
                return $this->json(['success' => false, 'error' => 'You are not assigned to any subject'], 403);
            }
        }

        // Get existing grades first (to preserve values Hakaes shouldn't modify)
        $existingGrades = DB::queryOne(
            "SELECT pop_culture, mythology, history_of_egypt, oceanology, astronomy FROM class_grades WHERE id_nethera = ?",
            [$studentId]
        );

        // Validate and merge grades
        if ($currentRole === 'Hakaes' && $hakaesSubject) {
            // Hakaes can only update their subject, others remain as is
            $pop_culture = $existingGrades['pop_culture'] ?? 0;
            $mythology = $existingGrades['mythology'] ?? 0;
            $history_of_egypt = $existingGrades['history_of_egypt'] ?? 0;
            $oceanology = $existingGrades['oceanology'] ?? 0;
            $astronomy = $existingGrades['astronomy'] ?? 0;

            // Update only the assigned subject
            switch ($hakaesSubject) {
                case 'pop_culture':
                    $pop_culture = max(0, min(100, intval($grades['pop_culture'] ?? $pop_culture)));
                    break;
                case 'mythology':
                    $mythology = max(0, min(100, intval($grades['mythology'] ?? $mythology)));
                    break;
                case 'history_of_egypt':
                    $history_of_egypt = max(0, min(100, intval($grades['history_of_egypt'] ?? $history_of_egypt)));
                    break;
                case 'oceanology':
                    $oceanology = max(0, min(100, intval($grades['oceanology'] ?? $oceanology)));
                    break;
                case 'astronomy':
                    $astronomy = max(0, min(100, intval($grades['astronomy'] ?? $astronomy)));
                    break;
            }
        } else {
            // Vasiki can update all subjects
            $pop_culture = max(0, min(100, intval($grades['pop_culture'] ?? 0)));
            $mythology = max(0, min(100, intval($grades['mythology'] ?? 0)));
            $history_of_egypt = max(0, min(100, intval($grades['history_of_egypt'] ?? 0)));
            $oceanology = max(0, min(100, intval($grades['oceanology'] ?? 0)));
            $astronomy = max(0, min(100, intval($grades['astronomy'] ?? 0)));
        }

        $totalPP = $pop_culture + $mythology + $history_of_egypt + $oceanology + $astronomy;

        // Check if student exists
        $student = DB::queryOne(
            "SELECT id_nethera, nama_lengkap FROM nethera WHERE id_nethera = ? AND role = 'Nethera'",
            [$studentId]
        );

        if (!$student) {
            return $this->json(['success' => false, 'error' => 'Student not found'], 404);
        }

        // Check if grades record exists
        $existingGrade = DB::queryOne(
            "SELECT id_grade FROM class_grades WHERE id_nethera = ?",
            [$studentId]
        );

        if ($existingGrade) {
            // Update existing record
            $result = DB::execute(
                "UPDATE class_grades SET 
                    pop_culture = ?, mythology = ?, history_of_egypt = ?, oceanology = ?, astronomy = ?, 
                    total_pp = ?, updated_at = NOW()
                 WHERE id_nethera = ?",
                [$pop_culture, $mythology, $history_of_egypt, $oceanology, $astronomy, $totalPP, $studentId]
            );
        } else {
            // Insert new record
            $result = DB::execute(
                "INSERT INTO class_grades (id_nethera, class_name, pop_culture, mythology, history_of_egypt, oceanology, astronomy, total_pp)
                 VALUES (?, 'Default Class', ?, ?, ?, ?, ?, ?)",
                [$studentId, $pop_culture, $mythology, $history_of_egypt, $oceanology, $astronomy, $totalPP]
            );
        }

        if ($result) {
            // Log the action
            error_log("Grades updated for student {$student['nama_lengkap']} (ID: $studentId) by " . Auth::name());

            return $this->json([
                'success' => true,
                'message' => "Grades updated for {$student['nama_lengkap']}",
                'total_pp' => $totalPP
            ]);
        }

        return $this->json(['success' => false, 'error' => 'Failed to save grades'], 500);
    }

    /**
     * Get all Nethera students (for dropdown)
     */
    public function getStudents()
    {
        // Require Hakaes or Vasiki role
        if (!Auth::canManageGrades()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }

        $students = DB::query(
            "SELECT n.id_nethera, n.nama_lengkap, n.username, s.nama_sanctuary,
                    COALESCE(cg.total_pp, 0) as total_pp
             FROM nethera n
             LEFT JOIN sanctuary s ON n.id_sanctuary = s.id_sanctuary
             LEFT JOIN class_grades cg ON n.id_nethera = cg.id_nethera
             WHERE n.role = 'Nethera'
             ORDER BY n.nama_lengkap ASC"
        );

        return $this->json([
            'success' => true,
            'students' => $students
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
