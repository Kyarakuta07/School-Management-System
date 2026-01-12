<?php
/**
 * Authentication Helper Class
 * Mediterranean of Egypt - School Management System
 * 
 * Centralizes authentication logic to avoid code duplication.
 * Must be used AFTER session_start() is called.
 * 
 * Usage:
 *   Auth::requireNethera();  // Redirect if not Nethera
 *   Auth::requireVasiki();   // Redirect if not Vasiki (admin)
 *   $user = Auth::user();    // Get current user data
 *   $id = Auth::id();        // Get current user ID
 */

class Auth
{
    private static $userData = null;

    // ==================================================
    // ROLE REQUIREMENT METHODS
    // ==================================================

    /**
     * Require Nethera role. Redirects to login if not authenticated.
     * Call this at the top of any Nethera-only page.
     * 
     * @param string $redirectUrl Optional custom redirect URL
     */
    public static function requireNethera($redirectUrl = '../index.php?pesan=gagal_akses')
    {
        if (!self::isLoggedIn() || !self::hasRole('Nethera')) {
            header("Location: $redirectUrl");
            exit();
        }

        // Check if account is still active
        if (!self::isAccountActive()) {
            session_destroy();
            $status = self::getSessionValue('status_akun', 'Pending');
            $message = $status === 'Pending' ? 'pending_approval' : 'access_denied';
            header("Location: ../index.php?pesan=$message");
            exit();
        }
    }

    /**
     * Require Nethera OR Vasiki role. Allows admins to view user dashboard.
     * Call this at the top of user pages that admin should also access.
     * 
     * @param string $redirectUrl Optional custom redirect URL
     */
    public static function requireNetheraOrVasiki($redirectUrl = '../index.php?pesan=gagal_akses')
    {
        if (!self::isLoggedIn()) {
            header("Location: $redirectUrl");
            exit();
        }

        $role = self::getSessionValue('role');
        if ($role !== 'Nethera' && $role !== 'Vasiki') {
            header("Location: $redirectUrl");
            exit();
        }

        // Check if account is still active (skip for admin)
        if ($role === 'Nethera' && !self::isAccountActive()) {
            session_destroy();
            $status = self::getSessionValue('status_akun', 'Pending');
            $message = $status === 'Pending' ? 'pending_approval' : 'access_denied';
            header("Location: ../index.php?pesan=$message");
            exit();
        }
    }

    /**
     * Require Vasiki (admin) role. Redirects to login if not authenticated.
     * Call this at the top of any admin-only page.
     * 
     * @param string $redirectUrl Optional custom redirect URL
     */
    public static function requireVasiki($redirectUrl = '../index.php?pesan=gagal')
    {
        if (!self::isLoggedIn() || !self::hasRole('Vasiki')) {
            header("Location: $redirectUrl");
            exit();
        }
    }

    /**
     * Require any authenticated user (Nethera or Vasiki)
     * 
     * @param string $redirectUrl Optional custom redirect URL
     */
    public static function requireLogin($redirectUrl = '../index.php?pesan=gagal')
    {
        if (!self::isLoggedIn()) {
            header("Location: $redirectUrl");
            exit();
        }
    }

