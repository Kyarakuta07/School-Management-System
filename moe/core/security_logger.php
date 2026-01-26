<?php
/**
 * Security Logger - Centralized Security Event Logging
 * MOE School Management System
 * 
 * Logs security-related events for audit trail and incident response
 */

/**
 * Log a security event
 * 
 * @param mysqli $conn Database connection
 * @param string $event_type Type of event (failed_login, successful_login, password_change, etc.)
 * @param int|null $user_id User ID (null for events before authentication)
 * @param string $details Additional details about the event
 * @param string $severity Event severity: 'info', 'warning', 'critical'
 * @return bool Success status
 */
function log_security_event($conn, $event_type, $user_id, $details, $severity = 'info')
{
    // Validate severity
    $valid_severities = ['info', 'warning', 'critical'];
    if (!in_array($severity, $valid_severities)) {
        $severity = 'info';
    }

    // Prepare data
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    // Truncate long user agents
    if (strlen($user_agent) > 500) {
        $user_agent = substr($user_agent, 0, 500);
    }

    // Create table if not exists (auto-migration)
    $create_table = "CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        user_id INT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        details TEXT,
        severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_event_type (event_type),
        INDEX idx_user_id (user_id),
        INDEX idx_severity (severity),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $create_table);

    // Insert log entry
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO security_logs (event_type, user_id, ip_address, user_agent, details, severity)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log("Security logger: Failed to prepare statement - " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "sissss", $event_type, $user_id, $ip, $user_agent, $details, $severity);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$success) {
        error_log("Security logger: Failed to log event - " . mysqli_error($conn));
    }

    return $success;
}

/**
 * Get recent security logs
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of logs to retrieve
 * @param string|null $event_type Filter by event type
 * @param string|null $severity Filter by severity
 * @return array Array of log entries
 */
function get_security_logs($conn, $limit = 100, $event_type = null, $severity = null)
{
    $query = "SELECT sl.*, n.username, n.nama_lengkap 
              FROM security_logs sl
              LEFT JOIN nethera n ON sl.user_id = n.id_nethera
              WHERE 1=1";

    $params = [];
    $types = "";

    if ($event_type) {
        $query .= " AND sl.event_type = ?";
        $params[] = $event_type;
        $types .= "s";
    }

    if ($severity) {
        $query .= " AND sl.severity = ?";
        $params[] = $severity;
        $types .= "s";
    }

    $query .= " ORDER BY sl.created_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = mysqli_prepare($conn, $query);

    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }

    mysqli_stmt_close($stmt);

    return $logs;
}

/**
 * Cleanup old security logs (older than specified days)
 * Call this periodically to prevent table bloat
 * 
 * @param mysqli $conn Database connection
 * @param int $days Keep logs for this many days
 * @return int Number of deleted records
 */
function cleanup_security_logs($conn, $days = 90)
{
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
    );

    mysqli_stmt_bind_param($stmt, "i", $days);
    mysqli_stmt_execute($stmt);
    $deleted = mysqli_affected_rows($conn);
    mysqli_stmt_close($stmt);

    return $deleted;
}
