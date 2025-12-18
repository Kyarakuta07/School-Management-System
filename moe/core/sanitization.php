<?php
/**
 * Input Sanitization and Validation Functions
 * Provides utilities for cleaning and validating user input
 */

/**
 * Sanitize string for safe HTML output
 * @param string $string The string to sanitize
 * @return string Sanitized string
 */
function sanitize_output($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize string for input processing
 * @param string $string The string to sanitize
 * @return string Sanitized string
 */
function sanitize_input($string)
{
    return trim(strip_tags($string));
}

/**
 * Validate and sanitize email address
 * @param string $email The email to validate
 * @return string|false Sanitized email or false if invalid
 */
function validate_email($email)
{
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate and sanitize phone number
 * @param string $phone The phone number to validate
 * @return string|false Sanitized phone or false if invalid
 */
function validate_phone($phone)
{
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Check length (10-15 digits)
    if (strlen($phone) >= 10 && strlen($phone) <= 15) {
        return $phone;
    }

    return false;
}

/**
 * Validate password strength
 * @param string $password The password to validate
 * @return array ['valid' => bool, 'errors' => array]
 */
function validate_password($password)
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate OTP code format
 * @param string $otp The OTP to validate
 * @return bool True if valid format
 */
function validate_otp_format($otp)
{
    return strlen($otp) === 6 && ctype_digit($otp);
}
