<?php

namespace App\Config;

/**
 * GameConfig — Centralized game constants.
 * Replaces magic numbers that were scattered across Controllers and Services.
 */
class GameConfig
{
    // ── Pet Evolution Level Caps ──
    // Must match EvolutionService thresholds: Egg→Baby at Lv.30, Baby→Adult at Lv.70
    public const PET_LEVEL_CAP_EGG = 30;
    public const PET_LEVEL_CAP_BABY = 70;
    public const PET_LEVEL_CAP_ADULT = 99;

    // ── Daily Reward ──
    public const DAILY_CLAIM_INTERVAL = 86400; // 24 hours in seconds

    // ── Auth / Security ──
    public const LOCKOUT_THRESHOLD = 5;    // Failed attempts before lockout
    public const LOCKOUT_MINUTES = 15;   // Lockout duration in minutes
    public const OTP_EXPIRY_SECONDS = 300; // 5 minutes

    // ── Pagination ──
    public const ADMIN_PER_PAGE = 15;
    public const PUNISHMENT_PER_PAGE = 20;
    public const HISTORY_PER_PAGE = 10;

    // ── War Rewards ──
    public const WAR_GOLD_WIN = 25;
    public const WAR_GOLD_LOSS = 5;
    public const WAR_POINTS_WIN = 3;
    public const WAR_POINTS_LOSS = 0;
    public const WAR_MAX_TICKETS = 3;

    // ── Quiz Rewards ──
    public const QUIZ_GOLD_PERFECT = 50;
    public const QUIZ_GOLD_PASS = 25;

    // ── Rhythm Game ──
    public const RHYTHM_MIN_DURATION_MS = 10000;  // 10 seconds minimum play
    public const RHYTHM_DAILY_PLAY_CAP = 10;     // Max rewarded plays per day
    public const RHYTHM_SCORE_GOLD_DIV = 10000;  // Score ÷ this = base gold
    public const RHYTHM_SCORE_EXP_DIV = 5000;   // Score ÷ this = base exp
    public const RHYTHM_BASE_REWARD = 3;      // Base reward added on top
    public const RHYTHM_MOOD_BOOST_WIN = 5;      // Pet mood boost for non-fail
    public const RHYTHM_MOOD_BOOST_FAIL = 1;      // Pet mood boost for fail
    public const RHYTHM_SCORE_SUBMIT_CD = 30;     // Seconds between score submissions

    // ── Battle ──
    public const BATTLE_DAILY_QUOTA = 5;      // Max battles per day

    // ── Pet Stage Names ──
    public static function getPetStageName(string $stage): string
    {
        switch ($stage) {
            case 'egg':
                return 'Egg';
            case 'baby':
                return 'Baby';
            case 'adult':
                return 'Adult';
            default:
                return ucfirst($stage);
        }
    }

    public static function getPetLevelCap(string $stage): int
    {
        switch ($stage) {
            case 'egg':
                return self::PET_LEVEL_CAP_EGG;
            case 'baby':
                return self::PET_LEVEL_CAP_BABY;
            case 'adult':
                return self::PET_LEVEL_CAP_ADULT;
            default:
                return self::PET_LEVEL_CAP_EGG;
        }
    }
}
