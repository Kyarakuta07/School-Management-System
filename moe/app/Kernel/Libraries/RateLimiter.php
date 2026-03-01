<?php

namespace App\Kernel\Libraries;

use Config\Database;

/**
 * Rate Limiter
 *
 * Prevents brute-force attacks by tracking request attempts in the database.
 * Ported from legacy moe/core/rate_limiter.php.
 */
class RateLimiter
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->ensureTableExists();
    }

    /**
     * Check if an action is allowed under its rate limit.
     *
     * @return array{allowed: bool, attempts: int, remaining: int, locked_until: string|null}
     */
    public function checkLimit(
        string $identifier,
        string $action,
        int $maxAttempts,
        int $windowMinutes
    ): array {
        // Clean expired entries first
        $this->db->table('rate_limits')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->delete();

        // Count attempts within window
        $since = date('Y-m-d H:i:s', strtotime("-{$windowMinutes} minutes"));
        $attempts = (int) $this->db->table('rate_limits')
            ->where('identifier', $identifier)
            ->where('action', $action)
            ->where('created_at >=', $since)
            ->countAllResults(false);

        $remaining = max(0, $maxAttempts - $attempts);
        $allowed = $attempts < $maxAttempts;

        // Record this attempt
        $this->db->table('rate_limits')->insert([
            'identifier' => $identifier,
            'action' => $action,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$windowMinutes} minutes")),
        ]);

        $lockedUntil = null;
        if (!$allowed) {
            // Find earliest expiry to tell caller when they can retry
            $earliest = $this->db->table('rate_limits')
                ->selectMin('expires_at')
                ->where('identifier', $identifier)
                ->where('action', $action)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->get()
                ->getRowArray();

            $lockedUntil = $earliest['expires_at'] ?? null;
        }

        return [
            'allowed' => $allowed,
            'attempts' => $attempts + 1,
            'remaining' => max(0, $remaining - 1),
            'locked_until' => $lockedUntil,
        ];
    }

    /**
     * Reset rate limit for a specific identifier / action pair.
     */
    public function resetLimit(string $identifier, string $action): void
    {
        $this->db->table('rate_limits')
            ->where('identifier', $identifier)
            ->where('action', $action)
            ->delete();
    }

    /**
     * Get current attempt count.
     */
    public function getAttempts(string $identifier, string $action): int
    {
        return (int) $this->db->table('rate_limits')
            ->where('identifier', $identifier)
            ->where('action', $action)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->countAllResults();
    }

    /**
     * Check if a daily quota is exhausted based on a fixed reset hour.
     * 
     * @param string $identifier User ID or IP
     * @param string $action Action name (e.g. 'arena_battle')
     * @param int $maxAttempts Max battles per day
     * @param int $resetHour Hour of day to reset (0-23)
     * @param string $timezone Timezone for reset (e.g. 'Asia/Jakarta')
     * @return array{allowed: bool, attempts: int, remaining: int, resets_at: string}
     */
    public function checkDailyLimit(
        string $identifier,
        string $action,
        int $maxAttempts,
        int $resetHour = 16,
        string $timezone = 'Asia/Jakarta'
    ): array {
        $tz = new \DateTimeZone($timezone);
        $now = new \DateTime('now', $tz);

        // Calculate the START of the current quota period
        // If it's currently AFTER 16:00, the period started today at 16:00.
        // If it's currently BEFORE 16:00, the period started yesterday at 16:00.
        $startOfPeriod = clone $now;
        $startOfPeriod->setTime($resetHour, 0, 0);

        if ($now < $startOfPeriod) {
            $startOfPeriod->modify('-1 day');
        }

        $endOfPeriod = clone $startOfPeriod;
        $endOfPeriod->modify('+1 day');

        $since = $startOfPeriod->format('Y-m-d H:i:s');

        // Count attempts since the start of this period
        $attempts = (int) $this->db->table('rate_limits')
            ->where('identifier', $identifier)
            ->where('action', $action)
            ->where('created_at >=', $since)
            ->countAllResults(false);

        $allowed = $attempts < $maxAttempts;

        if ($allowed) {
            // Record attempt
            $this->db->table('rate_limits')->insert([
                'identifier' => $identifier,
                'action' => $action,
                'created_at' => $now->format('Y-m-d H:i:s'),
                'expires_at' => $endOfPeriod->format('Y-m-d H:i:s'),
            ]);
            $attempts++;
        }

        return [
            'allowed' => $allowed,
            'attempts' => $attempts,
            'remaining' => max(0, $maxAttempts - $attempts),
            'resets_at' => $endOfPeriod->format('Y-m-d\TH:i:s'),
        ];
    }

    /**
     * Get the current status of a daily quota without recording an attempt.
     */
    public function getDailyStatus(
        string $identifier,
        string $action,
        int $maxAttempts,
        int $resetHour = 16,
        string $timezone = 'Asia/Jakarta'
    ): array {
        $tz = new \DateTimeZone($timezone);
        $now = new \DateTime('now', $tz);

        $startOfPeriod = clone $now;
        $startOfPeriod->setTime($resetHour, 0, 0);

        if ($now < $startOfPeriod) {
            $startOfPeriod->modify('-1 day');
        }

        $endOfPeriod = clone $startOfPeriod;
        $endOfPeriod->modify('+1 day');

        $since = $startOfPeriod->format('Y-m-d H:i:s');

        $attempts = (int) $this->db->table('rate_limits')
            ->where('identifier', $identifier)
            ->where('action', $action)
            ->where('created_at >=', $since)
            ->countAllResults(false);

        return [
            'attempts' => $attempts,
            'remaining' => max(0, $maxAttempts - $attempts),
            'limit' => $maxAttempts,
            'resets_at' => $endOfPeriod->format('Y-m-d\TH:i:s'),
        ];
    }

    /**
     * Auto-create the rate_limits table if it does not exist.
     */
    private function ensureTableExists(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS rate_limits (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action     VARCHAR(50)  NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            INDEX idx_identifier (identifier),
            INDEX idx_action     (action),
            INDEX idx_expires    (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
