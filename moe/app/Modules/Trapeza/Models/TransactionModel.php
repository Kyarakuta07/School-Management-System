<?php

namespace App\Modules\Trapeza\Models;

use CodeIgniter\Model;
use App\Modules\Trapeza\Interfaces\TransactionServiceInterface;

/**
 * Transaction Model
 * Handles `trapeza_transactions` table.
 */
class TransactionModel extends Model implements TransactionServiceInterface
{
    protected $table = 'trapeza_transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $allowedFields = ['sender_id', 'receiver_id', 'amount', 'transaction_type', 'description', 'status'];

    /**
     * Get transaction history for a user
     */
    public function getUserTransactions(int $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->db->table('trapeza_transactions AS tt')
            ->select('tt.*, sn.username AS sender_username, sn.nama_lengkap AS sender_name, rn.username AS receiver_username, rn.nama_lengkap AS receiver_name')
            ->join('nethera AS sn', 'sn.id_nethera = tt.sender_id', 'left')
            ->join('nethera AS rn', 'rn.id_nethera = tt.receiver_id', 'left')
            ->groupStart()
            ->where('tt.sender_id', $userId)
            ->orWhere('tt.receiver_id', $userId)
            ->groupEnd()
            ->orderBy('tt.created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Count today's transfers by a user
     */
    public function countTodayTransfers(int $userId): int
    {
        $row = $this->db->table('trapeza_transactions')
            ->selectCount('id', 'count')
            ->where('sender_id', $userId)
            ->where('transaction_type', 'transfer')
            ->where('DATE(created_at)', date('Y-m-d'))
            ->get()
            ->getRowArray();

        return (int) ($row['count'] ?? 0);
    }

    /**
     * Log a gold transaction
     */
    public function logTransaction(?int $senderId, ?int $receiverId, int $amount, string $type, string $description): bool
    {
        return $this->insert([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'amount' => $amount,
            'transaction_type' => $type,
            'description' => $description,
            'status' => 'completed'
        ]) !== false;
    }
}
