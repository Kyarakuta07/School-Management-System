<?php
/**
 * Admin Activity Logger
 * Mediterranean of Egypt - School Management System
 * 
 * Provides audit trail functionality for all admin actions.
 * Logs are stored in the admin_activity_log database table.
 * 
 * Usage:
 *   require_once 'includes/activity_logger.php';
 *   log_admin_activity($conn, 'DELETE', 'nethera', $id, 'Deleted user: ' . $username);
 */

// Activity types
define('ACTION_VIEW', 'VIEW');
define('ACTION_CREATE', 'CREATE');
define('ACTION_UPDATE', 'UPDATE');
define('ACTION_DELETE', 'DELETE');
define('ACTION_LOGIN', 'LOGIN');
define('ACTION_LOGOUT', 'LOGOUT');
define('ACTION_APPROVE', 'APPROVE');
define('ACTION_REJECT', 'REJECT');

/**
 * Log an admin activity
 * 
 * @param mysqli $conn Database connection
 * @param string $action Action type (CREATE, UPDATE, DELETE, etc.)
 * @param string $entity Entity type (nethera, class, schedule, grade, etc.)
 * @param int|null $entity_id ID of the affected entity
 * @param string $description Human-readable description of the action
 * @param array|null $old_data Previous data (for updates)
 * @param array|null $new_data New data (for creates/updates)
 * @return bool Success status
 */
