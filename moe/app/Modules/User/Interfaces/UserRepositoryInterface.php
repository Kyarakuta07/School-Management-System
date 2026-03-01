<?php

namespace App\Modules\User\Interfaces;

/**
 * UserRepositoryInterface — Contract for user data access.
 *
 * Cross-domain consumers should depend on this interface
 * instead of directly importing UserModel or UserRepository.
 */
interface UserRepositoryInterface
{
    /** Get user profile row with sanctuary and class info joined. */
    public function getUserProfile(int $userId): ?array;

    /** Get user with their active pet info. */
    public function getUserWithActivePet(int $userId): ?array;

    /** Get dashboard data (user + pet count + battle wins). */
    public function getDashboardData(int $userId): array;

    /** Search users by name/username, excluding a specific user. */
    public function searchUsersExcluding(string $query, int $excludeId, int $limit = 10): array;
}
