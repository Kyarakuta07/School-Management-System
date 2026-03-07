<?php

namespace App\Modules\Trapeza\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use App\Modules\Trapeza\Models\TransactionModel;
use App\Modules\User\Models\UserModel;
use App\Modules\User\Services\ActivityLogService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Trapeza (Bank) API Controller
 * 
 * Ported from legacy TrapezaController.php
 * 
 * Endpoints:
 *   GET  /api/bank/balance       → balance()
 *   GET  /api/bank/transactions  → transactions()
 *   POST /api/bank/transfer      → transfer()
 *   GET  /api/bank/search        → search()
 */
class TrapezaController extends BaseApiController
{
    use IdempotencyTrait;

    const DAILY_TRANSFER_LIMIT = 30;
    protected $activityLog;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->activityLog = service('activityLog');
    }

    public function balance(): ResponseInterface
    {
        return $this->success(['balance' => $this->getUserGold()]);
    }

    public function transactions(): ResponseInterface
    {
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));

        $txModel = new TransactionModel();
        $raw = $txModel->getUserTransactions($this->userId, $limit, $offset);

        $transactions = [];
        foreach ($raw as $row) {
            $isSent = ($row['sender_id'] == $this->userId);
            $transactions[] = [
                'id' => $row['id'],
                'type' => $row['transaction_type'],
                'is_income' => !$isSent,
                'amount' => (int) $row['amount'],
                'description' => $row['description'],
                'other_party' => $isSent ? ($row['receiver_username'] ?? 'System') : ($row['sender_username'] ?? 'System'),
                'created_at' => $row['created_at'],
                'direction' => $isSent ? 'sent' : 'received',
            ];
        }

        return $this->success(['transactions' => $transactions]);
    }

    public function transfer(): ResponseInterface
    {
        if (!$this->acquireIdempotencyLock('trapeza_transfer', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $recipientUsername = trim($input['recipient_username'] ?? '');
        $recipientId = (int) ($input['recipient_id'] ?? 0);
        $amount = (int) ($input['amount'] ?? 0);
        $description = trim($input['description'] ?? 'Gold transfer');

        $userModel = new UserModel();

        // Lookup by username if needed
        if ($recipientUsername && !$recipientId) {
            $recipient = $userModel->findActiveByUsername($recipientUsername);
            $recipientId = $recipient ? (int) $recipient['id_nethera'] : 0;
        }

        // Validations
        if (!$recipientId)
            return $this->error('Recipient not found', 400, 'VALIDATION_ERROR');
        if ($recipientId == $this->userId)
            return $this->error('Cannot transfer to yourself', 400, 'VALIDATION_ERROR');
        if ($amount < 1)
            return $this->error('Amount must be at least 1', 400, 'VALIDATION_ERROR');
        if ($amount > 10000)
            return $this->error('Maximum transfer is 10,000 gold', 400, 'VALIDATION_ERROR');

        // Daily limit check
        $txModel = new TransactionModel();
        if ($txModel->countTodayTransfers($this->userId) >= self::DAILY_TRANSFER_LIMIT) {
            return $this->error('Daily transfer limit reached (5 per day)', 429, 'RATE_LIMITED');
        }

        // Balance check
        $userGold = $this->getUserGold();
        if ($userGold < $amount) {
            return $this->error("Not enough gold! Have {$userGold}, need {$amount}.", 400, 'INSUFFICIENT_FUNDS');
        }

        // Verify recipient exists
        $recipient = $userModel->find($recipientId);
        if (!$recipient || $recipient['status_akun'] !== 'Aktif') {
            return $this->error('Recipient not found or inactive', 404, 'NOT_FOUND');
        }

        // Execute atomic transfer via Service
        $goldService = service('goldService');
        $success = $goldService->transferGold($this->userId, $recipientId, $amount, $description);

        if (!$success) {
            return $this->error('Transfer failed. Please check your balance and recipient status.', 500, 'TRANSFER_FAILED');
        }

        $this->activityLog->log('TRANSFER', 'BANK', "Transferred {$amount} gold to {$recipient['username']} (Recipient ID: {$recipientId})", $this->userId);

        if ($amount >= 1000) {
            $this->activityLog->log('HIGH_VALUE_TRANSFER', 'BANK', "HIGH VALUE: {$amount} gold transferred from user {$this->userId} to {$recipientId}");
        }

        return $this->success([
            'new_balance' => $userGold - $amount,
            'recipient' => $recipient['username'],
        ], "Transferred {$amount} gold to {$recipient['username']}!");
    }

    public function search(): ResponseInterface
    {
        $query = trim($this->request->getGet('query') ?? '');

        if (strlen($query) < 2) {
            return $this->success(['results' => []]);
        }

        $userModel = new UserModel();
        $results = $userModel->searchNethera($query, $this->userId);

        return $this->success(['results' => $results]);
    }
}