function log_admin_activity($conn, $action, $entity, $entity_id = null, $description = '', $old_data = null, $new_data = null)
{
    // Ensure table exists
    ensure_log_table_exists($conn);

    // Get admin info from session
    $admin_id = isset($_SESSION['id_nethera']) ? (int) $_SESSION['id_nethera'] : 0;
    $admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';

    // Get IP address
    $ip_address = get_client_ip();

    // Serialize data changes if provided
    $changes_json = null;
    if ($old_data !== null || $new_data !== null) {
        $changes = [];
        if ($old_data !== null)
            $changes['before'] = $old_data;
        if ($new_data !== null)
            $changes['after'] = $new_data;
        $changes_json = json_encode($changes, JSON_UNESCAPED_UNICODE);
    }

    // Get user agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

    // Insert log entry
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO admin_activity_log 
        (admin_id, admin_username, action, entity, entity_id, description, changes, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    if (!$stmt) {
        error_log("Activity Logger: Failed to prepare statement - " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssissss",
        $admin_id,
        $admin_username,
        $action,
        $entity,
        $entity_id,
        $description,
        $changes_json,
        $ip_address,
        $user_agent
    );

    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        error_log("Activity Logger: Failed to insert log - " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Shorthand for logging create actions
 */
function log_create($conn, $entity, $entity_id, $description, $new_data = null)
{
    return log_admin_activity($conn, ACTION_CREATE, $entity, $entity_id, $description, null, $new_data);
}

/**
 * Shorthand for logging update actions
 */
function log_update($conn, $entity, $entity_id, $description, $old_data = null, $new_data = null)
{
    return log_admin_activity($conn, ACTION_UPDATE, $entity, $entity_id, $description, $old_data, $new_data);
}

/**
 * Shorthand for logging delete actions
 */
function log_delete($conn, $entity, $entity_id, $description, $old_data = null)
{
    return log_admin_activity($conn, ACTION_DELETE, $entity, $entity_id, $description, $old_data, null);
}

/**
 * Shorthand for logging login/logout
 */
function log_auth($conn, $action, $description)
{
    return log_admin_activity($conn, $action, 'auth', null, $description);
}

/**
 * Get recent activity logs
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of logs to retrieve
 * @param int $offset Offset for pagination
 * @param array $filters Optional filters (action, entity, admin_id, date_from, date_to)
 * @return array Array of log entries
 */
function get_activity_logs($conn, $limit = 50, $offset = 0, $filters = [])
{
    ensure_log_table_exists($conn);

    $where_clauses = [];
    $params = [];
    $types = "";

    if (!empty($filters['action'])) {
        $where_clauses[] = "action = ?";
        $params[] = $filters['action'];
        $types .= "s";
    }

    if (!empty($filters['entity'])) {
        $where_clauses[] = "entity = ?";
        $params[] = $filters['entity'];
        $types .= "s";
    }

    if (!empty($filters['admin_id'])) {
        $where_clauses[] = "admin_id = ?";
        $params[] = (int) $filters['admin_id'];
        $types .= "i";
    }

    if (!empty($filters['date_from'])) {
        $where_clauses[] = "created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }

    if (!empty($filters['date_to'])) {
        $where_clauses[] = "created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
        $types .= "s";
    }

    $where_sql = "";
    if (!empty($where_clauses)) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }

    $query = "SELECT * FROM admin_activity_log $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        error_log("Activity Logger: Failed to prepare get logs query");
        return [];
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Decode changes JSON
        if ($row['changes']) {
            $row['changes'] = json_decode($row['changes'], true);
        }
        $logs[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $logs;
}

/**
 * Get activity log statistics
 */
function get_activity_stats($conn, $days = 30)
{
    ensure_log_table_exists($conn);

    $date_limit = date('Y-m-d', strtotime("-$days days"));

    $stats = [
        'total' => 0,
        'by_action' => [],
        'by_entity' => [],
        'by_admin' => []
    ];

    // Total count
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin_activity_log WHERE created_at >= '$date_limit'");
    if ($row = mysqli_fetch_assoc($result)) {
        $stats['total'] = (int) $row['total'];
    }

    // By action
    $result = mysqli_query($conn, "SELECT action, COUNT(*) as count FROM admin_activity_log WHERE created_at >= '$date_limit' GROUP BY action ORDER BY count DESC");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['by_action'][$row['action']] = (int) $row['count'];
    }

    // By entity
    $result = mysqli_query($conn, "SELECT entity, COUNT(*) as count FROM admin_activity_log WHERE created_at >= '$date_limit' GROUP BY entity ORDER BY count DESC");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['by_entity'][$row['entity']] = (int) $row['count'];
    }

    // By admin
    $result = mysqli_query($conn, "SELECT admin_username, COUNT(*) as count FROM admin_activity_log WHERE created_at >= '$date_limit' GROUP BY admin_username ORDER BY count DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['by_admin'][$row['admin_username']] = (int) $row['count'];
    }

    return $stats;
}

/**
 * Ensure the log table exists
 */
function ensure_log_table_exists($conn)
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admin_activity_log'");

    if (mysqli_num_rows($table_check) == 0) {
        $create_sql = "CREATE TABLE admin_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            admin_username VARCHAR(100) NOT NULL,
            action VARCHAR(20) NOT NULL,
            entity VARCHAR(50) NOT NULL,
            entity_id INT NULL,
            description TEXT,
            changes JSON NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(500) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_id (admin_id),
            INDEX idx_action (action),
            INDEX idx_entity (entity),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!mysqli_query($conn, $create_sql)) {
            error_log("Activity Logger: Failed to create table - " . mysqli_error($conn));
        }
    }

    $checked = true;
}

/**
 * Get client IP address
 */
function get_client_ip()
{
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return trim($ip);
}

/**
 * Format action for display
 */
function format_action_badge($action)
{
    $badges = [
        'CREATE' => '<span class="badge badge-success">CREATE</span>',
        'UPDATE' => '<span class="badge badge-warning">UPDATE</span>',
        'DELETE' => '<span class="badge badge-danger">DELETE</span>',
        'VIEW' => '<span class="badge badge-info">VIEW</span>',
        'LOGIN' => '<span class="badge badge-primary">LOGIN</span>',
        'LOGOUT' => '<span class="badge badge-secondary">LOGOUT</span>',
        'APPROVE' => '<span class="badge badge-success">APPROVE</span>',
        'REJECT' => '<span class="badge badge-danger">REJECT</span>',
    ];

    return $badges[$action] ?? '<span class="badge">' . htmlspecialchars($action) . '</span>';
}
