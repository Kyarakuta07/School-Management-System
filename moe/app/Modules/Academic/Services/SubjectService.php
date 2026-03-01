<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Config\SubjectConfig;
use App\Modules\Academic\Models\MaterialModel;
use Config\Database;

/**
 * SubjectService — Handles subject and material operations.
 */
class SubjectService
{
    protected $db;
    protected MaterialModel $materialModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->materialModel = new MaterialModel();
    }

    /**
     * Get all materials for a subject.
     */
    public function getMaterials(string $subjectKey): array
    {
        return $this->materialModel
            ->where('subject', $subjectKey)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Add a new material.
     */
    public function addMaterial(array $data): int
    {
        $this->materialModel->insert($data);
        return $this->materialModel->getInsertID();
    }

    /**
     * Update a material.
     */
    public function updateMaterial(int $id, array $data): bool
    {
        return $this->materialModel->update($id, $data);
    }

    /**
     * Delete a material.
     */
    public function deleteMaterial(int $id): bool
    {
        return $this->materialModel->delete($id);
    }

    /**
     * Get all subjects from config.
     */
    public function getAllSubjects(): array
    {
        return SubjectConfig::$subjects;
    }
}
