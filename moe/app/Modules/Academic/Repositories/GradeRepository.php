<?php

namespace App\Modules\Academic\Repositories;

use CodeIgniter\Database\BaseConnection;

/**
 * GradeRepository
 *
 * Handles complex multi-table read queries for the Academic domain.
 * Simple single-table CRUD stays in GradeModel.
 */
class GradeRepository
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Get all grades with user and sanctuary info (3-table JOIN).
     */
    public function getGradesWithInfo(): array
    {
        return $this->db->table('class_grades')
            ->select('class_grades.*, nethera.nama_lengkap, sanctuary.nama_sanctuary')
            ->join('nethera', 'class_grades.id_nethera = nethera.id_nethera')
            ->join('sanctuary', 'nethera.id_sanctuary = sanctuary.id_sanctuary', 'left')
            ->orderBy('class_grades.id_grade', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Search grades by query (3-table JOIN + dynamic LIKE).
     */
    public function search(string $query): array
    {
        $builder = $this->db->table('class_grades')
            ->select('class_grades.*, nethera.nama_lengkap, sanctuary.nama_sanctuary')
            ->join('nethera', 'class_grades.id_nethera = nethera.id_nethera')
            ->join('sanctuary', 'nethera.id_sanctuary = sanctuary.id_sanctuary', 'left');

        if (!empty($query)) {
            $builder->groupStart()
                ->like('nethera.nama_lengkap', $query)
                ->orLike('sanctuary.nama_sanctuary', $query)
                ->orLike('class_grades.class_name', $query)
                ->groupEnd();
        }

        return $builder->orderBy('class_grades.id_grade', 'DESC')->get()->getResultArray();
    }

    /**
     * Get sanctuary points for chart (SUM aggregation + 3-table JOIN).
     */
    public function getSanctuaryPoints(): array
    {
        return $this->db->table('nethera n')
            ->select('s.nama_sanctuary, SUM(cg.total_pp) as total_points')
            ->join('class_grades cg', 'n.id_nethera = cg.id_nethera')
            ->join('sanctuary s', 'n.id_sanctuary = s.id_sanctuary')
            ->groupBy('s.nama_sanctuary')
            ->orderBy('total_points', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get top N scholars with sanctuary info.
     * Cached for 5 minutes.
     */
    public function getTopScholars(int $limit = 5): array
    {
        return cache()->remember("top_scholars_{$limit}", 300, function () use ($limit) {
            return $this->db->table('class_grades cg')
                ->select('n.id_nethera, n.nama_lengkap, s.nama_sanctuary, s.id_sanctuary, cg.total_pp')
                ->join('nethera n', 'cg.id_nethera = n.id_nethera')
                ->join('sanctuary s', 'n.id_sanctuary = s.id_sanctuary', 'left')
                ->where('n.role', ROLE_NETHERA)
                ->where('n.status_akun', 'Aktif')
                ->where('cg.total_pp >', 0)
                ->orderBy('cg.total_pp', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        });
    }

    /**
     * Get sanctuary ranking by aggregated total_pp.
     * Cached for 10 minutes.
     */
    public function getSanctuaryRanking(): array
    {
        return cache()->remember('sanctuary_ranking', 600, function () {
            return $this->db->table('class_grades cg')
                ->select('s.nama_sanctuary, SUM(cg.total_pp) as total_points, COUNT(DISTINCT cg.id_nethera) as member_count')
                ->join('nethera n', 'cg.id_nethera = n.id_nethera')
                ->join('sanctuary s', 'n.id_sanctuary = s.id_sanctuary')
                ->groupBy('s.id_sanctuary')
                ->orderBy('total_points', 'DESC')
                ->get()
                ->getResultArray();
        });
    }

    /**
     * Get all Nethera students with full grade data (for admin/hakaes management).
     */
    public function getAllStudentsWithGrades(): array
    {
        return $this->db->table('nethera n')
            ->select('n.id_nethera, n.nama_lengkap, n.username, s.nama_sanctuary,
                      COALESCE(cg.pop_culture, 0) as pop_culture,
                      COALESCE(cg.mythology, 0) as mythology,
                      COALESCE(cg.history_of_egypt, 0) as history_of_egypt,
                      COALESCE(cg.oceanology, 0) as oceanology,
                      COALESCE(cg.astronomy, 0) as astronomy,
                      COALESCE(cg.total_pp, 0) as total_pp')
            ->join('sanctuary s', 'n.id_sanctuary = s.id_sanctuary', 'left')
            ->join('class_grades cg', 'n.id_nethera = cg.id_nethera', 'left')
            ->where('n.role', ROLE_NETHERA)
            ->orderBy('cg.total_pp', 'DESC')
            ->orderBy('n.nama_lengkap', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get all students with sanctuary info and total_pp (for teacher view).
     */
    public function getStudentsWithGrades(): array
    {
        return $this->db->table('nethera AS n')
            ->select('n.id_nethera, n.nama_lengkap, n.username, s.nama_sanctuary, COALESCE(cg.total_pp, 0) AS total_pp')
            ->join('sanctuary AS s', 's.id_sanctuary = n.id_sanctuary', 'left')
            ->join('class_grades AS cg', 'cg.id_nethera = n.id_nethera', 'left')
            ->where('n.role', ROLE_NETHERA)
            ->orderBy('n.nama_lengkap', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Get all grades/classes with student counts.
     */
    public function getGradesWithCounts(): array
    {
        return $this->db->table('grades g')
            ->select('g.*, COUNT(n.id_nethera) as student_count')
            ->join('nethera n', 'g.id_kelas = n.class_id AND n.status = "nethera"', 'left')
            ->groupBy('g.id_kelas')
            ->orderBy('g.nama_kelas')
            ->get()
            ->getResultArray();
    }
}
