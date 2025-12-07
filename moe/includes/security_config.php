<?php
/**
 * Secure Session Configuration
 * Sets secure cookie parameters for production use
 */

// Configure secure session settings before starting session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // Disable secure flag for localhost (HTTP), enable in production with HTTPS
    ini_set('session.cookie_secure', 0);
    ini_set('session.cookie_samesite', 'Lax'); // Changed from Strict to Lax for localhost

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',  // Empty for localhost compatibility
        'secure' => false,  // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'  // Lax allows POST from same origin
    ]);
}
