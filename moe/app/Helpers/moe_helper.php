<?php

/**
 * MOE Helper Functions
 * Ported from legacy moe/core/helpers.php + sanitization.php.
 *
 * Load with: helper('moe');
 */

// ==================================================
// DATE / TIME HELPERS
// ==================================================

if (!function_exists('format_date_id')) {
    /**
     * Format date to Indonesian format.
     */
    function format_date_id(string $date, bool $withTime = false): string
    {
        $months = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];

        $ts = strtotime($date);
        if ($ts === false) {
            return $date;
        }

        $day = date('j', $ts);
        $month = $months[(int) date('n', $ts)];
        $year = date('Y', $ts);
        $formatted = "$day $month $year";

        if ($withTime) {
            $formatted .= ' ' . date('H:i', $ts) . ' WIB';
        }

        return $formatted;
    }
}

if (!function_exists('time_ago')) {
    /**
     * Human-readable "time ago" string.
     */
    function time_ago($datetime): string
    {
        $ts = is_numeric($datetime) ? (int) $datetime : strtotime($datetime);
        $diff = time() - $ts;

        if ($diff < 60) {
            return 'Baru saja';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        }
        if ($diff < 2592000) {
            return floor($diff / 86400) . ' hari lalu';
        }
        if ($diff < 31536000) {
            return floor($diff / 2592000) . ' bulan lalu';
        }

        return floor($diff / 31536000) . ' tahun lalu';
    }
}

// ==================================================
// NUMBER / DISPLAY HELPERS
// ==================================================

if (!function_exists('format_number')) {
    /**
     * Format number with thousand separator.
     */
    function format_number($number): string
    {
        return number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('format_gold')) {
    /**
     * Format gold amount with coin icon.
     */
    function format_gold(int $gold): string
    {
        return '<span class="gold-amount">🪙 ' . format_number($gold) . '</span>';
    }
}

// ==================================================
// STRING HELPERS
// ==================================================

if (!function_exists('random_string_moe')) {
    /**
     * Generate a random string.
     */
    function random_string_moe(int $length = 32): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }
}

if (!function_exists('generate_otp')) {
    /**
     * Generate a numeric OTP code.
     */
    function generate_otp(int $length = 6): string
    {
        $min = (int) pow(10, $length - 1);
        $max = (int) pow(10, $length) - 1;

        return (string) random_int($min, $max);
    }
}

// ==================================================
// SANITIZATION / VALIDATION
// ==================================================

if (!function_exists('sanitize_input')) {
    /**
     * Sanitize user input by trimming and stripping tags.
     */
    function sanitize_input(string $string): string
    {
        return trim(strip_tags($string));
    }
}

if (!function_exists('validate_phone')) {
    /**
     * Validate and sanitize phone number (10-15 digits).
     *
     * @return string|false
     */
    function validate_phone(string $phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) >= 10 && strlen($phone) <= 15) {
            return $phone;
        }

        return false;
    }
}

if (!function_exists('validate_password_strength')) {
    /**
     * Validate password strength.
     *
     * @return array{valid: bool, errors: string[]}
     */
    function validate_password_strength(string $password): array
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

        return ['valid' => empty($errors), 'errors' => $errors];
    }
}

if (!function_exists('validate_otp_format')) {
    /**
     * Validate OTP format (6 digits).
     */
    function validate_otp_format(string $otp): bool
    {
        return strlen($otp) === 6 && ctype_digit($otp);
    }
}

// ==================================================
// CONTENT HELPERS
// ==================================================

if (!function_exists('wrap_plain_text')) {
    /**
     * Wrap plain text (no HTML tags) with <p> and nl2br().
     * If the text already contains HTML tags, it is returned as-is.
     */
    function wrap_plain_text(string $text): string
    {
        if (preg_match('/<[^>]+>/', $text)) {
            return $text; // Already contains HTML
        }

        return '<p>' . nl2br(htmlspecialchars($text)) . '</p>';
    }
}
