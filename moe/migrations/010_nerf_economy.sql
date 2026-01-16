-- =====================================================
-- Migration: 010_nerf_economy.sql
-- Description: Hardcore economy rebalancing - nerf achievement rewards
-- Mediterranean of Egypt - School Management System
-- Created: 2026-01-13
-- =====================================================

-- =====================
-- NERF ACHIEVEMENT GOLD REWARDS
-- =====================

-- Collection achievements
UPDATE achievements SET reward_gold = 10 WHERE name = 'First Friend';        -- Was 50
UPDATE achievements SET reward_gold = 25 WHERE name = 'Pet Collector';       -- Was 150
UPDATE achievements SET reward_gold = 50 WHERE name = 'Master Collector';    -- Was 300

-- Rarity achievements
UPDATE achievements SET reward_gold = 30 WHERE name = 'Shiny Hunter';        -- Was 200
UPDATE achievements SET reward_gold = 10 WHERE name = 'Rare Find';           -- Was 50
UPDATE achievements SET reward_gold = 20 WHERE name = 'Epic Discovery';      -- Was 100
UPDATE achievements SET reward_gold = 75 WHERE name = 'Legendary Owner';     -- Was 500

-- Battle achievements
UPDATE achievements SET reward_gold = 10 WHERE name = 'First Victory';       -- Was 50
UPDATE achievements SET reward_gold = 25 WHERE name = 'Battle Veteran';      -- Was 150
UPDATE achievements SET reward_gold = 75 WHERE name = 'Battle Legend';       -- Was 500

-- Level achievements
UPDATE achievements SET reward_gold = 10 WHERE name = 'Growing Up';          -- Was 50
UPDATE achievements SET reward_gold = 50 WHERE name = 'Seasoned Trainer';    -- Was 300
UPDATE achievements SET reward_gold = 150 WHERE name = 'Max Power';          -- Was 1000

-- Login achievements
UPDATE achievements SET reward_gold = 15 WHERE name = 'Daily Visitor';       -- Was 100
UPDATE achievements SET reward_gold = 35 WHERE name = 'Dedicated Player';    -- Was 200

-- =====================================================
-- VERIFICATION
-- =====================================================
-- Run: SELECT name, reward_gold FROM achievements;

-- =====================================================
-- ADD CLAIMED COLUMN TO USER_ACHIEVEMENTS
-- =====================================================
-- This column tracks whether the user has collected the reward
-- NOTE: If you get "Duplicate column name" error, the column already exists - safe to ignore

ALTER TABLE user_achievements ADD COLUMN claimed TINYINT(1) DEFAULT 0;

-- Set existing unlocked achievements as already claimed (to avoid giving free gold)
UPDATE user_achievements SET claimed = 1 WHERE unlocked_at IS NOT NULL;
