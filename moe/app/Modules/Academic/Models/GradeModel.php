<?php

namespace App\Modules\Academic\Models;

use CodeIgniter\Model;

class GradeModel extends Model
{
    protected $table = 'class_grades';
    protected $primaryKey = 'id_grade';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_nethera',
        'class_name',
        'history',
        'pop_culture',
        'mythology',
        'history_of_egypt',
        'oceanology',
        'astronomy',
        'total_pp'
    ];

    // ── Simple single-table queries (stay in Model) ──

    /**
     * Get latest grades for a user.
     */
    public function getUserGrades(int $userId): ?array
    {
        return $this->select('pop_culture, mythology, history_of_egypt, oceanology, astronomy, total_pp')
            ->where('id_nethera', $userId)
            ->orderBy('id_grade', 'DESC')
            ->first();
    }

    /**
     * Get user ranking based on total_pp.
     */
    public function getUserRank(int $totalPp): int
    {
        return (int) $this->where('total_pp >', $totalPp)->countAllResults() + 1;
    }
}
