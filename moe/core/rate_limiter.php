<?php
/**
 * Rate Limiter Class
 * Prevents brute force attacks by limiting request attempts
 * Uses database to track attempts across sessions
 */

class RateLimiter
{
    private $conn;
    private static $table_checked = false;

    /**
     * Constructor
     * @param mysqli $db_connection Database connection
     */
    public function __construct($db_connection)
    {
        $this->conn = $db_connection;

        // Only check table existence once per script execution
        if (!self::$table_checked) {
            $this->createTableIfNotExists();
            self::$table_checked = true;
        }
    }

    /**
     * Create rate_limits table if it doesn't exist
     */
    private function createTableIfNotExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            attempts INT DEFAULT 1,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            locked_until DATETIME NULL,
            INDEX idx_identifier_action (identifier, action),
            INDEX idx_locked (locked_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        mysqli_query($this->conn, $sql);
    }

    /**
     * Check if action is allowed under rate limit
     * @param string $identifier Unique identifier (username, IP, etc.)
     * @param string $action Action type (login, register, otp_verify, etc.)
     * @param int $max_attempts Maximum attempts allowed
     * @param int $time_window_minutes Time window in minutes
     * @return array ['allowed' => bool, 'attempts' => int, 'remaining' => int, 'locked_until' => string|null]
     */
    public function checkLimit($identifier, $action, $max_attempts, $time_window_minutes)
    {
        // Clean old records that are no longer locked and outside time window
        // IMPORTANT: Only clean records for THIS SPECIFIC action to prevent
        // shorter time windows from deleting records with longer time windows
        $cleanup_stmt = mysqli_prepare(
            $this->conn,
            "DELETE FROM rate_limits 
             WHERE action = ?
             AND last_attempt < DATE_SUB(NOW(), INTERVAL ? MINUTE) 
             AND (locked_until IS NULL OR locked_until < NOW())"
        );
        mysqli_stmt_bind_param($cleanup_stmt, "si", $action, $time_window_minutes);
        mysqli_stmt_execute($cleanup_stmt);
        mysqli_stmt_close($cleanup_stmt);

        // Check if currently locked
        $lock_check = mysqli_prepare(
            $this->conn,
            "SELECT locked_until FROM rate_limits 
             WHERE identifier = ? AND action = ? AND locked_until > NOW()"
        );
        mysqli_stmt_bind_param($lock_check, "ss", $identifier, $action);
        mysqli_stmt_execute($lock_check);
        $lock_result = mysqli_stmt_get_result($lock_check);

        if ($lock_row = mysqli_fetch_assoc($lock_result)) {
            mysqli_stmt_close($lock_check);
            return [
                'allowed' => false,
                'locked_until' => $lock_row['locked_until'],
                'attempts' => $max_attempts,
                'remaining' => 0
            ];
        }
        mysqli_stmt_close($lock_check);

        // Get current attempts within time window
        $check_stmt = mysqli_prepare(
            $this->conn,
            "SELECT id, attempts FROM rate_limits 
             WHERE identifier = ? AND action = ? 
             AND last_attempt > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        mysqli_stmt_bind_param($check_stmt, "ssi", $identifier, $action, $time_window_minutes);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if ($row = mysqli_fetch_assoc($check_result)) {
            $record_id = $row['id'];
            $attempts = $row['attempts'];
            mysqli_stmt_close($check_stmt);

            if ($attempts >= $max_attempts) {
                // Lock the identifier
                $lock_stmt = mysqli_prepare(
                    $this->conn,
                    "UPDATE rate_limits 
                     SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) 
                     WHERE id = ?"
                );
                mysqli_stmt_bind_param($lock_stmt, "ii", $time_window_minutes, $record_id);
                mysqli_stmt_execute($lock_stmt);
                mysqli_stmt_close($lock_stmt);

                return [
                    'allowed' => false,
                    'attempts' => $attempts,
                    'remaining' => 0,
                    'locked_until' => date('Y-m-d H:i:s', time() + ($time_window_minutes * 60))
                ];
            }

            // Increment attempts
            $update_stmt = mysqli_prepare(
                $this->conn,
                "UPDATE rate_limits 
                 SET attempts = attempts + 1, last_attempt = NOW() 
                 WHERE id = ?"
            );
            mysqli_stmt_bind_param($update_stmt, "i", $record_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);

            return [
                'allowed' => true,
                'attempts' => $attempts + 1,
                'remaining' => $max_attempts - $attempts - 1,
                'locked_until' => null
            ];
        } else {
            // First attempt - create new record
            mysqli_stmt_close($check_stmt);

            $insert_stmt = mysqli_prepare(
                $this->conn,
                "INSERT INTO rate_limits (identifier, action, attempts, last_attempt) 
                 VALUES (?, ?, 1, NOW())"
            );
            mysqli_stmt_bind_param($insert_stmt, "ss", $identifier, $action);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);

            return [
                'allowed' => true,
                'attempts' => 1,
                'remaining' => $max_attempts - 1,
                'locked_until' => null
            ];
        }
    }

    /**
     * Reset rate limit for an identifier and action
     * @param string $identifier Unique identifier
     * @param string $action Action type
     */
    public function resetLimit($identifier, $action)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "DELETE FROM rate_limits WHERE identifier = ? AND action = ?"
        );
        mysqli_stmt_bind_param($stmt, "ss", $identifier, $action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /**
     * Get current attempt count
     * @param string $identifier Unique identifier
     * @param string $action Action type
     * @return int Number of attempts
     */
    public function getAttempts($identifier, $action)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT attempts FROM rate_limits 
             WHERE identifier = ? AND action = ?"
        );
        mysqli_stmt_bind_param($stmt, "ss", $identifier, $action);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            return $row['attempts'];
        }

        mysqli_stmt_close($stmt);
        return 0;
    }
}
