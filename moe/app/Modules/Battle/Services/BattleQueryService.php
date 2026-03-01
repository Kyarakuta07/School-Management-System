<?php

namespace App\Modules\Battle\Services;

use Config\Database;

/**
 * BattleQueryService — Read-only queries for battle history/stats.
 */
class BattleQueryService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Get battle history for a user.
     */
    public function getUserHistory(int $userId, int $limit = 20): array
    {
        return $this->db->query("
            SELECT pb.*,
                   attacker.username as attacker_name,
                   defender.username as defender_name,
                   ap.name as attacker_pet_name,
                   dp.name as defender_pet_name
            FROM pet_battles pb
            LEFT JOIN nethera attacker ON pb.attacker_id = attacker.id_nethera
            LEFT JOIN nethera defender ON pb.defender_id = defender.id_nethera
            LEFT JOIN pets ap ON pb.attacker_pet_id = ap.id
            LEFT JOIN pets dp ON pb.defender_pet_id = dp.id
            WHERE pb.attacker_id = ? OR pb.defender_id = ?
            ORDER BY pb.created_at DESC
            LIMIT ?
        ", [$userId, $userId, $limit])->getResultArray();
    }

    /**
     * Get win count for a user.
     */
    public function getWinCount(int $userId): int
    {
        return $this->db->table('pet_battles')
            ->where('winner_id', $userId)
            ->countAllResults();
    }

    /**
     * Get current win streak for a user.
     */
    public function getWinStreak(int $userId): int
    {
        $battles = $this->db->query("
            SELECT winner_id
            FROM pet_battles
            WHERE attacker_id = ? OR defender_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ", [$userId, $userId])->getResultArray();

        $streak = 0;
        foreach ($battles as $battle) {
            if ((int) $battle['winner_id'] === $userId) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get battle leaderboard.
     */
    public function getLeaderboard(int $limit = 20): array
    {
        return $this->db->query("
            SELECT n.id_nethera, n.username, n.nama_lengkap, n.avatar,
                   COUNT(pb.id) as wins
            FROM nethera n
            INNER JOIN pet_battles pb ON pb.winner_id = n.id_nethera
            WHERE n.status = 'nethera'
            GROUP BY n.id_nethera
            ORDER BY wins DESC
            LIMIT ?
        ", [$limit])->getResultArray();
    }
}
