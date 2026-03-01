<?php

namespace App\Modules\Sanctuary\Interfaces;

use CodeIgniter\Database\ConnectionInterface;

/**
 * SanctuaryServiceInterface — Contract for sanctuary operations.
 *
 * Cross-domain consumers (Battle/war, Social/guild) should depend
 * on this interface instead of importing SanctuaryService directly.
 */
interface SanctuaryServiceInterface
{
    /** Get upgrade configuration (cost, description, icon). */
    public function getUpgradeConfig(): array;

    /** Validate and process upgrade purchase. */
    public function purchaseUpgrade(int $sanctuaryId, string $upgradeType, int $currentGold, array $existingUpgrades): array;

    /** Check if user can claim daily reward. */
    public function canClaimDaily(int $userId, int $sanctuaryId): array;

    /** Calculate daily reward based on upgrades and pet happiness. */
    public function calculateDailyReward(array $upgrades, ?array $activePet): array;

    /** Process daily claim (update DB, grant rewards). */
    public function processDailyClaim(int $userId, int $sanctuaryId, array $upgrades, ?array $activePet): array;

    /** Validate and process donation to sanctuary treasury. */
    public function processDonation(int $userId, int $sanctuaryId, int $amount, int $userGold, int $sanctuaryGold): array;
}
