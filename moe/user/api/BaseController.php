<?php
/**
 * MOE Pet System - Base Controller
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Abstract base class for all API controllers.
 * Provides common functionality like:
 * - Database connection
 * - User authentication
 * - JSON response helpers
 * - Rate limiting
 */

abstract class BaseController
{
    protected $conn;
    protected $user_id;
    protected $rate_limiter;

    /**
     * Initialize controller with dependencies
     * 
     * @param mysqli $conn Database connection
     * @param int $user_id Authenticated user ID
     * @param RateLimiter|null $rate_limiter Rate limiter instance
     */
    public function __construct($conn, $user_id, $rate_limiter = null)
    {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->rate_limiter = $rate_limiter;
    }

    /**
     * Send JSON success response
     * 
     * @param array $data Data to include
     * @param string|null $message Optional message
     */
    protected function success($data = [], $message = null)
    {
        // Clear any previous output (whitespace, warnings)
        if (ob_get_length())
            ob_clean();

        $response = array_merge(['success' => true], $data);
        if ($message) {
            $response['message'] = $message;
        }
        echo json_encode($response);
    }

    /**
     * Send JSON error response
     * 
     * @param string $error Error message
     * @param int $code HTTP status code
     */
    protected function error($error, $code = 400)
    {
        // Clear any previous output
        if (ob_get_length())
            ob_clean();

        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $error
        ]);
    }

    /**
     * Get JSON input from request body
     * 
     * @return array Decoded JSON or empty array
     */
    protected function getInput()
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    /**
     * Require POST method
     */
    protected function requirePost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Method not allowed. Use POST.', 405);
            exit;
        }
    }

    /**
     * Require GET method
     */
    protected function requireGet()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error('Method not allowed. Use GET.', 405);
            exit;
        }
    }

    /**
     * Check rate limit
     * 
     * @param string $action Action identifier
     * @param int $max_attempts Max attempts allowed
     * @param int $window_minutes Time window in minutes
     * @return bool True if allowed, exits with error if not
     */
    protected function checkRateLimit($action, $max_attempts, $window_minutes)
    {
        if (!$this->rate_limiter) {
            return true;
        }

        $result = $this->rate_limiter->checkLimit($this->user_id, $action, $max_attempts, $window_minutes);

        if (!$result['allowed']) {
            $this->error('Rate limit exceeded. Please wait.', 429);
            exit;
        }

        return true;
    }

    /**
     * Get user's current gold balance
     * 
     * @return int Gold amount
     */
    protected function getUserGold()
    {
        $stmt = mysqli_prepare($this->conn, "SELECT gold FROM nethera WHERE id_nethera = ?");
        mysqli_stmt_bind_param($stmt, "i", $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $row ? (int) $row['gold'] : 0;
    }

    /**
     * Deduct gold from user
     * 
     * @param int $amount Amount to deduct
     * @return bool Success
     */
    protected function deductGold($amount)
    {
        $stmt = mysqli_prepare($this->conn, "UPDATE nethera SET gold = gold - ? WHERE id_nethera = ?");
        mysqli_stmt_bind_param($stmt, "ii", $amount, $this->user_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    /**
     * Add gold to user
     * 
     * @param int $amount Amount to add
     * @return bool Success
     */
    protected function addGold($amount)
    {
        $stmt = mysqli_prepare($this->conn, "UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?");
        mysqli_stmt_bind_param($stmt, "ii", $amount, $this->user_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    /**
     * Log gold transaction
     */
    protected function logGoldTransaction($sender_id, $receiver_id, $amount, $type, $description)
    {
        try {
            $stmt = mysqli_prepare(
                $this->conn,
                "INSERT INTO trapeza_transactions (sender_id, receiver_id, amount, transaction_type, description, status) 
                 VALUES (?, ?, ?, ?, ?, 'completed')"
            );
            mysqli_stmt_bind_param($stmt, "iiiss", $sender_id, $receiver_id, $amount, $type, $description);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Transaction log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify pet ownership
     * 
     * @param int $pet_id Pet ID to verify
     * @return array|null Pet data if owned, null otherwise
     */
    protected function verifyPetOwnership($pet_id)
    {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $pet_id, $this->user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $pet = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $pet;
    }
}
