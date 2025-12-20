<?php
/**
 * MOE Pet System - Trapeza (Bank) Controller
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles banking/gold-related endpoints:
 * - get_balance: Get user's gold balance
 * - get_transactions: Get transaction history
 * - transfer_gold: Transfer gold to another user
 * - search_nethera: Search for transfer recipients
 */

require_once __DIR__ . '/../BaseController.php';

class TrapezaController extends BaseController
{
    const DAILY_TRANSFER_LIMIT = 5;

    /**
     * GET: Get user's gold balance
     */
    public function getBalance()
    {
        $this->requireGet();

        $this->success([
            'balance' => $this->getUserGold()
        ]);
    }

    /**
     * GET: Get transaction history
     */
    public function getTransactions()
    {
        $this->requireGet();

        $limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 20;
        $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

        $query = "SELECT tt.*, 
                         sn.username as sender_username, sn.nama_lengkap as sender_name,
                         rn.username as receiver_username, rn.nama_lengkap as receiver_name
                  FROM trapeza_transactions tt
                  LEFT JOIN nethera sn ON tt.sender_id = sn.id_nethera
                  LEFT JOIN nethera rn ON tt.receiver_id = rn.id_nethera
                  WHERE tt.sender_id = ? OR tt.receiver_id = ?
                  ORDER BY tt.created_at DESC
                  LIMIT ? OFFSET ?";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iiii", $this->user_id, $this->user_id, $limit, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $transactions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $isSent = ($row['sender_id'] == $this->user_id);

            // Format transaction for frontend (trapeza.js expects these fields)
            $transactions[] = [
                'id' => $row['id'],
                'type' => $row['transaction_type'],
                'is_income' => !$isSent, // income if user is receiver
                'amount' => (int) $row['amount'],
                'description' => $row['description'],
                'other_party' => $isSent
                    ? ($row['receiver_username'] ?? 'System')
                    : ($row['sender_username'] ?? 'System'),
                'created_at' => $row['created_at'],
                'direction' => $isSent ? 'sent' : 'received'
            ];
        }
        mysqli_stmt_close($stmt);

        $this->success(['transactions' => $transactions]);
    }

    /**
     * POST: Transfer gold to another user
     */
    public function transferGold()
    {
        $this->requirePost();

        $input = $this->getInput();
        // Support both recipient_username (from frontend) and recipient_id (for API compatibility)
        $recipient_username = isset($input['recipient_username']) ? trim($input['recipient_username']) : '';
        $recipient_id = isset($input['recipient_id']) ? (int) $input['recipient_id'] : 0;
        $amount = isset($input['amount']) ? (int) $input['amount'] : 0;
        $description = isset($input['description']) ? trim($input['description']) : 'Gold transfer';

        // If username provided, look up the ID
        if ($recipient_username && !$recipient_id) {
            $lookup_stmt = mysqli_prepare($this->conn, "SELECT id_nethera FROM nethera WHERE username = ? AND status_akun = 'Aktif'");
            mysqli_stmt_bind_param($lookup_stmt, "s", $recipient_username);
            mysqli_stmt_execute($lookup_stmt);
            $lookup_result = mysqli_stmt_get_result($lookup_stmt);
            $lookup_row = mysqli_fetch_assoc($lookup_result);
            mysqli_stmt_close($lookup_stmt);

            if ($lookup_row) {
                $recipient_id = (int) $lookup_row['id_nethera'];
            }
        }

        // Validation
        if (!$recipient_id) {
            $this->error('Recipient not found');
            return;
        }

        if ($recipient_id == $this->user_id) {
            $this->error('Cannot transfer to yourself');
            return;
        }

        if ($amount < 1) {
            $this->error('Amount must be at least 1');
            return;
        }

        if ($amount > 10000) {
            $this->error('Maximum transfer is 10,000 gold');
            return;
        }

        // Rate limit - 5 transfers per day
        $today = date('Y-m-d');
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) as count FROM trapeza_transactions 
             WHERE sender_id = ? AND transaction_type = 'transfer' AND DATE(created_at) = ?"
        );
        mysqli_stmt_bind_param($stmt, "is", $this->user_id, $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($row['count'] >= self::DAILY_TRANSFER_LIMIT) {
            $this->error('Daily transfer limit reached (5 per day)');
            return;
        }

        // Check balance
        $user_gold = $this->getUserGold();
        if ($user_gold < $amount) {
            $this->error("Not enough gold! Have {$user_gold}, need {$amount}.");
            return;
        }

        // Verify recipient exists
        $rec_stmt = mysqli_prepare($this->conn, "SELECT id_nethera, username FROM nethera WHERE id_nethera = ? AND status_akun = 'Aktif'");
        mysqli_stmt_bind_param($rec_stmt, "i", $recipient_id);
        mysqli_stmt_execute($rec_stmt);
        $rec_result = mysqli_stmt_get_result($rec_stmt);
        $recipient = mysqli_fetch_assoc($rec_result);
        mysqli_stmt_close($rec_stmt);

        if (!$recipient) {
            $this->error('Recipient not found or inactive');
            return;
        }

        // Execute transfer (atomic)
        mysqli_begin_transaction($this->conn);
        try {
            // Deduct from sender
            $this->deductGold($amount);

            // Add to receiver
            $add_stmt = mysqli_prepare($this->conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
            mysqli_stmt_bind_param($add_stmt, "ii", $amount, $recipient_id);
            mysqli_stmt_execute($add_stmt);
            mysqli_stmt_close($add_stmt);

            // Log transaction
            $this->logGoldTransaction($this->user_id, $recipient_id, $amount, 'transfer', $description);

            mysqli_commit($this->conn);

            $this->success([
                'new_balance' => $user_gold - $amount,
                'recipient' => $recipient['username']
            ], "Transferred {$amount} gold to {$recipient['username']}!");

        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            $this->error('Transfer failed: ' . $e->getMessage());
        }
    }

    /**
     * GET: Search for transfer recipients
     */
    public function searchNethera()
    {
        $this->requireGet();

        $query = isset($_GET['query']) ? trim($_GET['query']) : '';

        if (strlen($query) < 2) {
            $this->success(['results' => []]);
            return;
        }

        $search_pattern = '%' . $query . '%';
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT id_nethera, username, nama_lengkap 
             FROM nethera 
             WHERE (username LIKE ? OR nama_lengkap LIKE ?) 
             AND status_akun = 'Aktif' 
             AND role = 'Nethera'
             AND id_nethera != ?
             LIMIT 10"
        );
        mysqli_stmt_bind_param($stmt, "ssi", $search_pattern, $search_pattern, $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $results = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
        mysqli_stmt_close($stmt);

        $this->success(['results' => $results]);
    }
}
