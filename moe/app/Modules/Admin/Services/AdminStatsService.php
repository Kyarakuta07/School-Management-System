<?php

namespace App\Modules\Admin\Services;

use App\Modules\User\Models\UserModel;
use Config\Database;

/**
 * AdminStatsService — Provides dashboard statistics for admin panel.
 */
class AdminStatsService
{
    protected $db;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->userModel = new UserModel();
    }

    /**
     * Get dashboard statistics (user counts by status).
     */
    public function getDashboardStats(): array
    {
        return $this->userModel->getStatusCounts();
    }

    /**
     * Get latest registered users.
     */
    public function getLatestUsers(int $limit = 5): array
    {
        return $this->userModel->getLatestUsers($limit);
    }

    /**
     * Get sanctuary chart data (member counts per sanctuary).
     */
    public function getSanctuaryChartData(): array
    {
        $result = $this->db->table('sanctuary s')
            ->select('s.nama_sanctuary, COUNT(n.id_nethera) as member_count')
            ->join('nethera n', 's.id_sanctuary = n.sanctuary_id AND n.status = "nethera"', 'left')
            ->groupBy('s.id_sanctuary')
            ->orderBy('s.nama_sanctuary')
            ->get()
            ->getResultArray();

        $labels = array_column($result, 'nama_sanctuary');
        $values = array_map('intval', array_column($result, 'member_count'));

        return ['labels' => $labels, 'values' => $values];
    }
}
