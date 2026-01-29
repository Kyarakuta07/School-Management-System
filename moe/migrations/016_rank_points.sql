-- Migration: Add Rank Points System to user_pets
-- Version: 016_rank_points.sql
-- Description: Adds rank_points column for ELO-based competitive ranking
-- Compatible with MySQL 5.7+

-- Add rank_points column (Starting at 1000 = Silver Tier)
-- Using stored procedure to safely add columns if they don't exist

DELIMITER //

DROP PROCEDURE IF EXISTS add_rank_columns//

CREATE PROCEDURE add_rank_columns()
BEGIN
    -- Add rank_points if not exists
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'user_pets' 
        AND COLUMN_NAME = 'rank_points'
    ) THEN
        ALTER TABLE user_pets ADD COLUMN rank_points INT DEFAULT 1000 NOT NULL;
    END IF;

    -- Add highest_rank if not exists
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'user_pets' 
        AND COLUMN_NAME = 'highest_rank'
    ) THEN
        ALTER TABLE user_pets ADD COLUMN highest_rank INT DEFAULT 1000 NOT NULL;
    END IF;

    -- Add attacker_rp_change to pet_battles if not exists
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'pet_battles' 
        AND COLUMN_NAME = 'attacker_rp_change'
    ) THEN
        ALTER TABLE pet_battles ADD COLUMN attacker_rp_change INT DEFAULT 0;
    END IF;

    -- Add defender_rp_change to pet_battles if not exists
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'pet_battles' 
        AND COLUMN_NAME = 'defender_rp_change'
    ) THEN
        ALTER TABLE pet_battles ADD COLUMN defender_rp_change INT DEFAULT 0;
    END IF;
END//

DELIMITER ;

-- Execute the procedure
CALL add_rank_columns();

-- Cleanup
DROP PROCEDURE IF EXISTS add_rank_columns;

-- Add index for fast leaderboard queries (ignore if exists)
CREATE INDEX idx_rank_points ON user_pets(rank_points DESC);

-- Update existing pets to have default 1000 RP if null
UPDATE user_pets SET rank_points = 1000 WHERE rank_points IS NULL OR rank_points = 0;
UPDATE user_pets SET highest_rank = 1000 WHERE highest_rank IS NULL OR highest_rank = 0;
