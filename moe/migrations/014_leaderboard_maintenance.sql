-- =====================================================
-- Migration: 014_leaderboard_maintenance.sql
-- Description: Add columns for leaderboard resets and history cleanup
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- Add total_wins column to user_pets for persistent tracking
-- This allows us to safely delete old history while keeping accurate stats
ALTER TABLE user_pets
ADD COLUMN total_wins INT DEFAULT 0 COMMENT 'All-time wins for this pet';

-- Backfill total_wins from existing pet_battles data
UPDATE user_pets up
SET total_wins = (
    SELECT COUNT(*)
    FROM pet_battles pb
    WHERE pb.winner_pet_id = up.id
);

-- Add index for faster leaderboard queries
ALTER TABLE pet_battles
ADD INDEX idx_created_at (created_at);

-- Add index for monthly leaderboard filter
ALTER TABLE pet_battles
ADD INDEX idx_winner_created (winner_pet_id, created_at);
