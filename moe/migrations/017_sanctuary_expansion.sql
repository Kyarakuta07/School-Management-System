-- =====================================================
-- Migration: 017_sanctuary_expansion.sql
-- Description: Sanctuary features expansion + EXP Potion buff
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- =====================
-- SANCTUARY GOLD COLUMN
-- =====================
ALTER TABLE `sanctuary` ADD COLUMN IF NOT EXISTS `gold` INT NOT NULL DEFAULT 0;

-- =====================
-- SANCTUARY UPGRADES TABLE
-- =====================
CREATE TABLE IF NOT EXISTS `sanctuary_upgrades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sanctuary_id` INT NOT NULL,
    `upgrade_type` ENUM('training_dummy', 'beastiary', 'crystal_vault') NOT NULL,
    `level` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_upgrade` (`sanctuary_id`, `upgrade_type`),
    FOREIGN KEY (`sanctuary_id`) REFERENCES `sanctuary`(`id_sanctuary`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================
-- SANCTUARY DAILY CLAIMS TABLE
-- =====================
CREATE TABLE IF NOT EXISTS `sanctuary_daily_claims` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `sanctuary_id` INT NOT NULL,
    `last_claim` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_sanctuary` (`user_id`, `sanctuary_id`),
    FOREIGN KEY (`user_id`) REFERENCES `nethera`(`id_nethera`) ON DELETE CASCADE,
    FOREIGN KEY (`sanctuary_id`) REFERENCES `sanctuary`(`id_sanctuary`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================
-- UPDATE EXP POTION VALUE (100 -> 200)
-- =====================
UPDATE `shop_items` SET `effect_value` = 200 WHERE `effect_type` = 'exp_boost';

-- =====================================================
-- END OF MIGRATION
-- =====================================================
