<?php
/**
 * MaterialController - Handles class material API endpoints
 * Mediterranean of Egypt - School Management System
 * 
 * For Hakaes (teacher) role to manage subject materials
 */

require_once '../../core/bootstrap.php';

class MaterialController
{
    /**
     * Get materials for a specific subject
     */
    public function getMaterials()
    {
        $subject = $_GET['subject'] ?? '';
        $validSubjects = ['pop_culture', 'mythology', 'history_of_egypt', 'oceanology', 'astronomy'];

        if (!in_array($subject, $validSubjects)) {
            return $this->json(['success' => false, 'error' => 'Invalid subject'], 400);
        }

        $materials = DB::query(
            "SELECT m.*, n.nama_lengkap as creator_name 
             FROM class_materials m
             LEFT JOIN nethera n ON m.created_by = n.id_nethera
             WHERE m.subject = ? AND m.is_active = 1
             ORDER BY m.created_at DESC",
            [$subject]
        );

        return $this->json([
            'success' => true,
            'materials' => $materials
        ]);
    }

    /**
     * Add new material (Hakaes only)
     */
    public function addMaterial()
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

        $subject = $input['subject'] ?? '';
        $title = trim($input['title'] ?? '');
        $type = $input['material_type'] ?? 'text';
        $content = trim($input['content'] ?? '');

        // Validation
        $validSubjects = ['pop_culture', 'mythology', 'history_of_egypt', 'oceanology', 'astronomy'];
        $validTypes = ['text', 'youtube', 'pdf'];

        if (!in_array($subject, $validSubjects)) {
            return $this->json(['success' => false, 'error' => 'Invalid subject'], 400);
        }

        if (!in_array($type, $validTypes)) {
            return $this->json(['success' => false, 'error' => 'Invalid material type'], 400);
        }

        if (empty($title)) {
            return $this->json(['success' => false, 'error' => 'Title is required'], 400);
        }

        if (empty($content)) {
            return $this->json(['success' => false, 'error' => 'Content is required'], 400);
        }

        // For text type, wrap in paragraph if not HTML
        if ($type === 'text' && !preg_match('/<[^>]+>/', $content)) {
            $content = '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
        }

        // Insert material
        $result = DB::execute(
            "INSERT INTO class_materials (subject, title, material_type, content, created_by)
             VALUES (?, ?, ?, ?, ?)",
            [$subject, $title, $type, $content, Auth::id()]
        );

        if ($result) {
            return $this->json([
                'success' => true,
                'message' => 'Material added successfully'
            ]);
        }

        return $this->json(['success' => false, 'error' => 'Failed to add material'], 500);
    }

    /**
     * Delete material (Hakaes only)
     */
    public function deleteMaterial()
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

        $materialId = intval($input['id_material'] ?? 0);

        if (!$materialId) {
            return $this->json(['success' => false, 'error' => 'Material ID required'], 400);
        }

        // Soft delete (set is_active = 0)
        $result = DB::execute(
            "UPDATE class_materials SET is_active = 0 WHERE id_material = ?",
            [$materialId]
        );

        if ($result) {
            return $this->json([
                'success' => true,
                'message' => 'Material deleted successfully'
            ]);
        }

        return $this->json(['success' => false, 'error' => 'Failed to delete material'], 500);
    }

    /**
     * Update material (Hakaes only - text type only)
     */
    public function updateMaterial()
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

        $materialId = intval($input['id_material'] ?? 0);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');

        if (!$materialId) {
            return $this->json(['success' => false, 'error' => 'Material ID required'], 400);
        }

        if (empty($title)) {
            return $this->json(['success' => false, 'error' => 'Title is required'], 400);
        }

        if (empty($content)) {
            return $this->json(['success' => false, 'error' => 'Content is required'], 400);
        }

        // Verify material exists and is text type
        $material = DB::queryOne(
            "SELECT id_material, material_type FROM class_materials WHERE id_material = ? AND is_active = 1",
            [$materialId]
        );

        if (!$material) {
            return $this->json(['success' => false, 'error' => 'Material not found'], 404);
        }

        if ($material['material_type'] !== 'text') {
            return $this->json(['success' => false, 'error' => 'Only text materials can be edited'], 400);
        }

        // Wrap content in paragraph if not HTML
        if (!preg_match('/<[^>]+>/', $content)) {
            $content = '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
        }

        // Update material
        $result = DB::execute(
            "UPDATE class_materials SET title = ?, content = ?, updated_at = NOW() WHERE id_material = ?",
            [$title, $content, $materialId]
        );

        if ($result) {
            return $this->json([
                'success' => true,
                'message' => 'Material updated successfully'
            ]);
        }

        return $this->json(['success' => false, 'error' => 'Failed to update material'], 500);
    }

    /**
     * Upload PDF material (Hakaes only)
     * Uses multipart/form-data for file upload
     */
    public function uploadPdf()
    {
        // Require Hakaes or Vasiki role
        if (!Auth::canManageGrades()) {
            return $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }

        // CSRF validation
        if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $subject = $_POST['subject'] ?? '';
        $title = trim($_POST['title'] ?? '');

        // Validation
        $validSubjects = ['pop_culture', 'mythology', 'history_of_egypt', 'oceanology', 'astronomy'];

        if (!in_array($subject, $validSubjects)) {
            return $this->json(['success' => false, 'error' => 'Invalid subject'], 400);
        }

        if (empty($title)) {
            return $this->json(['success' => false, 'error' => 'Title is required'], 400);
        }

        // Check if file was uploaded
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'No file uploaded';
            if (isset($_FILES['pdf_file'])) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
                    UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                    UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                ];
                $errorMsg = $uploadErrors[$_FILES['pdf_file']['error']] ?? 'Upload error';
            }
            return $this->json(['success' => false, 'error' => $errorMsg], 400);
        }

        $file = $_FILES['pdf_file'];

        // Validate file type (PDF only)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            return $this->json(['success' => false, 'error' => 'Only PDF files are allowed'], 400);
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return $this->json(['success' => false, 'error' => 'File size must be less than 5MB'], 400);
        }

        // Generate secure filename
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.pdf';
        $uploadDir = realpath(__DIR__ . '/../../../uploads/materials/');

        if (!$uploadDir) {
            // Create directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../../uploads/materials/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $uploadDir = realpath($uploadDir);
        }

        $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $relativePath = 'uploads/materials/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return $this->json(['success' => false, 'error' => 'Failed to save file'], 500);
        }

        // Insert into database
        $result = DB::execute(
            "INSERT INTO class_materials (subject, title, material_type, content, file_path, created_by)
             VALUES (?, ?, 'pdf', ?, ?, ?)",
            [$subject, $title, $file['name'], $relativePath, Auth::id()]
        );

        if ($result) {
            return $this->json([
                'success' => true,
                'message' => 'PDF uploaded successfully',
                'file_path' => $relativePath
            ]);
        }

        // Cleanup file if database insert failed
        @unlink($filePath);
        return $this->json(['success' => false, 'error' => 'Failed to save material'], 500);
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
