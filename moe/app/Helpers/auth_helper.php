<?php

/**
 * Auth Helpers — Pure session-based authentication.
 *
 * Provides helper functions for role checking, login status,
 * and user info without any external auth library dependency.
 *
 * Load via: helper('auth');
 */

if (!function_exists('user_role')) {
    /**
     * Get the current user's role as the ROLE_* constant string (title-case).
     */
    function user_role(): ?string
    {
        return session('role');
    }
}

if (!function_exists('user_has_role')) {
    /**
     * Check if the current user has one of the given roles.
     *
     * @param string|array $roles  One or more ROLE_* constants
     */
    function user_has_role($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $currentRole = user_role();
        return $currentRole !== null && in_array($currentRole, $roles, true);
    }
}

if (!function_exists('user_is_logged_in')) {
    /**
     * Check if the current user is authenticated.
     */
    function user_is_logged_in(): bool
    {
        return (bool) session('id_nethera');
    }
}

if (!function_exists('user_name')) {
    /**
     * Get the current user's display name.
     */
    function user_name(): string
    {
        return session('nama_lengkap') ?? '';
    }
}
