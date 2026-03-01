<?php

namespace App\Kernel\Libraries;

use Config\Database;

/**
 * Account Lockout
 *
 * Implements account lockout after multiple failed login attempts.
 * Ported from legacy moe/core/account_lockout.php.
 */
class AccountLockout
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Check if an account is currently locked.
     *
     * @return array{locked: bool, locked_until: string|null, attempts: int}
     */
    public function check(string $username): array
    {
        $row = $this->db->table('nethera')
            ->select('failed_attempts, locked_until')
            ->where('username', $username)
            ->get()
            ->getRowArray();

        if (!$row) {
            return ['locked' => false, 'locked_until' => null, 'attempts' => 0];
        }

        $attempts = (int) $row['failed_attempts'];
        $lockedUntil = $row['locked_until'];

        if ($lockedUntil && strtotime($lockedUntil) > time()) {
            return ['locked' => true, 'locked_until' => $lockedUntil, 'attempts' => $attempts];
        }

        // Expired lock — auto-reset
        if ($lockedUntil && strtotime($lockedUntil) <= time()) {
            $this->resetAttempts($username);
            return ['locked' => false, 'locked_until' => null, 'attempts' => 0];
        }

        return ['locked' => false, 'locked_until' => null, 'attempts' => $attempts];
    }

    /**
     * Increment failed login attempts; lock if threshold reached.
     *
     * @return array{locked: bool, attempts: int, locked_until: string|null}
     */
    public function incrementFailed(string $username, int $maxAttempts = 10, int $lockoutMinutes = 30): array
    {
        // Verify user exists (prevent username enumeration)
        $exists = $this->db->table('nethera')
            ->where('username', $username)
            ->countAllResults();

        if (!$exists) {
            return ['locked' => false, 'attempts' => 0, 'locked_until' => null];
        }

        // Increment
        $this->db->table('nethera')
            ->where('username', $username)
            ->set('failed_attempts', 'failed_attempts + 1', false)
            ->set('last_failed_login', date('Y-m-d H:i:s'))
            ->update();

        // Read updated count
        $attempts = (int) ($this->db->table('nethera')
            ->select('failed_attempts')
            ->where('username', $username)
            ->get()
            ->getRowArray()['failed_attempts'] ?? 0);

        $lockedUntil = null;
        $isLocked = false;

        if ($attempts >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"));
            $this->db->table('nethera')
                ->where('username', $username)
                ->update(['locked_until' => $lockedUntil]);
            $isLocked = true;
        }

        return ['locked' => $isLocked, 'attempts' => $attempts, 'locked_until' => $lockedUntil];
    }

    /**
     * Reset failed login attempts (call after successful login).
     */
    public function resetAttempts(string $username): bool
    {
        return $this->db->table('nethera')
            ->where('username', $username)
            ->update([
                'failed_attempts' => 0,
                'locked_until' => null,
                'last_failed_login' => null,
            ]);
    }

    /**
     * Manually unlock an account (admin function).
     */
    public function unlock(int $userId): bool
    {
        return $this->db->table('nethera')
            ->where('id_nethera', $userId)
            ->update([
                'failed_attempts' => 0,
                'locked_until' => null,
            ]);
    }

    /**
     * Get all currently locked accounts.
     */
    public function getLockedAccounts(): array
    {
        return $this->db->table('nethera')
            ->select('id_nethera, username, nama_lengkap, failed_attempts, locked_until, last_failed_login')
            ->where('locked_until IS NOT NULL')
            ->where('locked_until >', date('Y-m-d H:i:s'))
            ->orderBy('locked_until', 'DESC')
            ->get()
            ->getResultArray();
    }
}
