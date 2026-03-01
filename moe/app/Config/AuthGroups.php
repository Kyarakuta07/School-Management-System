<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    /**
     * Default group for newly registered users.
     * Maps to ROLE_NETHERA (student).
     */
    public string $defaultGroup = 'nethera';

    /**
     * --------------------------------------------------------------------
     * Groups — Mapped from ROLE_ constants
     * --------------------------------------------------------------------
     * nethera  = ROLE_NETHERA  (Student / regular user)
     * vasiki   = ROLE_VASIKI   (Super Admin)
     * hakaes   = ROLE_HAKAES   (Teacher)
     * anubis   = ROLE_ANUBIS   (Moderator)
     *
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'vasiki' => [
            'title' => 'Vasiki',
            'description' => 'Super Admin — full control over all site features.',
        ],
        'hakaes' => [
            'title' => 'Hakaes',
            'description' => 'Teacher — manages classes, grades, quizzes, and materials.',
        ],
        'anubis' => [
            'title' => 'Anubis',
            'description' => 'Moderator — manages punishments and oversight.',
        ],
        'nethera' => [
            'title' => 'Nethera',
            'description' => 'Student — standard user with access to learning and game features.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * Mapped from existing route-level and controller-level checks.
     */
    public array $permissions = [
        'admin.access' => 'Can access the admin dashboard',
        'admin.nethera' => 'Can manage Nethera users',
        'admin.classes' => 'Can manage classes, grades, and schedules',
        'academic.grades' => 'Can edit student grades',
        'academic.materials' => 'Can manage learning materials',
        'academic.quizzes' => 'Can create and manage quizzes',
        'punishment.manage' => 'Can issue and manage punishments',
        'leaderboard.archive' => 'Can archive leaderboard seasons',
        'rhythm.import' => 'Can import osu! beatmaps',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups based on existing route filters.
     */
    public array $matrix = [
        'vasiki' => [
            'admin.*',
            'academic.*',
            'punishment.*',
            'leaderboard.*',
            'rhythm.*',
        ],
        'hakaes' => [
            'admin.access',
            'admin.classes',
            'academic.grades',
            'academic.materials',
            'academic.quizzes',
        ],
        'anubis' => [
            'admin.access',
            'punishment.manage',
        ],
        'nethera' => [],
    ];
}
