-- =====================================================
-- Migration: 005_hakaes_role.sql
-- Description: Add Hakaes (Teacher) role support
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- NOTE: Role column in `nethera` table should already be VARCHAR
-- from migration 004. If not, run this:
-- ALTER TABLE `nethera` MODIFY COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'Nethera';

-- Add id_hakaes column to class_schedule (links schedule to teacher)
ALTER TABLE `class_schedule` 
ADD COLUMN `id_hakaes` INT NULL COMMENT 'Teacher/Hakaes who teaches this class' AFTER `hakaes_name`,
ADD CONSTRAINT `fk_schedule_hakaes` 
    FOREIGN KEY (`id_hakaes`) REFERENCES `nethera`(`id_nethera`) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Create index for teacher lookup
CREATE INDEX `idx_hakaes` ON `class_schedule`(`id_hakaes`);

-- =====================================================
-- Sample Hakaes user (uncomment and modify to use)
-- =====================================================
-- INSERT INTO nethera (nama_lengkap, username, email, password, role, status_akun, id_sanctuary)
-- VALUES ('Guru Contoh', 'hakaes', 'hakaes@moe.edu', 
--         '$2y$10$YourHashedPasswordHere', 'Hakaes', 'Aktif', 1);

-- =====================================================
-- Grant existing schedule to Hakaes (if needed)
-- =====================================================
-- UPDATE class_schedule SET id_hakaes = (SELECT id_nethera FROM nethera WHERE role = 'Hakaes' LIMIT 1);
