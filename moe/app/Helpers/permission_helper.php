<?php

/**
 * Permission Helper — Centralized authorization checks.
 *
 * Replaces scattered `in_array($role, [ROLE_X, ROLE_Y])` patterns
 * with readable `can('permission_name')` calls.
 *
 * Usage:
 *   if (!can('manage_grades')) return redirect()...
 *   $canManage = can('manage_punishment');
 */

if (!function_exists('can')) {
    /**
     * Check if the current user has a specific permission.
     *
     * @param string $permission One of the defined permission names
     * @param string|null $role Override role (defaults to session role)
     * @return bool
     */
    function can(string $permission, ?string $role = null): bool
    {
        $role = $role ?? session()->get('role') ?? '';

        return match ($permission) {
            // Academic: grade management, quiz CRUD
            'manage_grades',
            'manage_quizzes' => in_array($role, [ROLE_HAKAES, ROLE_VASIKI]),

            // Academic: punishment management
            'manage_punishment' => in_array($role, [ROLE_ANUBIS, ROLE_VASIKI]),

            // Admin panel access
            'access_admin' => in_array($role, [ROLE_VASIKI, ROLE_ANUBIS, ROLE_HAKAES]),

            // Super admin only
            'super_admin' => $role === ROLE_VASIKI,

            default => false,
        };
    }
}

if (!function_exists('cannot')) {
    /**
     * Inverse of can() — for readability.
     */
    function cannot(string $permission, ?string $role = null): bool
    {
        return !can($permission, $role);
    }
}
