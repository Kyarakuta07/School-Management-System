-- =====================================================
-- Migration: 012_status_effects.sql
-- Description: Add status effects support to pet skills
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- Add status effect columns to pet_skills
-- Note: Run each ALTER separately if column already exists
ALTER TABLE pet_skills 
ADD COLUMN `status_effect` VARCHAR(20) DEFAULT NULL COMMENT 'burn, poison, freeze, stun, atk_down, def_down',
ADD COLUMN `status_chance` INT DEFAULT 0 COMMENT 'Chance 0-100%',
ADD COLUMN `status_duration` INT DEFAULT 0 COMMENT 'Duration in turns';

-- Add skill_element if not exists (may already exist)
-- Run this separately if it fails
-- ALTER TABLE pet_skills ADD COLUMN `skill_element` VARCHAR(20) DEFAULT NULL COMMENT 'Element for skill';

-- Add index for status effect lookups (ignore if already exists)
-- ALTER TABLE pet_skills ADD INDEX `idx_status_effect` (`status_effect`);

-- =====================================================
-- Element Resistance Table
-- Defines which elements resist which status effects
-- =====================================================
CREATE TABLE IF NOT EXISTS `element_status_resistance` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `element` ENUM('Fire', 'Water', 'Earth', 'Air', 'Light', 'Dark') NOT NULL,
    `resists_status` VARCHAR(20) NOT NULL COMMENT 'Status effect this element resists',
    `resistance_percent` INT DEFAULT 50 COMMENT 'Reduction in application chance',
    
    UNIQUE KEY `unique_element_status` (`element`, `resists_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert element resistances
INSERT INTO element_status_resistance (element, resists_status, resistance_percent) VALUES
-- Fire element resists Burn and Freeze
('Fire', 'burn', 100),      -- Immune to burn
('Fire', 'freeze', 75),     -- 75% less chance to freeze

-- Water element resists Burn
('Water', 'burn', 75),      -- 75% less chance to burn

-- Earth element resists Stun and Poison
('Earth', 'stun', 50),      -- 50% less chance to stun
('Earth', 'poison', 50),    -- 50% less chance to poison

-- Air element resists Stun
('Air', 'stun', 75),        -- 75% less chance to stun

-- Light element resists ATK/DEF debuffs
('Light', 'atk_down', 50),  -- 50% less chance
('Light', 'def_down', 50),  -- 50% less chance

-- Dark element resists Poison
('Dark', 'poison', 75)      -- 75% less chance to poison
ON DUPLICATE KEY UPDATE resistance_percent = VALUES(resistance_percent);

-- =====================================================
-- Sample skills with status effects (OPTIONAL)
-- Run these manually after checking your table structure
-- Change 'element_type' to your actual column name if different
-- =====================================================

-- Commented out - run manually if needed:
-- UPDATE pet_skills 
-- SET status_effect = 'burn', status_chance = 25, status_duration = 3
-- WHERE element_type = 'Fire' AND base_damage >= 40 AND status_effect IS NULL
-- LIMIT 5;

-- UPDATE pet_skills 
-- SET status_effect = 'freeze', status_chance = 15, status_duration = 1
-- WHERE element_type = 'Water' AND base_damage >= 50 AND status_effect IS NULL
-- LIMIT 3;

-- UPDATE pet_skills 
-- SET status_effect = 'poison', status_chance = 30, status_duration = 4
-- WHERE element_type = 'Dark' AND base_damage >= 30 AND status_effect IS NULL
-- LIMIT 5;

-- UPDATE pet_skills 
-- SET status_effect = 'stun', status_chance = 20, status_duration = 1
-- WHERE element_type = 'Air' AND base_damage >= 45 AND status_effect IS NULL
-- LIMIT 3;
