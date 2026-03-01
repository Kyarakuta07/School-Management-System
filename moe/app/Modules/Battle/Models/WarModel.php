<?php

namespace App\Modules\Battle\Models;

use CodeIgniter\Model;

class WarModel extends Model
{
    protected $table = 'sanctuary_wars';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'war_date',
        'status',
        'winner_sanctuary_id',
        'created_at'
    ];

    protected $useTimestamps = false;

    /**
     * Get active war
     */
    public function getActiveWar()
    {
        return $this->where('status', 'active')
            ->orderBy('war_date', 'DESC')
            ->first();
    }

    /**
     * Get scores for a specific war
     */
    public function getWarScores(int $warId)
    {
        return $this->db->table('sanctuary_war_scores sws')
            ->select('sws.*, s.nama_sanctuary, s.logo_url')
            ->join('sanctuary s', 's.id_sanctuary = sws.sanctuary_id')
            ->where('sws.war_id', $warId)
            ->orderBy('sws.total_points', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Update (or insert) sanctuary war scores
     */
    public function updateScore(int $warId, int $sanctuaryId, int $wins, int $losses, int $ties): void
    {
        $pointsEarned = ($wins * 3) + ($ties * 1);

        // Atomic upsert with proper SQL increment expressions (F8 fix)
        // Uses INSERT...ON DUPLICATE KEY UPDATE for thread-safe concurrent updates
        $this->db->query(
            "INSERT INTO sanctuary_war_scores (war_id, sanctuary_id, wins, losses, ties, total_points)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                wins = wins + VALUES(wins),
                losses = losses + VALUES(losses),
                ties = ties + VALUES(ties),
                total_points = total_points + VALUES(total_points)",
            [$warId, $sanctuaryId, $wins, $losses, $ties, $pointsEarned]
        );
    }
}
