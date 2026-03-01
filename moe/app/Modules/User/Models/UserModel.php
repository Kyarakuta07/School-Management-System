<?php

namespace App\Modules\User\Models;

use CodeIgniter\Model;

/**
 * User Model
 * Handles `nethera` table (users).
 */
class UserModel extends Model
{
    protected $table = 'nethera';
    protected $primaryKey = 'id_nethera';
    protected $returnType = 'array';
    protected $allowedFields = [
        'username',
        'nama_lengkap',
        'email',
        'password',
        'noHP',
        'tanggal_lahir',
        'periode_masuk',
        'id_sanctuary',
        'no_registrasi',
        // 'gold' — server-computed only, use addGold()/deductGold()
        'status_akun',
        // 'role' — server-computed only, use setRole()
        'otp_code',
        'otp_expires',
        'otp_attempts',
        'email_verified_at',
        'reset_token',
        'token_expires',
        'approved_at',
        'approved_by',
        'failed_attempts',
        'last_failed_login',
        'locked_until',
        'last_login',
        // 'arena_wins' — server-computed only, written via raw SQL in BaseArenaService
        // 'arena_losses' — server-computed only, written via raw SQL in BaseArenaService
        // 'current_win_streak' — server-computed only, written via raw SQL in BaseArenaService
        'fun_fact',
        'profile_photo',
    ];

