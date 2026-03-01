<?php

namespace App\Modules\Academic\Models;

use CodeIgniter\Model;

/**
 * MaterialModel — class_materials table.
 * Replaces raw $db->table('class_materials') calls in MaterialController.
 */
class MaterialModel extends Model
{
    protected $table = 'class_materials';
    protected $primaryKey = 'id_material';
    protected $allowedFields = [
        'subject',
        'title',
        'material_type',
        'content',
        'file_path',
        'is_active',
        'created_by',
        'created_at',
    ];
    protected $useTimestamps = false;

    /**
     * Get all active materials for a subject, with creator name.
     */
    public function getBySubject(string $subject): array
    {
        return $this->db->table('class_materials AS m')
            ->select('m.*, n.nama_lengkap AS creator_name')
            ->join('nethera AS n', 'n.id_nethera = m.created_by', 'left')
            ->where('m.subject', $subject)
            ->where('m.is_active', 1)
            ->orderBy('m.created_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Get a single active material by ID.
     */
    public function getActiveMaterial(int $id): ?array
    {
        return $this->where('id_material', $id)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Soft-delete a material (set is_active = 0).
     */
    public function softDelete(int $id): void
    {
        $this->update($id, ['is_active' => 0]);
    }
}
