<?php

namespace App\Kernel\Traits;

/**
 * IdempotencyTrait
 * 
 * Provides request deduplication for mutating API endpoints.
 * Uses a DB-backed `idempotency_keys` table with a UNIQUE(user_id, action)
 * constraint to prevent concurrent/duplicate requests.
 * 
 * Usage in Controller:
 *   use IdempotencyTrait;
 *   if (!$this->acquireIdempotencyLock('gacha_roll', $this->userId)) {
 *       return $this->error('Request already in progress', 429, 'DUPLICATE_REQUEST');
 *   }
 */
trait IdempotencyTrait
{
    /**
     * Attempt to acquire a short-lived idempotency lock.
     * 
     * Uses INSERT IGNORE with a UNIQUE KEY to atomically prevent duplicate
     * requests. Old keys are cleaned up automatically.
     * 
     * @param string $action  Action identifier (e.g. 'gacha_roll', 'daily_claim')
     * @param int    $userId  User performing the action
     * @param int    $cooldownSeconds  Minimum seconds between identical requests (default: 3)
     * @return bool  True if lock acquired (proceed), false if duplicate (reject)
     */
    protected function acquireIdempotencyLock(string $action, int $userId, int $cooldownSeconds = 3): bool
    {
        $db = \Config\Database::connect();

        // 1. Cleanup expired keys for this user+action (older than cooldown)
        $db->query(
            "DELETE FROM idempotency_keys WHERE user_id = ? AND action = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$userId, $action, $cooldownSeconds]
        );

        // 2. Try to insert — UNIQUE KEY (user_id, action) prevents duplicates
        $db->query(
            "INSERT IGNORE INTO idempotency_keys (user_id, action, created_at) VALUES (?, ?, NOW())",
            [$userId, $action]
        );

        // 3. If affected rows = 1, we got the lock. If 0, duplicate exists.
        $acquired = $db->affectedRows() > 0;

        return $acquired;
    }

    /**
     * Release an idempotency lock after the request completes.
     * This is optional — locks auto-expire based on cooldown.
     * Call this only if you want immediate re-use (e.g., after a successful request).
     */
    protected function releaseIdempotencyLock(string $action, int $userId): void
    {
        $db = \Config\Database::connect();
        $db->query(
            "DELETE FROM idempotency_keys WHERE user_id = ? AND action = ?",
            [$userId, $action]
        );
    }
}
