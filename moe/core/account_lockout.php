<?php
/**
 * Account Lockout Functions
 * MOE School Management System
 * 
 * Implements account lockout mechanism after multiple failed login attempts
 */

/**
 * Check if account is currently locked
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username to check
 * @return array ['locked' => bool, 'locked_until' => datetime|null, 'attempts' => int]
 */
function check_account_lockout($conn, $username)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT failed_login_attempts, account_locked_until 
         FROM nethera 
         WHERE username = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$data) {
        return ['locked' => false, 'locked_until' => null, 'attempts' => 0];
    }

    $attempts = (int) $data['failed_login_attempts'];
    $locked_until = $data['account_locked_until'];

    // Check if account is locked and lock period has not expired
    $is_locked = false;
    if ($locked_until && strtotime($locked_until) > time()) {
        $is_locked = true;
    } elseif ($locked_until && strtotime($locked_until) <= time()) {
        // Lock period expired, auto-unlock
        reset_login_attempts($conn, $username);
        $is_locked = false;
        $attempts = 0;
    }

    return [
        'locked' => $is_locked,
        'locked_until' => $locked_until,
        'attempts' => $attempts
    ];
}

/**
 * Increment failed login attempts and lock account if threshold reached
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username
 * @param int $max_attempts Maximum attempts before lock (default: 10)
 * @param int $lockout_minutes Lockout duration in minutes (default: 30)
 * @return array ['locked' => bool, 'attempts' => int, 'locked_until' => datetime|null]
 */
function increment_failed_attempts($conn, $username, $max_attempts = 10, $lockout_minutes = 30)
{
    // First check if user exists
    $check_stmt = mysqli_prepare($conn, "SELECT id_nethera FROM nethera WHERE username = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $username);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (!mysqli_fetch_assoc($check_result)) {
        mysqli_stmt_close($check_stmt);
        // User doesn't exist, don't increment (prevents username enumeration)
        return ['locked' => false, 'attempts' => 0, 'locked_until' => null];
    }
    mysqli_stmt_close($check_stmt);

    // Increment attempts
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE nethera 
         SET failed_login_attempts = failed_login_attempts + 1,
             last_failed_login = NOW()
         WHERE username = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Get updated attempt count
    $get_stmt = mysqli_prepare(
        $conn,
        "SELECT failed_login_attempts FROM nethera WHERE username = ?"
    );

    mysqli_stmt_bind_param($get_stmt, "s", $username);
    mysqli_stmt_execute($get_stmt);
    $result = mysqli_stmt_get_result($get_stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($get_stmt);

    $attempts = (int) $data['failed_login_attempts'];
    $locked_until = null;
    $is_locked = false;

    // Lock account if max attempts reached
    if ($attempts >= $max_attempts) {
        $locked_until = date('Y-m-d H:i:s', strtotime("+$lockout_minutes minutes"));

        $lock_stmt = mysqli_prepare(
            $conn,
            "UPDATE nethera 
             SET account_locked_until = ?
             WHERE username = ?"
        );

        mysqli_stmt_bind_param($lock_stmt, "ss", $locked_until, $username);
        mysqli_stmt_execute($lock_stmt);
        mysqli_stmt_close($lock_stmt);

        $is_locked = true;

        // Log the lockout event
        if (function_exists('log_security_event')) {
            log_security_event(
                $conn,
                'account_locked',
                null,
                "Account '$username' locked after $attempts failed attempts. Locked until $locked_until",
                'critical'
            );
        }
    }

    return [
        'locked' => $is_locked,
        'attempts' => $attempts,
        'locked_until' => $locked_until
    ];
}

/**
 * Reset failed login attempts (call after successful login)
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username
 * @return bool Success status
 */
function reset_login_attempts($conn, $username)
{
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE nethera 
         SET failed_login_attempts = 0,
             account_locked_until = NULL,
             last_failed_login = NULL
         WHERE username = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $username);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}

/**
 * Manually unlock an account (admin function)
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to unlock
 * @return bool Success status
 */
function unlock_account($conn, $user_id)
{
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE nethera 
         SET failed_login_attempts = 0,
             account_locked_until = NULL
         WHERE id_nethera = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($success && function_exists('log_security_event')) {
        log_security_event(
            $conn,
            'account_unlocked',
            $user_id,
            "Account manually unlocked by admin",
            'info'
        );
    }

    return $success;
}

/**
 * Get accounts currently locked
 * 
 * @param mysqli $conn Database connection
 * @return array List of locked accounts
 */
function get_locked_accounts($conn)
{
    $query = "SELECT id_nethera, username, nama_lengkap, failed_login_attempts, 
                     account_locked_until, last_failed_login
              FROM nethera
              WHERE account_locked_until IS NOT NULL 
              AND account_locked_until > NOW()
              ORDER BY account_locked_until DESC";

    $result = mysqli_query($conn, $query);

    $locked_accounts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $locked_accounts[] = $row;
    }

    return $locked_accounts;
}
