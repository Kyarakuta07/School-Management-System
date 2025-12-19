-- =====================================================
-- Migration: 001_create_core_tables.sql
-- Description: Core tables for user management
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- 1. Sanctuary Table (Foreign key reference for users)
CREATE TABLE IF NOT EXISTS `sanctuary` (
    `id_sanctuary` INT PRIMARY KEY AUTO_INCREMENT,
    `nama_sanctuary` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Nethera (Users) Table
CREATE TABLE IF NOT EXISTS `nethera` (
    `id_nethera` INT PRIMARY KEY AUTO_INCREMENT,
    `no_registrasi` VARCHAR(20) DEFAULT NULL COMMENT 'Format: AMMIT_1_17',
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `username` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) NOT NULL,
    `noHP` VARCHAR(100) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed',
    `reset_token` VARCHAR(255) DEFAULT NULL,
    `token_expires` DATETIME DEFAULT NULL,
    `id_sanctuary` INT DEFAULT NULL,
    `periode_masuk` INT DEFAULT NULL,
    `status_akun` ENUM('Aktif', 'Pending', 'Out', 'Hiatus', 'Tidak Lulus') DEFAULT 'Pending',
    `otp_code` VARCHAR(6) DEFAULT NULL,
    `otp_expires` DATETIME DEFAULT NULL,
    `email_verified_at` DATETIME DEFAULT NULL,
    `role` ENUM('Nethera', 'Vasiki') DEFAULT 'Nethera' COMMENT 'Nethera=student, Vasiki=staff',
    `tanggal_lahir` DATE DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `fun_fact` TEXT DEFAULT NULL,
    `profile_photo` VARCHAR(255) DEFAULT NULL,
    `gold` INT DEFAULT 500 COMMENT 'In-game currency',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `unique_username` (`username`),
    UNIQUE KEY `unique_email` (`email`),
    KEY `idx_status` (`status_akun`),
    KEY `idx_role` (`role`),
    KEY `idx_sanctuary` (`id_sanctuary`),
    
    CONSTRAINT `fk_nethera_sanctuary` 
        FOREIGN KEY (`id_sanctuary`) REFERENCES `sanctuary`(`id_sanctuary`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Rate Limits Table (Security)
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `identifier` VARCHAR(255) NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `attempts` INT DEFAULT 1,
    `first_attempt` DATETIME NOT NULL,
    `last_attempt` DATETIME NOT NULL,
    
    UNIQUE KEY `unique_rate` (`identifier`, `action_type`),
    KEY `idx_cleanup` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default sanctuaries
INSERT INTO `sanctuary` (`nama_sanctuary`, `description`) VALUES
    ('Ammit', 'House of the Devourer'),
    ('Khonsu', 'House of the Moon God'),
    ('Hathor', 'House of Love and Beauty'),
    ('Osiris', 'House of the Underworld'),
    ('Sekhmet', 'House of the Warrior')
ON DUPLICATE KEY UPDATE `nama_sanctuary` = VALUES(`nama_sanctuary`);
