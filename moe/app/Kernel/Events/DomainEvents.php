<?php

namespace App\Kernel\Events;

use CodeIgniter\Events\Events;

/**
 * DomainEvents — Kernel-level event definitions for cross-domain decoupling.
 *
 * Modules fire events here instead of importing from other modules.
 * Listeners are registered in App\Config\Events.php.
 */
class DomainEvents
{
    /**
     * Fire when arena rankings change (battle result recorded).
     * Listeners: LeaderboardModel::invalidateCache()
     */
    public static function arenaRankingsChanged(): void
    {
        Events::trigger('arena.rankings.changed');
    }

    /**
     * Fire when a battle concludes (win or loss).
     * Listeners: AchievementService::triggerCheck()
     */
    public static function battleCompleted(int $userId, bool $isWin): void
    {
        Events::trigger('battle.completed', $userId, $isWin);
    }

    /**
     * Fire when a pet levels up.
     */
    public static function petLevelUp(int $userId, int $petId, int $newLevel): void
    {
        Events::trigger('pet.level.up', $userId, $petId, $newLevel);
    }

    /**
     * Fire when gold balance changes.
     */
    public static function goldChanged(int $userId, int $amount, string $type): void
    {
        Events::trigger('gold.changed', $userId, $amount, $type);
    }
}
