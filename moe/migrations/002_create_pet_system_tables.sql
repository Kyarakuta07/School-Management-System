-- =====================================================
-- Migration: 002_create_pet_system_tables.sql
-- Description: Pet system tables (species, pets, skills, shop)
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- 1. Pet Species Table (Master Data)
CREATE TABLE IF NOT EXISTS `pet_species` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `element` ENUM('Fire', 'Water', 'Earth', 'Air', 'Light', 'Dark') NOT NULL,
    `rarity` ENUM('Common', 'Rare', 'Epic', 'Legendary') NOT NULL,
    `base_attack` INT DEFAULT 10,
    `base_defense` INT DEFAULT 10,
    `base_speed` INT DEFAULT 10,
    `img_egg` VARCHAR(255) DEFAULT NULL,
    `img_baby` VARCHAR(255) DEFAULT NULL,
    `img_adult` VARCHAR(255) DEFAULT NULL,
    `passive_buff_type` VARCHAR(50) DEFAULT NULL,
    `passive_buff_value` INT DEFAULT 0,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY `idx_element` (`element`),
    KEY `idx_rarity` (`rarity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Pets Table (Pet Instances)
CREATE TABLE IF NOT EXISTS `user_pets` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `species_id` INT NOT NULL,
    `nickname` VARCHAR(50) DEFAULT NULL,
    `level` INT DEFAULT 1,
    `exp` INT DEFAULT 0,
    `evolution_stage` ENUM('egg', 'baby', 'adult') DEFAULT 'egg',
    `health` INT DEFAULT 100,
    `hunger` INT DEFAULT 100,
    `mood` INT DEFAULT 100,
    `status` ENUM('ALIVE', 'DEAD', 'SHELTER') DEFAULT 'ALIVE',
    `is_active` TINYINT(1) DEFAULT 0,
    `is_shiny` TINYINT(1) DEFAULT 0,
    `shiny_hue` INT DEFAULT 0 COMMENT 'HSL hue for shiny (30-330)',
    `has_shield` TINYINT(1) DEFAULT 0,
    `last_update_timestamp` INT DEFAULT NULL COMMENT 'Unix timestamp for lazy stat calc',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY `idx_user_active` (`user_id`, `is_active`),
    KEY `idx_status` (`status`),
    KEY `idx_species` (`species_id`),
    
    CONSTRAINT `fk_userpets_user` 
        FOREIGN KEY (`user_id`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_userpets_species` 
        FOREIGN KEY (`species_id`) REFERENCES `pet_species`(`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Pet Skills Table
CREATE TABLE IF NOT EXISTS `pet_skills` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `species_id` INT NOT NULL,
    `skill_name` VARCHAR(100) NOT NULL,
    `skill_slot` INT DEFAULT 1 COMMENT '1-4',
    `base_damage` INT DEFAULT 10,
    `element_type` VARCHAR(20) DEFAULT NULL,
    `effect_type` ENUM('damage', 'heal', 'buff', 'debuff') DEFAULT 'damage',
    `effect_value` INT DEFAULT 0,
    
    KEY `idx_species` (`species_id`),
    CONSTRAINT `fk_skills_species` 
        FOREIGN KEY (`species_id`) REFERENCES `pet_species`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Shop Items Table (Master Data)
CREATE TABLE IF NOT EXISTS `shop_items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `category` ENUM('food', 'potion', 'gacha', 'special') NOT NULL,
    `effect_type` VARCHAR(50) COMMENT 'food, potion, revive, gacha_ticket, exp_boost, shield',
    `effect_value` INT DEFAULT 0,
    `price` INT NOT NULL,
    `icon` VARCHAR(255) DEFAULT NULL,
    `is_purchasable` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. User Inventory Table
CREATE TABLE IF NOT EXISTS `user_inventory` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `quantity` INT DEFAULT 1,
    `obtained_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `idx_user_item` (`user_id`, `item_id`),
    
    CONSTRAINT `fk_inventory_user` 
        FOREIGN KEY (`user_id`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_inventory_item` 
        FOREIGN KEY (`item_id`) REFERENCES `shop_items`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Pet Battles Log
CREATE TABLE IF NOT EXISTS `pet_battles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `attacker_pet_id` INT NOT NULL,
    `defender_pet_id` INT NOT NULL,
    `winner_pet_id` INT DEFAULT NULL,
    `battle_type` ENUM('1v1', '3v3') DEFAULT '1v1',
    `battle_log` JSON DEFAULT NULL,
    `attacker_exp_gained` INT DEFAULT 0,
    `defender_exp_gained` INT DEFAULT 0,
    `gold_reward` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY `idx_attacker` (`attacker_pet_id`),
    KEY `idx_defender` (`defender_pet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Daily Login Streak
CREATE TABLE IF NOT EXISTS `daily_login_streak` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `current_day` INT DEFAULT 1 COMMENT 'Day 1-7',
    `last_claim_date` DATE DEFAULT NULL,
    `total_logins` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `unique_user` (`user_id`),
    
    CONSTRAINT `fk_streak_user` 
        FOREIGN KEY (`user_id`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Pet Evolution History
CREATE TABLE IF NOT EXISTS `pet_evolution_history` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `main_pet_id` INT NOT NULL,
    `fodder_pet_ids` TEXT COMMENT 'JSON array of sacrificed pet IDs',
    `gold_cost` INT DEFAULT 500,
    `evolved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY `idx_user` (`user_id`),
    KEY `idx_pet` (`main_pet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Achievements Table
CREATE TABLE IF NOT EXISTS `achievements` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `category` ENUM('collection', 'battle', 'level', 'gacha', 'login', 'special') NOT NULL,
    `icon` VARCHAR(50) DEFAULT '🏆',
    `rarity` ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    `requirement_type` VARCHAR(50) NOT NULL,
    `requirement_value` INT DEFAULT 1,
    `reward_gold` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. User Achievements (Unlocked)
CREATE TABLE IF NOT EXISTS `user_achievements` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `achievement_id` INT NOT NULL,
    `unlocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY `unique_user_achievement` (`user_id`, `achievement_id`),
    
    CONSTRAINT `fk_userachv_user` 
        FOREIGN KEY (`user_id`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_userachv_achv` 
        FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
