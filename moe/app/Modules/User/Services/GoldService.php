<?php

namespace App\Modules\User\Services;

use CodeIgniter\Database\BaseConnection;
use App\Modules\User\Models\UserModel;
use App\Modules\Trapeza\Models\TransactionModel;

use App\Modules\User\Interfaces\GoldServiceInterface;

/**
 * GoldService
 * 
 * Handles atomic gold mutations and ensures ledger consistency.
 * Every gold change must be accompanied by a transaction log.
 * 
 * Two variants exist for each operation:
 *   - addGold/subtractGold/transferGold: Own their own DB transaction (standalone use)
 *   - addGoldRaw/subtractGoldRaw: No own TX — caller must wrap in transaction (composable use)
 */
class GoldService implements GoldServiceInterface
{
    protected BaseConnection $db;
    protected UserModel $userModel;
    protected TransactionModel $transactionModel;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
        $this->userModel = new UserModel();
        $this->transactionModel = new TransactionModel();
    }

    // ==================================================
    // QUERY METHODS
    // ==================================================

    /**
     * Get gold balance for a user.
     */
    public function getBalance(int $userId): int
    {
        $user = $this->userModel->find($userId);
        return (int) ($user['gold'] ?? 0);
    }

    // ==================================================
    // RAW METHODS (no own TX — caller must wrap)
    // ==================================================

    /**
     * Add gold to a user and log transaction — WITHOUT starting a transaction.
     * Caller MUST wrap this in their own transBegin/transCommit.
     * 
     * @param int $userId Target user ID
     * @param int $amount Amount to add
     * @param string $type Transaction type
     * @param string $description Human-readable description
     * @return bool Success status
     */
    public function addGoldRaw(int $userId, int $amount, string $type, string $description): bool
    {
        if ($amount <= 0)
            return true;

        $this->userModel->addGold($userId, $amount);
        $this->transactionModel->logTransaction(null, $userId, $amount, $type, $description);

        return true;
    }

    /**
     * Subtract gold from a user with FOR UPDATE lock — WITHOUT starting a transaction.
     * Caller MUST wrap this in their own transBegin/transCommit.
     * 
     * @param int $userId Target user ID
     * @param int $amount Amount to subtract
     * @param string $type Transaction type
     * @param string $description Human-readable description
     * @return bool Success (false if insufficient gold)
     */
    public function subtractGoldRaw(int $userId, int $amount, string $type, string $description): bool
    {
        if ($amount <= 0)
            return true;

        // Lock the row and check balance atomically
        $user = $this->db->query(
            "SELECT gold FROM nethera WHERE id_nethera = ? FOR UPDATE",
            [$userId]
        )->getRowArray();

        if (!$user || (int) $user['gold'] < $amount) {
            return false;
        }

        $this->userModel->deductGold($userId, $amount);
        $this->transactionModel->logTransaction($userId, null, $amount, $type, $description);

        return true;
    }

    // ==================================================
    // STANDALONE METHODS (own TX — for direct/standalone use)
    // ==================================================

    /**
     * Add gold to a user and log transaction (standalone with own TX)
     */
    public function addGold(int $userId, int $amount, string $type, string $description): bool
    {
        if ($amount <= 0)
            return true;

        $this->db->transBegin();

        try {
            $this->addGoldRaw($userId, $amount, $type, $description);
            $this->db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', "[GoldService] addGold failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Subtract gold from a user and log transaction (standalone with own TX)
     */
    public function subtractGold(int $userId, int $amount, string $type, string $description): bool
    {
        if ($amount <= 0)
            return true;

        $this->db->transBegin();

        try {
            $success = $this->subtractGoldRaw($userId, $amount, $type, $description);
            if (!$success) {
                $this->db->transRollback();
                return false;
            }
            $this->db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', "[GoldService] subtractGold failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transfer gold between users (standalone with own TX + FOR UPDATE lock)
     */
    public function transferGold(int $senderId, int $receiverId, int $amount, string $description = 'User transfer'): bool
    {
        if ($amount <= 0)
            return false;

        $this->db->transBegin();

        try {
            // Lock sender row and check balance atomically
            $sender = $this->db->query(
                "SELECT gold FROM nethera WHERE id_nethera = ? FOR UPDATE",
                [$senderId]
            )->getRowArray();

            if (!$sender || (int) $sender['gold'] < $amount) {
                $this->db->transRollback();
                return false;
            }

            $this->userModel->deductGold($senderId, $amount);
            $this->userModel->addGold($receiverId, $amount);
            $this->transactionModel->logTransaction($senderId, $receiverId, $amount, 'transfer', $description);

            $this->db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', "[GoldService] transferGold failed: " . $e->getMessage());
            return false;
        }
    }
}
