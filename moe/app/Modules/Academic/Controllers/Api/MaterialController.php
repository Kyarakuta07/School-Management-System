<?php

namespace App\Modules\Academic\Controllers\Api;
use App\Kernel\BaseApiController;

use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Academic\Models\MaterialModel;

/**
 * Material API Controller
 * 
 * Ported from legacy MaterialController.php
 * 
 * Endpoints:
 *   GET  /api/materials           â†’ index()
 *   POST /api/materials/add       â†’ add()
 *   POST /api/materials/update    â†’ update()
 *   POST /api/materials/delete    â†’ delete()
 *   POST /api/materials/upload    â†’ upload()
 */
class MaterialController extends BaseApiController
{
    protected MaterialModel $materialModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->materialModel = new MaterialModel();
    }

    public function index(): ResponseInterface
    {
        $subject = $this->request->getGet('subject') ?? '';

        if (!\App\Modules\Academic\Config\SubjectConfig::isValid($subject)) {
            return $this->error('Invalid subject', 400, 'VALIDATION_ERROR');
        }

        $materials = $this->materialModel->getBySubject($subject);

        return $this->success(['materials' => $materials]);
    }

    public function add(): ResponseInterface
    {
        $input = $this->getInput();
        $subject = $input['subject'] ?? '';
        $title = trim($input['title'] ?? '');
        $type = $input['material_type'] ?? 'text';
        $content = trim($input['content'] ?? '');

        if (!\App\Modules\Academic\Config\SubjectConfig::isValid($subject))
            return $this->error('Invalid subject', 400);
        if (!in_array($type, ['text', 'youtube', 'pdf']))
            return $this->error('Invalid material type', 400);
        if (empty($title))
            return $this->error('Title is required', 400);
        if (empty($content))
            return $this->error('Content is required', 400);

        if ($type === 'text') {
            $content = wrap_plain_text($content);
        }

        $this->materialModel->insert([
            'subject' => $subject,
            'title' => $title,
            'material_type' => $type,
            'content' => $content,
            'created_by' => $this->userId,
        ]);

        return $this->success([], 'Material added successfully');
    }

    public function update(): ResponseInterface
    {
        $input = $this->getInput();
        $materialId = (int) ($input['id_material'] ?? 0);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');

        if (!$materialId)
            return $this->error('Material ID required', 400);
        if (empty($title))
            return $this->error('Title is required', 400);
        if (empty($content))
            return $this->error('Content is required', 400);

        $material = $this->materialModel->getActiveMaterial($materialId);

        if (!$material)
            return $this->error('Material not found', 404);
        if ($material['material_type'] !== 'text')
            return $this->error('Only text materials can be edited', 400);

        // A7 fix: Verify creator ownership
        if ((int) ($material['created_by'] ?? 0) !== $this->userId) {
            return $this->error('You can only edit your own materials', 403, 'FORBIDDEN');
        }

        $content = wrap_plain_text($content);

        $this->materialModel->update($materialId, [
            'title' => $title,
            'content' => $content,
        ]);

        return $this->success([], 'Material updated successfully');
    }

    public function delete(): ResponseInterface
    {
        $input = $this->getInput();
        $materialId = (int) ($input['id_material'] ?? 0);

        if (!$materialId)
            return $this->error('Material ID required', 400);

        // A6 fix: Verify creator ownership before delete
        $material = $this->materialModel->getActiveMaterial($materialId);
        if (!$material) {
            return $this->error('Material not found', 404, 'NOT_FOUND');
        }
        if ((int) ($material['created_by'] ?? 0) !== $this->userId) {
            return $this->error('You can only delete your own materials', 403, 'FORBIDDEN');
        }

        $this->materialModel->softDelete($materialId);

        return $this->success([], 'Material deleted successfully');
    }

    public function upload(): ResponseInterface
    {
        $subject = $this->request->getPost('subject') ?? '';
        $title = trim($this->request->getPost('title') ?? '');

        if (!\App\Modules\Academic\Config\SubjectConfig::isValid($subject))
            return $this->error('Invalid subject', 400);
        if (empty($title))
            return $this->error('Title is required', 400);

        /** @var \CodeIgniter\HTTP\IncomingRequest $request */
        $request = $this->request;
        $file = $request->getFile('pdf_file');
        if (!$file || !$file->isValid()) {
            return $this->error('No valid file uploaded', 400, 'VALIDATION_ERROR');
        }

        if ($file->getMimeType() !== 'application/pdf') {
            return $this->error('Only PDF files are allowed', 400, 'VALIDATION_ERROR');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->error('File size must be less than 5MB', 400, 'VALIDATION_ERROR');
        }

        $newName = $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/materials', $newName);

        $this->materialModel->insert([
            'subject' => $subject,
            'title' => $title,
            'material_type' => 'pdf',
            'content' => $file->getClientName(),
            'file_path' => 'writable/uploads/materials/' . $newName,
            'created_by' => $this->userId,
        ]);

        return $this->success([], 'PDF uploaded successfully');
    }

    /**
     * Serve a PDF material file for viewing or download.
     * GET /api/materials/download?id=X&dl=1
     */
    public function download(): ResponseInterface
    {
        $materialId = (int) ($this->request->getGet('id') ?? 0);
        $forceDownload = $this->request->getGet('dl') === '1';

        if (!$materialId) {
            return $this->pdfError('Material ID required');
        }

        $material = $this->materialModel->find($materialId);
        if (!$material || ($material['is_active'] ?? 1) == 0) {
            return $this->pdfError('Material not found');
        }
        if ($material['material_type'] !== 'pdf') {
            return $this->pdfError('Not a PDF material');
        }

        $filePath = !empty($material['file_path']) ? ROOTPATH . $material['file_path'] : '';
        if (empty($filePath) || !is_file($filePath)) {
            return $this->pdfError('PDF file not found on server. The file may have been deleted.');
        }

        $fileName = $material['content'] ?: basename($filePath);
        $fileSize = filesize($filePath);

        if ($forceDownload) {
            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/octet-stream')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->setHeader('Content-Length', (string) $fileSize)
                ->setBody(file_get_contents($filePath));
        }

        // Inline view in iframe
        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setHeader('Content-Length', (string) $fileSize)
            ->setBody(file_get_contents($filePath));
    }

    /**
     * Return an HTML error page (for display inside iframe, not JSON).
     */
    private function pdfError(string $message): ResponseInterface
    {
        $html = '<!DOCTYPE html><html><head><style>
            body { background: #1a1a2e; color: #fff; font-family: sans-serif;
                display: flex; align-items: center; justify-content: center;
                height: 100vh; margin: 0; text-align: center; }
            .err { padding: 40px; }
            .err i { font-size: 48px; color: #e74c3c; margin-bottom: 16px; }
            h3 { color: #e74c3c; }
            p { color: rgba(255,255,255,0.6); }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head><body>
            <div class="err"><i class="fas fa-file-pdf"></i>
            <h3>PDF Unavailable</h3><p>' . esc($message) . '</p></div>
        </body></html>';

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'text/html')
            ->setBody($html);
    }
}