    /**
     * Get user info with sanctuary data for dashboard.
     */
    public function getUserDashboardInfo(int $userId): ?array
    {
        return $this->db->table('nethera AS n')
            ->select('n.status_akun, n.profile_photo, n.fun_fact, n.gold,
                      s.nama_sanctuary, s.deskripsi,
                      LOWER(s.nama_sanctuary) AS faction_slug')
            ->join('sanctuary AS s', 's.id_sanctuary = n.id_sanctuary')
            ->where('n.id_nethera', $userId)
            ->get()->getRowArray();
    }

    /**
     * Get aggregate counts of users by status
     */
    public function getStatusCounts(): array
    {
        return $this->select('status_akun, COUNT(*) as total')
            ->where('role', ROLE_NETHERA)
            ->groupBy('status_akun')
            ->get()
            ->getResultArray();
    }

    /**
     * Get paginated Nethera users with sanctuary info
     */
    public function getNetheraWithSanctuary(int $perPage = 15)
    {
        return $this->select('nethera.*, sanctuary.nama_sanctuary')
            ->join('sanctuary', 'sanctuary.id_sanctuary = nethera.id_sanctuary', 'left')
            ->where('nethera.role', ROLE_NETHERA)
            ->orderBy('nethera.id_nethera', 'DESC')
            ->paginate($perPage);
    }

    /**
     * Search Nethera users with sanctuary info
     */
    public function searchWithSanctuary(string $query = '', string $status = 'all')
    {
        $builder = $this->select('nethera.*, sanctuary.nama_sanctuary')
            ->join('sanctuary', 'sanctuary.id_sanctuary = nethera.id_sanctuary', 'left')
            ->where('nethera.role', ROLE_NETHERA)
            ->orderBy('nethera.id_nethera', 'DESC');

        if (!empty($query)) {
            $builder->groupStart()
                ->like('nethera.nama_lengkap', $query)
                ->orLike('nethera.username', $query)
                ->orLike('sanctuary.nama_sanctuary', $query)
                ->orLike('nethera.no_registrasi', $query)
                ->groupEnd();
        }

        if (!empty($status) && $status !== 'all') {
            $builder->where('nethera.status_akun', $status);
        }

        return $builder->findAll();
    }

    /**
     * Search for active Nethera users by username or name
     */
    public function searchNethera(string $query, int $excludeUserId, int $limit = 10): array
    {
        return $this->db->table('nethera')
            ->select('id_nethera, username, nama_lengkap')
            ->like('username', $query)
            ->orLike('nama_lengkap', $query)
            ->where('status_akun', 'Aktif')
            ->where('role', ROLE_NETHERA)
            ->where('id_nethera !=', $excludeUserId)
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Find active user by username
     */
    public function findActiveByUsername(string $username): ?array
    {
        return $this->where('username', $username)
            ->where('status_akun', 'Aktif')
            ->first();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Update OTP for a user.
     * Stores a SHA-256 hash of the OTP code, never plaintext.
     * Resets the per-user attempt counter.
     */
    public function updateOTP(int $userId, string $hashedCode, int $expirySeconds = 300): bool
    {
        return $this->update($userId, [
            'otp_code' => $hashedCode,
            'otp_expires' => date('Y-m-d H:i:s', time() + $expirySeconds),
            'otp_attempts' => 0,
        ]);
    }

    /**
     * Verify OTP using hash comparison.
     * Enforces per-user max attempts (5) and sets email_verified_at on success.
     */
    public function verifyOTP(string $username, string $code): bool
    {
        $user = $this->where('username', $username)
            ->where('otp_expires >=', date('Y-m-d H:i:s'))
            ->first();

        if (!$user || empty($user['otp_code'])) {
            return false;
        }

        // Max 5 attempts per OTP
        if (($user['otp_attempts'] ?? 0) >= 5) {
            $this->update($user['id_nethera'], [
                'otp_code' => null,
                'otp_expires' => null,
                'otp_attempts' => 0,
            ]);
            return false; // Force resend
        }

        // Hash comparison
        if (!hash_equals($user['otp_code'], hash('sha256', $code))) {
            $this->builder()
                ->where('id_nethera', $user['id_nethera'])
                ->set('otp_attempts', 'otp_attempts + 1', false)
                ->update();
            return false;
        }

        // Success — clear OTP and mark email verified
        $this->update($user['id_nethera'], [
            'otp_code' => null,
            'otp_expires' => null,
            'otp_attempts' => 0,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /**
     * Increment failed attempts and lock if necessary.
     *
     * Escalating lockout: duration doubles each time (15m → 30m → 60m → 120m, max 24h).
     * Counter only resets on successful login (resetLockout).
     */
    public function incrementFailedAttempts(int $userId, int $threshold = 5): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        $newAttempts = ($user['failed_attempts'] ?? 0) + 1;
        $data = [
            'failed_attempts' => $newAttempts,
            'last_failed_login' => date('Y-m-d H:i:s'),
        ];

        if ($newAttempts >= $threshold) {
            // Escalating lockout: 15min * 2^(consecutive_locks - 1), max 24hr
            $consecutiveLocks = (int) floor($newAttempts / $threshold);
            $lockMinutes = min(15 * pow(2, $consecutiveLocks - 1), 1440); // cap 24h
            $data['locked_until'] = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
            // DO NOT reset failed_attempts — keep count for escalation
        }

        return $this->update($userId, $data);
    }

    /**
     * Reset lockout counters
     */
    public function resetLockout(int $userId): bool
    {
        return $this->update($userId, [
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if account is locked
     */
    public function isLocked(array $user): bool
    {
        if (empty($user['locked_until']))
            return false;
        return strtotime($user['locked_until']) > time();
    }

    /**
     * Get remaining lockout time in minutes
     */
    public function getLockoutMinutes(array $user): int
    {
        if (!$this->isLocked($user))
            return 0;
        return ceil((strtotime($user['locked_until']) - time()) / 60);
    }

    // ==================================================
    // SERVER-SIDE MUTATORS (bypass $allowedFields)
    // ==================================================

    /**
     * Set user role — server-side only, bypasses $allowedFields.
     */
    public function setRole(int $userId, string $role): bool
    {
        return $this->builder()
            ->where('id_nethera', $userId)
            ->update(['role' => $role]);
    }

    // ==================================================
    // GOLD MANAGEMENT
    // ==================================================

    /**
     * Get user's current gold
     */
    public function getGold(int $userId): int
    {
        $user = $this->find($userId);
        return (int) ($user['gold'] ?? 0);
    }

    /**
     * Add gold to user
     */
    public function addGold(int $userId, int $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $this->builder()
            ->where('id_nethera', $userId)
            ->set('gold', 'gold + ' . $amount, false)
            ->update();
    }

    /**
     * Deduct gold from user (ensures it doesn't drop below 0)
     */
    public function deductGold(int $userId, int $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        // Use atomic set with WHERE condition to prevent negative balances during concurrent requests
        $this->builder()
            ->where('id_nethera', $userId)
            ->where('gold >=', $amount)
            ->set('gold', 'gold - ' . $amount, false)
            ->update();

        // Return true only if a row was actually updated (meaning they had enough gold)
        return $this->db->affectedRows() > 0;
    }
}
