<?php

namespace App\Modules\Sanctuary\Repositories;

use CodeIgniter\Database\BaseConnection;

/**
 * SanctuaryRepository
 *
 * Handles complex multi-table read queries for the Sanctuary domain.
 * Simple single-table CRUD stays in SanctuaryModel.
 */
class SanctuaryRepository
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Get total PP (Performance Points) for all members of a sanctuary.
     * JOIN aggregation across class_grades + nethera.
     */
    public function getTotalPP(int $sanctuaryId): int
    {
        $result = $this->db->table('class_grades AS cg')
            ->selectSum('cg.total_pp')
            ->join('nethera AS n', 'cg.id_nethera = n.id_nethera')
            ->where('n.id_sanctuary', $sanctuaryId)
            ->get()
            ->getRowArray();

        return (int) ($result['total_pp'] ?? 0);
    }

    /**
     * Get leadership (Hosa & Vizier) for a sanctuary.
     * Cached for 10 minutes.
     *
     * @return array{hosa: array|null, viziers: array}
     */
    public function getLeaders(int $sanctuaryId): array
    {
        return cache()->remember("sanctuary_leaders_{$sanctuaryId}", 600, function () use ($sanctuaryId) {
            $leaders = $this->db->table('nethera')
                ->select('nama_lengkap, sanctuary_role, profile_photo, username')
                ->where('id_sanctuary', $sanctuaryId)
                ->whereIn('sanctuary_role', ['hosa', 'vizier'])
                ->orderBy("FIELD(sanctuary_role, 'hosa', 'vizier')", '', false)
                ->get()
                ->getResultArray();

            $hosa = null;
            $viziers = [];

            foreach ($leaders as $leader) {
                if ($leader['sanctuary_role'] === 'hosa') {
                    $hosa = $leader;
                } else {
                    $viziers[] = $leader;
                }
            }

            return ['hosa' => $hosa, 'viziers' => $viziers];
        });
    }

    /**
     * Get regular members (non-leader) for barracks display.
     * LEFT JOIN + multiple WHERE filters.
     */
    public function getMembers(int $sanctuaryId, int $limit = 50): array
    {
        return $this->db->table('nethera AS n')
            ->select('n.id_nethera, n.nama_lengkap, n.username, n.profile_photo, n.sanctuary_role, COALESCE(SUM(cg.total_pp), 0) as total_pp')
            ->join('class_grades AS cg', 'n.id_nethera = cg.id_nethera', 'left')
            ->where('n.id_sanctuary', $sanctuaryId)
            ->where('n.role', ROLE_NETHERA)
            ->where('n.status_akun', 'Aktif')
            ->whereNotIn('n.sanctuary_role', ['hosa', 'vizier'])
            ->groupBy('n.id_nethera')
            ->orderBy('total_pp', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get consolidated stats for a sanctuary.
     * Correlated subqueries + Cached for 5 minutes.
     */
    public function getSanctuaryStats(int $sanctuaryId): ?array
    {
        return cache()->remember("sanctuary_stats_{$sanctuaryId}", 300, function () use ($sanctuaryId) {
            return $this->db->table('sanctuary s')
                ->select('s.*, 
                          (SELECT COUNT(*) FROM nethera WHERE id_sanctuary = s.id_sanctuary) as member_count,
                          (SELECT SUM(total_pp) FROM class_grades cg JOIN nethera n ON n.id_nethera = cg.id_nethera WHERE n.id_sanctuary = s.id_sanctuary) as total_pp')
                ->where('s.id_sanctuary', $sanctuaryId)
                ->get()
                ->getRowArray();
        });
    }
}
