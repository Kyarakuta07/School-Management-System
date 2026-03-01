<?php

namespace App\Modules\Battle\Models;

use CodeIgniter\Model;

/**
 * WarBattleModel — war_battles table.
 * Replaces raw $db->table('war_battles') calls in SanctuaryWarController.
 */
class WarBattleModel extends Model
{
    protected $table = 'war_battles';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'war_id',
        'user_id',
        'opponent_id',
        'user_sanctuary_id',
        'opponent_sanctuary_id',
        'user_pet_id',
        'opponent_pet_id',
        'winner_user_id',
        'points_earned',
        'gold_earned',
        'created_at',
    ];
    protected $useTimestamps = false;

    /**
     * Record a new war battle.
     */
    public function recordBattle(array $data): void
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->insert($data);
    }

    /**
     * Count tickets (battles) used by a user in a specific war.
     */
    public function getTicketsUsed(int $warId, int $userId): int
    {
        return (int) $this->where('war_id', $warId)
            ->where('user_id', $userId)
            ->countAllResults();
    }

    /**
     * Count tickets with FOR UPDATE lock — use inside TX to prevent TOCTOU (F7 fix).
     */
    public function getTicketsUsedForUpdate(int $warId, int $userId): int
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM war_battles WHERE war_id = ? AND user_id = ? FOR UPDATE",
            [$warId, $userId]
        )->getRowArray();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Get a user's contribution in a specific war.
     */
    public function getUserContribution(int $warId, int $userId): array
    {
        $battles = $this->where('war_id', $warId)
            ->where('user_id', $userId)
            ->findAll();

        $wins = 0;
        $losses = 0;
        $totalGold = 0;
        $totalPoints = 0;

        foreach ($battles as $b) {
            if ((int) $b['winner_user_id'] === $userId) {
                $wins++;
            } else {
                $losses++;
            }
            $totalGold += (int) $b['gold_earned'];
            $totalPoints += (int) $b['points_earned'];
        }

        return [
            'battles' => count($battles),
            'wins' => $wins,
            'losses' => $losses,
            'gold_earned' => $totalGold,
            'points_earned' => $totalPoints,
        ];
    }

    /**
     * Get recap for the last completed war.
     */
    public function getWarRecap(int $warId): array
    {
        return $this->select('user_id, winner_user_id, points_earned, gold_earned')
            ->where('war_id', $warId)
            ->findAll();
    }
}
