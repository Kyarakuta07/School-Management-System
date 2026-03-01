<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * All service registrations now point to Module namespaces directly,
 * eliminating the extra stub-inheritance layer.
 */
class Services extends BaseService
{
    public static function achievementService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('achievementService');
        return new \App\Modules\Sanctuary\Services\AchievementService(\Config\Database::connect());
    }

    public static function activityLog($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('activityLog');
        return new \App\Modules\User\Services\ActivityLogService();
    }

    public static function arena1v1Service($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('arena1v1Service');
        return new \App\Modules\Battle\Services\Arena1v1Service(\Config\Database::connect());
    }

    public static function battle1v1Engine($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('battle1v1Engine');
        return new \App\Modules\Battle\Engines\Battle1v1Engine();
    }

    public static function battle3v3Engine($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('battle3v3Engine');
        return new \App\Modules\Battle\Engines\Battle3v3Engine();
    }

    public static function battleRepository($db = null, $getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('battleRepository', $db);
        return new \App\Modules\Battle\Repositories\BattleRepository($db ?? \Config\Database::connect());
    }

    public static function arena3v3Service($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('arena3v3Service');
        return new \App\Modules\Battle\Services\Arena3v3Service(\Config\Database::connect());
    }

    public static function authService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('authService');
        return new \App\Modules\Auth\Services\AuthService();
    }

    public static function evolutionService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('evolutionService');
        return new \App\Modules\Pet\Services\EvolutionService(\Config\Database::connect());
    }

    public static function gachaService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('gachaService');
        return new \App\Modules\Pet\Services\GachaService(\Config\Database::connect());
    }

    public static function goldService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('goldService');
        return new \App\Modules\User\Services\GoldService(\Config\Database::connect());
    }

    public static function itemService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('itemService');
        return new \App\Modules\Pet\Services\ItemService(\Config\Database::connect());
    }

    public static function rewardDistributor($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('rewardDistributor');
        return new \App\Modules\Sanctuary\Services\RewardDistributor(\Config\Database::connect());
    }

    public static function rewardService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('rewardService');
        return new \App\Modules\Sanctuary\Services\RewardService(\Config\Database::connect());
    }

    public static function sanctuaryService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('sanctuaryService');
        return new \App\Modules\Sanctuary\Services\SanctuaryService(\Config\Database::connect());
    }

    public static function shopService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('shopService');
        return new \App\Modules\Pet\Services\ShopService(\Config\Database::connect());
    }

    public static function sanctuaryWarService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('sanctuaryWarService');
        return new \App\Modules\Battle\Services\SanctuaryWarService();
    }

    public static function petService($getShared = true)
    {
        if ($getShared)
            return static::getSharedInstance('petService');
        return new \App\Modules\Pet\Services\PetService(\Config\Database::connect());
    }
}
