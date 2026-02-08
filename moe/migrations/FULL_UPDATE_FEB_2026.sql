-- ==========================================================
-- CONSOLIDATED MIGRATION - FEB 2026 UPDATE (SAFE MODE)
-- Run this in phpMyAdmin.
-- This script safely adds columns only if they don't exist.
-- ==========================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `UpgradeDatabaseFeb2026` $$

CREATE PROCEDURE `UpgradeDatabaseFeb2026`()
BEGIN
    -- 1. Add total_wins to user_pets
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_pets' AND COLUMN_NAME = 'total_wins') THEN
        ALTER TABLE user_pets ADD COLUMN total_wins INT DEFAULT 0;
    END IF;

    -- 2. Add current_streak to user_pets
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_pets' AND COLUMN_NAME = 'current_streak') THEN
        ALTER TABLE user_pets ADD COLUMN current_streak INT DEFAULT 0;
    END IF;

    -- 3. Add total_losses to user_pets
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_pets' AND COLUMN_NAME = 'total_losses') THEN
        ALTER TABLE user_pets ADD COLUMN total_losses INT DEFAULT 0;
    END IF;

    -- 4. Add rank_points to user_pets
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_pets' AND COLUMN_NAME = 'rank_points') THEN
        ALTER TABLE user_pets ADD COLUMN rank_points INT DEFAULT 1000;
        CREATE INDEX idx_rank_points ON user_pets(rank_points);
    END IF;

    -- 5. Add base_health to pet_species
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pet_species' AND COLUMN_NAME = 'base_health') THEN
        ALTER TABLE pet_species ADD COLUMN base_health INT DEFAULT 100;
    END IF;

    -- 6. Add gold to sanctuary
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sanctuary' AND COLUMN_NAME = 'gold') THEN
        ALTER TABLE sanctuary ADD COLUMN gold INT DEFAULT 0;
    END IF;

    -- 7. Create leaderboard_history table
    CREATE TABLE IF NOT EXISTS leaderboard_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        month_year VARCHAR(7) NOT NULL,
        rank INT NOT NULL,
        pet_id INT NOT NULL,
        sort_type VARCHAR(20) DEFAULT 'rank',
        score BIGINT DEFAULT 0,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_history_month (month_year)
    );

    -- 8. Create sanctuary_upgrades table
    CREATE TABLE IF NOT EXISTS sanctuary_upgrades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sanctuary_id INT NOT NULL,
        upgrade_type VARCHAR(50) NOT NULL,
        level INT DEFAULT 1,
        purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sanctuary_id) REFERENCES sanctuary(id_sanctuary) ON DELETE CASCADE
    );

    -- 9. Create sanctuary_daily_claims table
    CREATE TABLE IF NOT EXISTS sanctuary_daily_claims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        sanctuary_id INT NOT NULL,
        last_claim DATETIME DEFAULT NULL,
        UNIQUE KEY unique_claim (user_id, sanctuary_id)
    );

    -- 10. Indexes (Safe to try, will fail silently or succeed)
    IF NOT EXISTS (SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pet_battles' AND INDEX_NAME = 'idx_pet_battles_created_at') THEN
        CREATE INDEX idx_pet_battles_created_at ON pet_battles(created_at);
    END IF;

    IF NOT EXISTS (SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pet_battles' AND INDEX_NAME = 'idx_pet_battles_winner') THEN
        CREATE INDEX idx_pet_battles_winner ON pet_battles(winner_pet_id);
    END IF;
    
    -- 11. Update Items
    UPDATE items SET effect_value = 200 WHERE item_name LIKE '%Potion%';

END $$

DELIMITER ;

-- Run the procedure
CALL UpgradeDatabaseFeb2026();

-- Cleanup
DROP PROCEDURE IF EXISTS UpgradeDatabaseFeb2026;
