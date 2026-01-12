-- =====================================================
-- Migration: 004_anubis_punishment_system.sql
-- Description: Add Anubis role and update punishment system
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- 1. Add 'Anubis' to role column (if using ENUM, need to modify)
-- If role is VARCHAR, just INSERT user with role='Anubis'
-- Check and update column type if needed:
ALTER TABLE `nethera` 
MODIFY COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'Nethera';

-- 2. Drop and recreate punishment_log with updated schema
DROP TABLE IF EXISTS `punishment_log`;

CREATE TABLE IF NOT EXISTS `punishment_log` (
    `id_punishment` INT PRIMARY KEY AUTO_INCREMENT,
    `id_nethera` INT NOT NULL COMMENT 'Punished user',
    `jenis_pelanggaran` VARCHAR(200) NOT NULL COMMENT 'Violation type',
    `deskripsi_pelanggaran` TEXT COMMENT 'Detailed description',
    `jenis_hukuman` VARCHAR(200) NOT NULL COMMENT 'Punishment type',
    `poin_pelanggaran` INT DEFAULT 0 COMMENT 'Penalty points',
    `status_hukuman` ENUM('active', 'completed', 'waived') DEFAULT 'active',
    `tanggal_pelanggaran` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'When violation occurred',
    `tanggal_selesai` DATETIME NULL COMMENT 'When punishment ended (manual release)',
    `locked_features` VARCHAR(255) DEFAULT 'trapeza,pet,class' COMMENT 'Comma-separated locked features',
    `given_by` INT DEFAULT NULL COMMENT 'Anubis/Admin who gave punishment',
    `released_by` INT DEFAULT NULL COMMENT 'Who released the punishment',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_nethera` (`id_nethera`),
    KEY `idx_status` (`status_hukuman`),
    KEY `idx_given_by` (`given_by`),
    
    CONSTRAINT `fk_punishment_nethera` 
        FOREIGN KEY (`id_nethera`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insert sample Anubis user (for testing)
-- Change password and details as needed
-- INSERT INTO `nethera` (nama_lengkap, username, email, password, role, status_akun, id_sanctuary)
-- VALUES ('Anubis Guardian', 'anubis', 'anubis@moe.edu', '$2y$10$...hashedpassword...', 'Anubis', 'Aktif', 1);
