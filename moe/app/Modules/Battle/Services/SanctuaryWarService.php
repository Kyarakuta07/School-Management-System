<?php

namespace App\Modules\Battle\Services;

use App\Modules\Battle\Models\WarModel;
use App\Modules\Battle\Models\WarBattleModel;
use App\Config\GameConfig;

/**
 * SanctuaryWarService — Business logic for Sanctuary War.
 * Extracted from SanctuaryWarController to separate concerns.
 */
class SanctuaryWarService
{
    protected WarModel $warModel;
    protected WarBattleModel $battleModel;
    protected $db;

    public function __construct()
    {
        $this->warModel = new WarModel();
        $this->battleModel = new WarBattleModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get the next Saturday war date.
     */
    public function getNextWarDate(): string
    {
        $now = new \DateTime();
        $dayOfWeek = (int) $now->format('N');
        $daysUntilSat = (6 - $dayOfWeek + 7) % 7;
        if ($daysUntilSat === 0)
            $daysUntilSat = 7;
        return $now->modify("+{$daysUntilSat} days")->format('Y-m-d') . ' 14:00:00';
    }

    /**
     * Get user's sanctuary data.
     */
    public function getUserSanctuary(int $userId): ?array
    {
        return $this->db->table('nethera AS n')
            ->select('s.*')
            ->join('sanctuary AS s', 's.id_sanctuary = n.id_sanctuary')
            ->where('n.id_nethera', $userId)
            ->get()->getRowArray();
    }

    /**
     * Get user's contribution in a war.
     */
    public function getUserContribution(int $warId, int $userId): array
    {
        return $this->battleModel->getUserContribution($warId, $userId);
    }

    /**
     * Get recap of the last completed war.
     */
    public function getLastWarRecap(): ?array
    {
        $war = $this->warModel->where('status', 'completed')
            ->orderBy('war_date', 'DESC')
            ->first();

        if (!$war)
            return null;

        $scores = $this->warModel->getWarScores($war['id']);
        $champion = !empty($scores) ? $scores[0] : null;

        return [
            'date' => $war['war_date'],
            'champion' => $champion,
            'standings' => array_slice($scores, 0, 5),
        ];
    }

    /**
     * Get tickets used by a user in a war.
     */
    public function getTicketsUsed(int $warId, int $userId): int
    {
        return $this->battleModel->getTicketsUsed($warId, $userId);
    }

    /**
     * Find a random opponent from a different sanctuary.
     * Uses count-offset approach instead of ORDER BY RAND() for scalability.
     */
    public function findOpponent(int $excludeSanctuaryId): ?array
    {
        $builder = $this->db->table('user_pets AS up')
            ->select('up.*, ps.name AS species_name, ps.element, ps.base_attack AS base_atk, ps.base_defense AS base_def, ps.base_speed AS base_spd, n.id_nethera AS user_id, n.id_sanctuary AS opponent_sanctuary_id')
            ->join('pet_species AS ps', 'ps.id = up.species_id')
            ->join('nethera AS n', 'n.id_nethera = up.user_id')
            ->where('up.is_active', 1)
            ->where('up.status', 'ALIVE')
            ->where('n.id_sanctuary !=', $excludeSanctuaryId);

        // Count-offset approach: avoids ORDER BY RAND()
        $count = (clone $builder)->countAllResults(false);
        if ($count === 0)
            return null;

        $offset = rand(0, max(0, $count - 1));
        return $builder->limit(1, $offset)->get()->getRowArray();
    }

    /**
     * Simulate a battle between two pets.
     */
    public function simulateBattle(array $userPet, array $oppPet): array
    {
        $userPower = ((int) $userPet['level'] * 10) + (int) ($userPet['base_attack'] ?? 50);
        $oppPower = ((int) $oppPet['level'] * 10) + (int) ($oppPet['base_attack'] ?? 50);

        $userRoll = $userPower + rand(0, 50);
        $oppRoll = $oppPower + rand(0, 50);

        return [
            'won' => $userRoll > $oppRoll,
            'tied' => $userRoll === $oppRoll,
            'user_power' => $userRoll,
            'opp_power' => $oppRoll,
        ];
    }

    /**
     * Record a battle result.
     */
    public function recordBattle(
        int $warId,
        int $userId,
        int $userPetId,
        int $oppPetId,
        int $userSanctuaryId,
        int $oppSanctuaryId,
        int $winnerUserId,
        int $points,
        int $gold
    ): void {
        $this->battleModel->recordBattle([
            'war_id' => $warId,
            'user_id' => $userId,
            'opponent_id' => $winnerUserId !== $userId ? $winnerUserId : 0,
            'user_sanctuary_id' => $userSanctuaryId,
            'opponent_sanctuary_id' => $oppSanctuaryId,
            'user_pet_id' => $userPetId,
            'opponent_pet_id' => $oppPetId,
            'winner_user_id' => $winnerUserId,
            'points_earned' => $points,
            'gold_earned' => $gold,
        ]);
    }
}
