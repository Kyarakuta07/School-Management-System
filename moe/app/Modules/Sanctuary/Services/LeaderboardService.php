<?php

namespace App\Modules\Sanctuary\Services;

use Config\Database;

/**
 * LeaderboardService — Handles leaderboard data queries.
 */
class LeaderboardService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Get battle leaderboard (top players by wins).
     */
    public function getBattleLeaderboard(int $limit = 50): array
    {
        return $this->db->query("
            SELECT n.id_nethera, n.username, n.nama_lengkap, n.avatar, n.sanctuary_id,
                   s.nama_sanctuary,
                   COUNT(pb.id) as total_wins
            FROM nethera n
            LEFT JOIN pet_battles pb ON pb.winner_id = n.id_nethera
            LEFT JOIN sanctuary s ON n.sanctuary_id = s.id_sanctuary
            WHERE n.status = 'nethera'
            GROUP BY n.id_nethera
            ORDER BY total_wins DESC
            LIMIT {$limit}
        ")->getResultArray();
    }

    /**
     * Get sanctuary war leaderboard.
     */
    public function getWarLeaderboard(int $limit = 20): array
    {
        return $this->db->query("
            SELECT s.id_sanctuary, s.nama_sanctuary, s.image,
                   COUNT(CASE WHEN sw.winner_sanctuary_id = s.id_sanctuary THEN 1 END) as war_wins
            FROM sanctuary s
            LEFT JOIN sanctuary_wars sw ON sw.attacker_sanctuary_id = s.id_sanctuary
                OR sw.defender_sanctuary_id = s.id_sanctuary
            GROUP BY s.id_sanctuary
            ORDER BY war_wins DESC
            LIMIT {$limit}
        ")->getResultArray();
    }

    /**
     * Get Hall of Fame data.
     */
    public function getHallOfFame(): array
    {
        return $this->db->query("
            SELECT n.id_nethera, n.username, n.nama_lengkap, n.avatar, n.gold,
                   s.nama_sanctuary,
                   (SELECT COUNT(*) FROM pet_battles pb WHERE pb.winner_id = n.id_nethera) as battle_wins,
                   (SELECT COUNT(*) FROM user_pets up WHERE up.user_id = n.id_nethera) as pet_count
            FROM nethera n
            LEFT JOIN sanctuary s ON n.sanctuary_id = s.id_sanctuary
            WHERE n.status = 'nethera'
            ORDER BY battle_wins DESC
            LIMIT 10
        ")->getResultArray();
    }

    /**
     * Archive current season leaderboard.
     */
    public function archiveSeason(string $seasonName): bool
    {
        $leaderboard = $this->getBattleLeaderboard(100);

        foreach ($leaderboard as $rank => $entry) {
            $this->db->table('leaderboard_archives')->insert([
                'season_name' => $seasonName,
                'user_id' => $entry['id_nethera'],
                'rank' => $rank + 1,
                'total_wins' => $entry['total_wins'],
                'archived_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return true;
    }

    /**
     * Get pet rankings for the leaderboard page (sorted by rank/wins/level).
     */
    public function getPetRankings(string $sort = 'rank', string $element = '', int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('user_pets AS up')
            ->select('up.id, up.nickname, up.level, up.exp, up.rank_points, up.total_wins, up.total_wins AS wins, up.total_losses AS losses, up.evolution_stage, up.is_shiny,
                      ps.name AS species_name, ps.element, ps.rarity, ps.img_egg, ps.img_baby, ps.img_adult,
                      n.username')
            ->join('pet_species AS ps', 'ps.id = up.species_id')
            ->join('nethera AS n', 'n.id_nethera = up.user_id')
            ->where('up.status', 'ALIVE');

        if ($element && $element !== 'all') {
            $builder->where('ps.element', $element);
        }

        switch ($sort) {
            case 'wins':
                $builder->orderBy('up.total_wins', 'DESC')->orderBy('up.rank_points', 'DESC');
                break;
            case 'level':
                $builder->orderBy('up.level', 'DESC')->orderBy('up.exp', 'DESC');
                break;
            case 'rank':
            default:
                $builder->orderBy('up.rank_points', 'DESC');
                break;
        }

        return $builder->limit($limit, $offset)->get()->getResultArray();
    }

    /**
     * Count total pets matching filter criteria.
     */
    public function countPetRankings(string $element = ''): int
    {
        $builder = $this->db->table('user_pets AS up')
            ->join('pet_species AS ps', 'ps.id = up.species_id')
            ->where('up.status', 'ALIVE');

        if ($element && $element !== 'all') {
            $builder->where('ps.element', $element);
        }

        return $builder->countAllResults();
    }

    /**
     * Get war contributors leaderboard.
     */
    public function getWarContributors(int $warId = 0, int $limit = 20): array
    {
        $builder = $this->db->table('sanctuary_war_battles AS swb')
            ->select('n.username, n.nama_lengkap, s.nama_sanctuary,
                      SUM(swb.points) AS total_points,
                      COUNT(CASE WHEN swb.winner_id = swb.user_id THEN 1 END) AS wins,
                      COUNT(*) AS total_battles')
            ->join('nethera AS n', 'n.id_nethera = swb.user_id')
            ->join('sanctuary AS s', 's.id_sanctuary = n.id_sanctuary', 'left');

        if ($warId > 0) {
            $builder->where('swb.war_id', $warId);
        }

        return $builder
            ->groupBy('swb.user_id')
            ->orderBy('total_points', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Get available pet elements for filter.
     */
    public function getAvailableElements(): array
    {
        return $this->db->table('pet_species')
            ->select('DISTINCT(element) AS element')
            ->orderBy('element')
            ->get()->getResultArray();
    }
}
