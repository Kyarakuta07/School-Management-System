<?php

namespace App\Modules\Sanctuary\Models;

use CodeIgniter\Model;

class LeaderboardModel extends Model
{
    protected $table = 'leaderboard_history';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'month_year',
        'pet_id',
        'sort_type',
        'created_at'
        // 'rank' — server-computed only, written via archiveCurrentMonth()
        // 'score' — server-computed only, written via archiveCurrentMonth()
    ];
    protected $useTimestamps = false;

    /**
     * Invalidate all leaderboard cache entries (F15 fix).
     * Call this after any battle result that changes rankings.
     */
    public static function invalidateCache(): void
    {
        $cache = \Config\Services::cache();
        // Clear all known leaderboard cache patterns
        foreach (['rank', 'wins', 'level'] as $sort) {
            foreach (['', 'all', 'Fire', 'Water', 'Earth', 'Air', 'Light', 'Dark'] as $elem) {
                foreach ([20, 50, 100] as $limit) {
                    $cache->delete("lb_pet_{$sort}_{$elem}_{$limit}_0");
                }
            }
        }
        $cache->delete('lb_elements');
    }

    /**
     * Archive the top 3 pets for each category for the current month.
     */
    public function archiveCurrentMonth(): bool
    {
        $monthYear = date('Y-m');

        // Check if already archived
        if ($this->where('month_year', $monthYear)->countAllResults() > 0) {
            return false;
        }

        $categories = ['level', 'wins', 'power'];
        $db = $this->db;

        foreach ($categories as $cat) {
            $builder = $db->table('user_pets AS up')
                ->select('up.id as pet_id, up.level, up.total_wins, (ps.base_attack + ps.base_defense + up.level * 3) as power_score')
                ->join('pet_species AS ps', 'ps.id = up.species_id')
                ->where('up.status', 'ALIVE');

            if ($cat === 'level') {
                $builder->orderBy('up.level', 'DESC')->orderBy('up.exp', 'DESC');
            } elseif ($cat === 'wins') {
                $builder->orderBy('up.total_wins', 'DESC');
            } else {
                $builder->orderBy('power_score', 'DESC');
            }

            $topPets = $builder->limit(3)->get()->getResultArray();

            foreach ($topPets as $index => $pet) {
                switch ($cat) {
                    case 'level':
                        $score = $pet['level'];
                        break;
                    case 'wins':
                        $score = $pet['total_wins'];
                        break;
                    case 'power':
                        $score = $pet['power_score'];
                        break;
                    default:
                        $score = 0;
                        break;
                }

                // Use $db->table()->insert() to bypass $allowedFields for computed rank/score
                $this->db->table($this->table)->insert([
                    'month_year' => $monthYear,
                    'rank' => $index + 1,
                    'pet_id' => $pet['pet_id'],
                    'sort_type' => $cat,
                    'score' => (int) $score,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return true;
    }

    /**
     * Get winners for a specific month (Cached for 1 hour)
     */
    public function getWinners(string $monthYear = null)
    {
        if (!$monthYear) {
            $monthYear = date('Y-m', strtotime('last month'));
        }

        $cache = \Config\Services::cache();
        $cacheKey = 'leaderboard_winners_' . $monthYear;

        if ($cached = $cache->get($cacheKey)) {
            return $cached;
        }

        $result = $this->select('leaderboard_history.*, up.nickname, ps.name as species_name, ps.img_adult, ps.img_baby, ps.img_egg, up.evolution_stage, n.username as owner_name')
            ->join('user_pets up', 'up.id = leaderboard_history.pet_id')
            ->join('pet_species ps', 'ps.id = up.species_id')
            ->join('nethera n', 'n.id_nethera = up.user_id')
            ->where('month_year', $monthYear)
            ->where('rank', 1)
            ->orderBy('sort_type', 'ASC')
            ->findAll();

        $cache->save($cacheKey, $result, 3600);

        return $result;
    }
}
