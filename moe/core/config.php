<?php
/**
 * System Configuration Constants
 * Mediterranean of Egypt - School Management System
 */

// OTP Settings
define('OTP_EXPIRY_SECONDS', 900); // 15 minutes
define('OTP_LENGTH', 6);

// Gacha Settings  
define('GACHA_COST_NORMAL', 100);
define('GACHA_COST_PREMIUM', 500);

// Rate Limiting
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('REGISTER_MAX_PER_HOUR', 3);
define('OTP_MAX_ATTEMPTS', 5);

// Session Settings
define('SESSION_TIMEOUT_MINUTES', 120);

// Timezone
date_default_timezone_set('Asia/Jakarta');
