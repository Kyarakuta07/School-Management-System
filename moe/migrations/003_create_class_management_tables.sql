-- =====================================================
-- Migration: 003_create_class_management_tables.sql
-- Description: Class grades, schedules, and admin logs
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- 1. Class Grades Table
CREATE TABLE IF NOT EXISTS `class_grades` (
    `id_grade` INT PRIMARY KEY AUTO_INCREMENT,
    `id_nethera` INT NOT NULL,
    `class_name` VARCHAR(100) NOT NULL COMMENT 'e.g., PP KHONSU #1',
    `english` INT DEFAULT 0,
    `herbology` INT DEFAULT 0,
    `oceanology` INT DEFAULT 0,
    `astronomy` INT DEFAULT 0,
    `total_pp` INT DEFAULT 0 COMMENT 'Total Performance Points',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_nethera` (`id_nethera`),
    KEY `idx_class` (`class_name`),
    
    CONSTRAINT `fk_grades_nethera` 
        FOREIGN KEY (`id_nethera`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Class Schedule Table
CREATE TABLE IF NOT EXISTS `class_schedule` (
    `id_schedule` INT PRIMARY KEY AUTO_INCREMENT,
    `class_name` VARCHAR(100) NOT NULL,
    `hakaes_name` VARCHAR(100) NOT NULL COMMENT 'Teacher/instructor name',
    `schedule_day` VARCHAR(50) NOT NULL COMMENT 'Senin, Selasa, etc.',
    `schedule_time` VARCHAR(50) NOT NULL COMMENT 'e.g., 19:00 WIB',
    `class_image_url` VARCHAR(255) DEFAULT NULL,
    `class_description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Punishment Log Table
CREATE TABLE IF NOT EXISTS `punishment_log` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `violation_type` VARCHAR(100) NOT NULL,
    `punishment` TEXT NOT NULL,
    `given_by` INT DEFAULT NULL COMMENT 'Staff/admin ID',
    `status` ENUM('Active', 'Completed', 'Waived') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    
    KEY `idx_student` (`student_id`),
    KEY `idx_status` (`status`),
    
    CONSTRAINT `fk_punishment_student` 
        FOREIGN KEY (`student_id`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Admin Activity Log Table
CREATE TABLE IF NOT EXISTS `admin_activity_log` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `admin_id` INT NOT NULL,
    `admin_username` VARCHAR(100) NOT NULL,
    `action` ENUM('CREATE', 'UPDATE', 'DELETE') NOT NULL,
    `entity` VARCHAR(50) NOT NULL COMMENT 'nethera, grade, schedule, etc.',
    `entity_id` INT DEFAULT NULL,
    `description` TEXT,
    `changes` JSON DEFAULT NULL COMMENT 'Before/after changes',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY `idx_admin` (`admin_id`),
    KEY `idx_entity` (`entity`, `entity_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Trapeza (Banking) Transactions Table
CREATE TABLE IF NOT EXISTS `trapeza_transactions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `amount` INT NOT NULL,
    `transaction_type` ENUM('transfer', 'purchase', 'reward', 'penalty') DEFAULT 'transfer',
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY `idx_sender` (`sender_id`),
    KEY `idx_receiver` (`receiver_id`),
    
    CONSTRAINT `fk_trapeza_sender` 
        FOREIGN KEY (`sender_id`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_trapeza_receiver` 
        FOREIGN KEY (`receiver_id`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
