-- =====================================================
-- Migration: 006_class_materials.sql
-- Description: Class materials system for subject content
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- Class Materials Table
CREATE TABLE IF NOT EXISTS `class_materials` (
    `id_material` INT PRIMARY KEY AUTO_INCREMENT,
    `subject` VARCHAR(50) NOT NULL COMMENT 'english, herbology, oceanology, astronomy',
    `title` VARCHAR(200) NOT NULL,
    `material_type` ENUM('text', 'youtube', 'pdf') NOT NULL DEFAULT 'text',
    `content` TEXT COMMENT 'For text: HTML content, For youtube: video ID or URL',
    `file_path` VARCHAR(255) NULL COMMENT 'For PDF: file path',
    `created_by` INT NOT NULL COMMENT 'Hakaes id_nethera who created this',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_subject` (`subject`),
    KEY `idx_type` (`material_type`),
    KEY `idx_created_by` (`created_by`),
    
    CONSTRAINT `fk_material_creator` 
        FOREIGN KEY (`created_by`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Materials (uncomment to use)
-- =====================================================
-- INSERT INTO class_materials (subject, title, material_type, content, created_by) VALUES
-- ('english', 'Introduction to English Literature', 'text', '<p>Welcome to English Studies...</p>', 1),
-- ('english', 'Shakespeare Overview', 'youtube', 'dQw4w9WgXcQ', 1),
-- ('herbology', 'Basics of Mediterranean Herbs', 'text', '<p>Herbs have been used since ancient times...</p>', 1);
