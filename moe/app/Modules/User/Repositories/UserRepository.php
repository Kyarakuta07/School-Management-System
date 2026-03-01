<?php

namespace App\Modules\User\Repositories;

use App\Modules\User\Models\UserModel;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use Config\Database;

/**
 * UserRepository — Centralized user data access.
 */
class UserRepository implements UserRepositoryInterface
{
    protected $db;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->userModel = new UserModel();
    }

    /**
     * Get user profile with sanctuary and class info.
     */
    public function getUserProfile(int $userId): ?array
    {
        return $this->db->table('nethera n')
            ->select('n.*, s.nama_sanctuary, g.nama_kelas')
            ->join('sanctuary s', 'n.sanctuary_id = s.id_sanctuary', 'left')
            ->join('grades g', 'n.class_id = g.id_kelas', 'left')
            ->where('n.id_nethera', $userId)
            ->get()
            ->getRowArray();
    }

    /**
     * Get user with active pet info.
     */
    public function getUserWithActivePet(int $userId): ?array
    {
        $user = $this->getUserProfile($userId);
        if (!$user)
            return null;

        $pet = $this->db->table('user_pets up')
            ->select('up.*, p.name as species_name, p.element, p.rarity, p.image_url, p.base_hp, p.base_attack, p.base_defense, p.base_speed')
            ->join('pets p', 'up.pet_id = p.id')
            ->where('up.user_id', $userId)
            ->where('up.is_active', 1)
            ->get()
            ->getRowArray();

        $user['active_pet'] = $pet;
        return $user;
    }

    /**
     * Get dashboard data for a user.
     */
    public function getDashboardData(int $userId): array
    {
        $user = $this->getUserWithActivePet($userId);

        $petCount = $this->db->table('user_pets')
            ->where('user_id', $userId)
            ->countAllResults();

        $battleWins = $this->db->table('pet_battles')
            ->where('winner_id', $userId)
            ->countAllResults();

        return [
            'user' => $user,
            'petCount' => $petCount,
            'battleWins' => $battleWins,
        ];
    }

    /**
     * Search users by name or username (excluding self).
     */
    public function searchUsersExcluding(string $query, int $excludeId, int $limit = 10): array
    {
        return $this->db->table('nethera')
            ->select('id_nethera, username, nama_lengkap, avatar, gold')
            ->groupStart()
            ->like('nama_lengkap', $query)
            ->orLike('username', $query)
            ->groupEnd()
            ->where('id_nethera !=', $excludeId)
            ->where('status', 'nethera')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
