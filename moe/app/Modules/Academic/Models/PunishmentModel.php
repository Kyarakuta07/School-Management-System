<?php

namespace App\Modules\Academic\Models;

use CodeIgniter\Model;

class PunishmentModel extends Model
{
    protected $table = 'punishment_log';
    protected $primaryKey = 'id_punishment';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'id_nethera',
        'jenis_pelanggaran',
        'deskripsi_pelanggaran',
        'jenis_hukuman',
        'poin_pelanggaran',
        'status_hukuman',
        'tanggal_pelanggaran',
        'tanggal_selesai',
        'locked_features',
        'given_by',
        'released_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get active punishments for a user
     */
    public function getActivePunishments(int $userId)
    {
        return $this->where('id_nethera', $userId)
            ->where('status_hukuman', 'active')
            ->get()
            ->getResultArray();
    }

    /**
     * Check if a user has a specific feature locked
     */
    public function isFeatureLocked(int $userId, string $feature): bool
    {
        $active = $this->getActivePunishments($userId);
        foreach ($active as $p) {
            $locked = explode(',', $p['locked_features']);
            if (in_array($feature, $locked)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Get total violation points for a user
     */
    public function getTotalPoints(int $userId): int
    {
        $result = $this->selectSum('poin_pelanggaran', 'total')
            ->where('id_nethera', $userId)
            ->get()
            ->getRowArray();

        return (int) ($result['total'] ?? 0);
    }
}
