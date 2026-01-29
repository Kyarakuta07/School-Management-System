-- Migration: 015_leaderboard_history.sql
-- Stores past monthly leaderboard winners for Hall of Fame

CREATE TABLE IF NOT EXISTS leaderboard_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_year VARCHAR(20) NOT NULL COMMENT 'Format: YYYY-MM or Month Year',
    `rank` TINYINT NOT NULL DEFAULT 1,
    pet_id INT NOT NULL,
    sort_type ENUM('level', 'wins', 'power') NOT NULL DEFAULT 'level',
    score INT NOT NULL DEFAULT 0 COMMENT 'The value they ranked by (level, wins, or power)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_month (month_year),
    INDEX idx_rank (`rank`),
    FOREIGN KEY (pet_id) REFERENCES user_pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add current_streak column to user_pets (ignore error if exists)
-- Run each statement separately in phpMyAdmin or terminal

ALTER TABLE user_pets ADD COLUMN current_streak INT DEFAULT 0;
-- If error "Duplicate column name", that's OK - column exists

ALTER TABLE user_pets ADD COLUMN total_losses INT DEFAULT 0;
-- If error "Duplicate column name", that's OK - column exists

ALTER TABLE pet_species ADD COLUMN base_health INT DEFAULT 100;
-- If error "Duplicate column name", that's OK - column exists
