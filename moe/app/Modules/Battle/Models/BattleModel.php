<?php

namespace App\Modules\Battle\Models;

use CodeIgniter\Model;

class BattleModel extends Model
{
    protected $table = 'pet_battles';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'attacker_pet_id',
        'defender_pet_id',
        'winner_pet_id',
        'battle_log',
        'reward_gold',
        'reward_exp',
        'mode',
        'is_read_by_attacker',
        'is_read_by_defender',
        'created_at'
    ];

    protected $useTimestamps = false; // Using custom created_at timestamp

    /**
     * Get battle history for a specific pet
     */
    public function getPetHistory(int $petId, int $limit = 10)
    {
        return $this->where('attacker_pet_id', $petId)
            ->orWhere('defender_pet_id', $petId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get recent battles across the system
     */
    public function getRecentBattles(int $limit = 20)
    {
        return $this->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get combined battle history (Attacker + Defender roles)
     */
    public function getCombinedHistory(int $userId, int $offset = 0, int $limit = 30)
    {
        // Attacker perspective
        $attackerSql = "SELECT pb.created_at, pb.id, 'attacker' AS battle_role,
           COALESCE(up.nickname, ps.name) AS my_pet_name, up.level AS my_pet_level, ps.element AS my_pet_element,
           COALESCE(def_up.nickname, def_ps.name, 'Wild Pet') AS opp_pet_name,
           COALESCE(def_up.level, 10) AS opp_pet_level, COALESCE(def_ps.element, 'Dark') AS opp_pet_element,
           COALESCE(def_u.username, 'Wild Trainer') AS opp_username,
           (pb.winner_pet_id = pb.attacker_pet_id) AS won
        FROM pet_battles pb
        JOIN user_pets up ON pb.attacker_pet_id = up.id
        JOIN pet_species ps ON up.species_id = ps.id
        LEFT JOIN user_pets def_up ON pb.defender_pet_id = def_up.id AND pb.defender_pet_id > 0
        LEFT JOIN pet_species def_ps ON def_up.species_id = def_ps.id
        LEFT JOIN nethera def_u ON def_up.user_id = def_u.id_nethera
        WHERE up.user_id = $userId";

        // Defender perspective
        $defenderSql = "SELECT pb.created_at, pb.id, 'defender' AS battle_role,
           COALESCE(def_up.nickname, def_ps.name) AS my_pet_name, def_up.level AS my_pet_level, def_ps.element AS my_pet_element,
           COALESCE(atk_up.nickname, atk_ps.name) AS opp_pet_name,
           atk_up.level AS opp_pet_level, atk_ps.element AS opp_pet_element,
           COALESCE(atk_u.username, 'Unknown') AS opp_username,
           (pb.winner_pet_id = pb.defender_pet_id) AS won
        FROM pet_battles pb
        JOIN user_pets def_up ON pb.defender_pet_id = def_up.id
        JOIN pet_species def_ps ON def_up.species_id = def_ps.id
        JOIN user_pets atk_up ON pb.attacker_pet_id = atk_up.id
        JOIN pet_species atk_ps ON atk_up.species_id = atk_ps.id
        JOIN nethera atk_u ON atk_up.user_id = atk_u.id_nethera
        WHERE def_up.user_id = $userId AND atk_up.user_id != $userId";

        return $this->db->query(
            "SELECT * FROM ({$attackerSql} UNION ALL {$defenderSql}) AS combined 
             ORDER BY created_at DESC LIMIT ?, ?",
            [$offset, $limit]
        )->getResultArray();
    }

    /**
     * Count total wins for a user
     */
    public function countUserWins(int $userId): int
    {
        return $this->db->table($this->table . ' AS pb')
            ->join('user_pets AS up', 'up.id = pb.attacker_pet_id')
            ->where('up.user_id', $userId)
            ->where('pb.winner_pet_id = pb.attacker_pet_id')
            ->countAllResults();
    }

    /**
     * Count total battles for a user
     */
    public function countUserTotalBattles(int $userId): int
    {
        return $this->db->table($this->table . ' AS pb')
            ->join('user_pets AS up', 'up.id = pb.attacker_pet_id')
            ->where('up.user_id', $userId)
            ->countAllResults();
    }
}
