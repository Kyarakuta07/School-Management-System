<?php
/**
 * TRAPEZA - MOE Banking System API
 * Handles gold transactions, transfers, and transaction history
 */

session_start();

// Check authentication
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'Nethera') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['id_nethera'];

// Include dependencies
include '../connection.php';
require_once '../includes/rate_limiter.php';

// Initialize rate limiter
$api_limiter = new RateLimiter($conn);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Helper function for method validation
function api_method_not_allowed($expected)
{
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => "Method not allowed. Expected: $expected"]);
    exit();
}

// Helper function for rate limiting
function api_rate_limited($locked_until)
{
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Rate limit exceeded',
        'locked_until' => $locked_until
    ]);
    exit();
}

// Main routing
switch ($action) {

    // ============================================
    // GET: Get user's gold balance
    // ============================================
    case 'get_balance':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $balance_stmt = mysqli_prepare($conn, "SELECT gold, username FROM nethera WHERE id_nethera = ?");
        mysqli_stmt_bind_param($balance_stmt, "i", $user_id);
        mysqli_stmt_execute($balance_stmt);
        $balance_result = mysqli_stmt_get_result($balance_stmt);
        $balance_data = mysqli_fetch_assoc($balance_result);
        mysqli_stmt_close($balance_stmt);

        echo json_encode([
            'success' => true,
            'gold' => $balance_data['gold'] ?? 0,
            'username' => $balance_data['username'] ?? ''
        ]);
        break;

    // ============================================
    // GET: Get transaction history
    // ============================================
    case 'get_transactions':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $limit = isset($_GET['limit']) ? min(100, (int) $_GET['limit']) : 20;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        // Get transactions where user is sender or receiver
        $query = "SELECT 
                    t.id, 
                    t.amount, 
                    t.transaction_type, 
                    t.description, 
                    t.created_at,
                    t.sender_id,
                    t.receiver_id,
                    sender.username as sender_username,
                    receiver.username as receiver_username
                  FROM trapeza_transactions t
                  LEFT JOIN nethera sender ON t.sender_id = sender.id_nethera
                  LEFT JOIN nethera receiver ON t.receiver_id = receiver.id_nethera
                  WHERE t.sender_id = ? OR t.receiver_id = ?
                  ORDER BY t.created_at DESC
                  LIMIT ? OFFSET ?";

        $trans_stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($trans_stmt, "iiii", $user_id, $user_id, $limit, $offset);
        mysqli_stmt_execute($trans_stmt);
        $trans_result = mysqli_stmt_get_result($trans_stmt);

        $transactions = [];
        while ($row = mysqli_fetch_assoc($trans_result)) {
            $is_income = ($row['receiver_id'] == $user_id);
            $other_party = $is_income ? $row['sender_username'] : $row['receiver_username'];

            $transactions[] = [
                'id' => $row['id'],
                'type' => $row['transaction_type'],
                'amount' => $is_income ? $row['amount'] : -$row['amount'],
                'other_party' => $other_party,
                'description' => $row['description'],
                'created_at' => $row['created_at'],
                'is_income' => $is_income
            ];
        }
        mysqli_stmt_close($trans_stmt);

        // Get total count
        $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM trapeza_transactions WHERE sender_id = ? OR receiver_id = ?");
        mysqli_stmt_bind_param($count_stmt, "ii", $user_id, $user_id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_count = mysqli_fetch_assoc($count_result)['total'];
        mysqli_stmt_close($count_stmt);

        echo json_encode([
            'success' => true,
            'transactions' => $transactions,
            'total_count' => $total_count
        ]);
        break;

    // ============================================
    // POST: Transfer gold to another user
    // ============================================
    case 'transfer_gold':
        if ($method !== 'POST') {
            api_method_not_allowed('POST');
        }

        // Rate limiting - 5 transfers per day
        $transfer_limit = $api_limiter->checkLimit($user_id, 'gold_transfer', 5, 1440);
        if (!$transfer_limit['allowed']) {
            api_rate_limited($transfer_limit['locked_until']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $recipient_username = isset($input['recipient_username']) ? trim($input['recipient_username']) : '';
        $amount = isset($input['amount']) ? (int) $input['amount'] : 0;
        $description = isset($input['description']) ? trim($input['description']) : 'Gold transfer';

        // Validation
        if (empty($recipient_username)) {
            echo json_encode(['success' => false, 'error' => 'Recipient username required']);
            break;
        }

        if ($amount < 10) {
            echo json_encode(['success' => false, 'error' => 'Minimum transfer amount is 10 gold']);
            break;
        }

        if ($amount > 1000) {
            echo json_encode(['success' => false, 'error' => 'Maximum transfer amount is 1000 gold']);
            break;
        }

        // Get recipient
        $recipient_stmt = mysqli_prepare($conn, "SELECT id_nethera, username FROM nethera WHERE username = ? AND status_akun = 'Aktif'");
        mysqli_stmt_bind_param($recipient_stmt, "s", $recipient_username);
        mysqli_stmt_execute($recipient_stmt);
        $recipient_result = mysqli_stmt_get_result($recipient_stmt);
        $recipient = mysqli_fetch_assoc($recipient_result);
        mysqli_stmt_close($recipient_stmt);

        if (!$recipient) {
            echo json_encode(['success' => false, 'error' => 'Recipient not found or inactive']);
            break;
        }

        $recipient_id = $recipient['id_nethera'];

        // Cannot transfer to self
        if ($recipient_id == $user_id) {
            echo json_encode(['success' => false, 'error' => 'Cannot transfer to yourself']);
            break;
        }

        // Check daily limit (3000 gold)
        $daily_check = mysqli_prepare(
            $conn,
            "SELECT IFNULL(SUM(amount), 0) as total 
             FROM trapeza_transactions 
             WHERE sender_id = ? 
             AND transaction_type = 'transfer' 
             AND DATE(created_at) = CURDATE()"
        );
        mysqli_stmt_bind_param($daily_check, "i", $user_id);
        mysqli_stmt_execute($daily_check);
        $daily_result = mysqli_stmt_get_result($daily_check);
        $daily_total = mysqli_fetch_assoc($daily_result)['total'];
        mysqli_stmt_close($daily_check);

        if ($daily_total + $amount > 3000) {
            echo json_encode(['success' => false, 'error' => 'Daily transfer limit exceeded (3000 gold)']);
            break;
        }

        // Check sufficient funds
        $balance_check = mysqli_prepare($conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
        mysqli_stmt_bind_param($balance_check, "i", $user_id);
        mysqli_stmt_execute($balance_check);
        $balance_result = mysqli_stmt_get_result($balance_check);
        $user_gold = mysqli_fetch_assoc($balance_result)['gold'];
        mysqli_stmt_close($balance_check);

        if ($user_gold < $amount) {
            echo json_encode(['success' => false, 'error' => 'Insufficient funds']);
            break;
        }

        // Execute transfer (atomic transaction)
        mysqli_begin_transaction($conn);

        try {
            // Deduct from sender
            $deduct_stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold - ? WHERE id_nethera = ?");
            mysqli_stmt_bind_param($deduct_stmt, "ii", $amount, $user_id);
            mysqli_stmt_execute($deduct_stmt);
            mysqli_stmt_close($deduct_stmt);

            // Add to receiver
            $add_stmt = mysqli_prepare($conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
            mysqli_stmt_bind_param($add_stmt, "ii", $amount, $recipient_id);
            mysqli_stmt_execute($add_stmt);
            mysqli_stmt_close($add_stmt);

            // Log transaction
            $log_stmt = mysqli_prepare(
                $conn,
                "INSERT INTO trapeza_transactions (sender_id, receiver_id, amount, transaction_type, description) 
                 VALUES (?, ?, ?, 'transfer', ?)"
            );
            mysqli_stmt_bind_param($log_stmt, "iiis", $user_id, $recipient_id, $amount, $description);
            mysqli_stmt_execute($log_stmt);
            $transaction_id = mysqli_insert_id($conn);
            mysqli_stmt_close($log_stmt);

            mysqli_commit($conn);

            // Get new balance
            $new_balance = $user_gold - $amount;

            echo json_encode([
                'success' => true,
                'message' => 'Transfer successful!',
                'new_balance' => $new_balance,
                'transaction_id' => $transaction_id
            ]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'error' => 'Transfer failed: ' . $e->getMessage()]);
        }
        break;

    // ============================================
    // GET: Search for Nethera users
    // ============================================
    case 'search_nethera':
        if ($method !== 'GET') {
            api_method_not_allowed('GET');
        }

        $query = isset($_GET['query']) ? trim($_GET['query']) : '';

        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'results' => []]);
            break;
        }

        $search_pattern = '%' . $query . '%';
        $search_stmt = mysqli_prepare(
            $conn,
            "SELECT id_nethera, username, nama_lengkap 
             FROM nethera 
             WHERE (username LIKE ? OR nama_lengkap LIKE ?) 
             AND status_akun = 'Aktif' 
             AND role = 'Nethera'
             AND id_nethera != ?
             LIMIT 10"
        );
        mysqli_stmt_bind_param($search_stmt, "ssi", $search_pattern, $search_pattern, $user_id);
        mysqli_stmt_execute($search_stmt);
        $search_result = mysqli_stmt_get_result($search_stmt);

        $results = [];
        while ($row = mysqli_fetch_assoc($search_result)) {
            $results[] = [
                'id_nethera' => $row['id_nethera'],
                'username' => $row['username'],
                'nama_lengkap' => $row['nama_lengkap']
            ];
        }
        mysqli_stmt_close($search_stmt);

        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
        break;

    // ============================================
    // Default: Unknown action
    // ============================================
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action',
            'available_actions' => ['get_balance', 'get_transactions', 'transfer_gold', 'search_nethera']
        ]);
}
?>