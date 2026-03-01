<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR') || define('HOUR', 3600);
defined('DAY') || define('DAY', 86400);
defined('WEEK') || define('WEEK', 604800);
defined('MONTH') || define('MONTH', 2_592_000);
defined('YEAR') || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS') || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR') || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG') || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE') || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS') || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT') || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE') || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN') || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX') || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

/*
 |--------------------------------------------------------------------------
 | MOE Application Constants
 |--------------------------------------------------------------------------
 |
 | Application-specific constants ported from the legacy moe/core/config.php.
 |
 */

// Application Identity
defined('APP_DISPLAY_NAME') || define('APP_DISPLAY_NAME', 'Mediterranean of Egypt');
defined('APP_SHORT_NAME') || define('APP_SHORT_NAME', 'MOE');
defined('MOE_VERSION') || define('MOE_VERSION', '2.0.0-ci4');

// User Roles
defined('ROLE_NETHERA') || define('ROLE_NETHERA', 'Nethera');
defined('ROLE_VASIKI') || define('ROLE_VASIKI', 'Vasiki');
defined('ROLE_HAKAES') || define('ROLE_HAKAES', 'Hakaes');
defined('ROLE_ANUBIS') || define('ROLE_ANUBIS', 'Anubis');

// Account Statuses
defined('STATUS_AKTIF') || define('STATUS_AKTIF', 'Aktif');
defined('STATUS_PENDING') || define('STATUS_PENDING', 'Pending');
defined('STATUS_HIATUS') || define('STATUS_HIATUS', 'Hiatus');
defined('STATUS_OUT') || define('STATUS_OUT', 'Out');

// OTP Settings
defined('OTP_EXPIRY_SECONDS') || define('OTP_EXPIRY_SECONDS', 900);  // 15 minutes
defined('OTP_LENGTH') || define('OTP_LENGTH', 6);

// Gacha Settings
defined('GACHA_COST_NORMAL') || define('GACHA_COST_NORMAL', 100);
defined('GACHA_COST_PREMIUM') || define('GACHA_COST_PREMIUM', 500);

// Rate Limiting
defined('LOGIN_MAX_ATTEMPTS') || define('LOGIN_MAX_ATTEMPTS', 5);
defined('LOGIN_LOCKOUT_MINUTES') || define('LOGIN_LOCKOUT_MINUTES', 15);
defined('REGISTER_MAX_PER_HOUR') || define('REGISTER_MAX_PER_HOUR', 3);
defined('OTP_MAX_ATTEMPTS') || define('OTP_MAX_ATTEMPTS', 5);

// Session
defined('SESSION_TIMEOUT_MINUTES') || define('SESSION_TIMEOUT_MINUTES', 120);

// Defaults
defined('DEFAULT_GOLD') || define('DEFAULT_GOLD', 500);
defined('MAX_PROFILE_PHOTO_SIZE') || define('MAX_PROFILE_PHOTO_SIZE', 2 * 1024 * 1024); // 2 MB
