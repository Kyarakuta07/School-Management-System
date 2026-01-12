-- =====================================================
-- Migration: 007_class_quizzes.sql
-- Description: Quiz/Exam system for subjects
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- Quiz Header Table
CREATE TABLE IF NOT EXISTS `class_quizzes` (
    `id_quiz` INT PRIMARY KEY AUTO_INCREMENT,
    `subject` VARCHAR(50) NOT NULL COMMENT 'english, herbology, oceanology, astronomy',
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `time_limit` INT DEFAULT 30 COMMENT 'Time limit in minutes',
    `passing_score` INT DEFAULT 70 COMMENT 'Minimum passing percentage',
    `max_attempts` INT DEFAULT 1 COMMENT 'Maximum attempts allowed per student',
    `status` ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    `created_by` INT NOT NULL COMMENT 'Hakaes id_nethera',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_subject` (`subject`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`),
    
    CONSTRAINT `fk_quiz_creator` 
        FOREIGN KEY (`created_by`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz Questions Table (Multiple Choice)
CREATE TABLE IF NOT EXISTS `quiz_questions` (
    `id_question` INT PRIMARY KEY AUTO_INCREMENT,
    `id_quiz` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `option_a` VARCHAR(500) NOT NULL,
    `option_b` VARCHAR(500) NOT NULL,
    `option_c` VARCHAR(500) NOT NULL,
    `option_d` VARCHAR(500) NOT NULL,
    `correct_answer` CHAR(1) NOT NULL COMMENT 'a, b, c, or d',
    `points` INT DEFAULT 10 COMMENT 'Points for correct answer',
    `order_num` INT DEFAULT 0 COMMENT 'Question display order',
    
    KEY `idx_quiz` (`id_quiz`),
    
    CONSTRAINT `fk_question_quiz` 
        FOREIGN KEY (`id_quiz`) REFERENCES `class_quizzes`(`id_quiz`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz Attempts Table
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
    `id_attempt` INT PRIMARY KEY AUTO_INCREMENT,
    `id_quiz` INT NOT NULL,
    `id_nethera` INT NOT NULL,
    `score` INT DEFAULT 0 COMMENT 'Total score achieved',
    `max_score` INT DEFAULT 0 COMMENT 'Maximum possible score',
    `percentage` DECIMAL(5,2) DEFAULT 0.00,
    `passed` BOOLEAN DEFAULT FALSE,
    `answers` JSON COMMENT 'Student answers: {"q1": "a", "q2": "b", ...}',
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    
    KEY `idx_quiz` (`id_quiz`),
    KEY `idx_nethera` (`id_nethera`),
    
    CONSTRAINT `fk_attempt_quiz` 
        FOREIGN KEY (`id_quiz`) REFERENCES `class_quizzes`(`id_quiz`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_attempt_nethera` 
        FOREIGN KEY (`id_nethera`) REFERENCES `nethera`(`id_nethera`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Quiz (uncomment to use for testing)
-- =====================================================
-- INSERT INTO class_quizzes (subject, title, description, time_limit, status, created_by) VALUES
-- ('english', 'English Basics Quiz', 'Test your basic English knowledge', 15, 'active', 1);

-- INSERT INTO quiz_questions (id_quiz, question_text, option_a, option_b, option_c, option_d, correct_answer, points) VALUES
-- (1, 'What is the past tense of "go"?', 'goed', 'went', 'gone', 'going', 'b', 10),
-- (1, 'Which is a proper noun?', 'city', 'London', 'river', 'mountain', 'b', 10),
-- (1, 'Choose the correct sentence:', 'He go to school', 'He goes to school', 'He going to school', 'He gone to school', 'b', 10);