    /**
     * For API endpoints - returns JSON error instead of redirect
     */
    public static function requireNetheraApi()
    {
        if (!self::isLoggedIn() || !self::hasRole('Nethera')) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized. Please login.',
                'error_code' => 'UNAUTHORIZED'
            ]);
            exit();
        }
    }

    /**
     * For API endpoints - Nethera OR Vasiki role (allows admin access)
     */
    public static function requireNetheraOrVasikiApi()
    {
        if (!self::isLoggedIn()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized. Please login.',
                'error_code' => 'UNAUTHORIZED'
            ]);
            exit();
        }

        $role = self::getSessionValue('role');
        if ($role !== 'Nethera' && $role !== 'Vasiki') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Access denied.',
                'error_code' => 'FORBIDDEN'
            ]);
            exit();
        }
    }

    /**
     * For API endpoints - Vasiki role
     */
    public static function requireVasikiApi()
    {
        if (!self::isLoggedIn() || !self::hasRole('Vasiki')) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized. Admin access required.',
                'error_code' => 'UNAUTHORIZED'
            ]);
            exit();
        }
    }

    // ==================================================
    // USER DATA METHODS
    // ==================================================

    /**
     * Get current user's ID
     * @return int|null User ID or null if not logged in
     */
    public static function id()
    {
        return self::getSessionValue('id_nethera');
    }

    /**
     * Get current user's full name
     * @return string|null
     */
    public static function name()
    {
        return self::getSessionValue('nama_lengkap');
    }

    /**
     * Get current user's role
     * @return string|null 'Nethera' or 'Vasiki'
     */
    public static function role()
    {
        return self::getSessionValue('role');
    }

    /**
     * Get current user's username
     * @return string|null
     */
    public static function username()
    {
        return self::getSessionValue('username');
    }

    /**
     * Get full user data from database (cached per request)
     * 
     * @param mysqli $conn Database connection (optional, will use global $conn)
     * @return array|null User data array or null if not logged in
     * 
     * @example
     *   $user = Auth::user($conn);
     *   echo $user['email'];
     *   echo $user['gold'];
     */
    public static function user($conn = null)
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        // Return cached data if available
        if (self::$userData !== null) {
            return self::$userData;
        }

        // Get connection from global if not provided
        if ($conn === null) {
            global $conn;
        }

        $userId = self::id();
        $stmt = mysqli_prepare($conn, "SELECT * FROM nethera WHERE id_nethera = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        self::$userData = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return self::$userData;
    }

    /**
     * Get specific user attribute
     * 
     * @param string $key Attribute name (e.g., 'gold', 'email', 'sanctuary_id')
     * @param mixed $default Default value if not found
     * @param mysqli $conn Database connection
     * @return mixed
     */
    public static function get($key, $default = null, $conn = null)
    {
        $user = self::user($conn);
        return $user[$key] ?? $default;
    }

    /**
     * Refresh user data from database (clear cache)
     */
    public static function refresh()
    {
        self::$userData = null;
    }

    // ==================================================
    // CHECK METHODS
    // ==================================================

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['status_login']) && $_SESSION['status_login'] === 'berhasil';
    }

    /**
     * Check if user has specific role
     * @param string $role Role to check ('Nethera' or 'Vasiki')
     * @return bool
     */
    public static function hasRole($role)
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    /**
     * Check if user is Nethera
     * @return bool
     */
    public static function isNethera()
    {
        return self::hasRole('Nethera');
    }

    /**
     * Check if user is Vasiki (admin)
     * @return bool
     */
    public static function isVasiki()
    {
        return self::hasRole('Vasiki');
    }

    /**
     * Check if account status is active
     * Uses cached user data or checks database
     * @return bool
     */
    public static function isAccountActive()
    {
        // First check session cache
        if (isset($_SESSION['status_akun'])) {
            return $_SESSION['status_akun'] === 'Aktif';
        }

        // Otherwise check database
        $user = self::user();
        return $user && trim($user['status_akun']) === 'Aktif';
    }

    /**
     * Check if user owns a resource
     * Useful for checking pet ownership, etc.
     * 
     * @param int $ownerId Owner ID from database
     * @return bool
     */
    public static function owns($ownerId)
    {
        return self::id() === (int) $ownerId;
    }

    // ==================================================
    // SESSION HELPERS
    // ==================================================

    /**
     * Get a value from session with default
     * @param string $key Session key
     * @param mixed $default Default value
     * @return mixed
     */
    private static function getSessionValue($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value (for login process)
     * @param string $key
     * @param mixed $value
     */
    public static function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Clear all auth session data (for logout)
     */
    public static function clearSession()
    {
        unset($_SESSION['status_login']);
        unset($_SESSION['id_nethera']);
        unset($_SESSION['nama_lengkap']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        unset($_SESSION['status_akun']);
        self::$userData = null;
    }
}
