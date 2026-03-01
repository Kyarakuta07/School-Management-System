<?php

if (!function_exists('get_safe_avatar')) {
    /**
     * Generate a safe base_url for a profile photo
     */
    function get_safe_avatar(?string $photo): string
    {
        if (empty($photo)) {
            return '';
        }

        $safe = basename($photo);
        if (!preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $safe)) {
            return '';
        }

        return base_url('assets/uploads/profiles/' . $safe);
    }
}
