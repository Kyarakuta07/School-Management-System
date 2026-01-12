-- ============================================
-- Migration: Add Performance Indexes
-- Mediterranean of Egypt - School Management System
-- Created: 2026-01-13
-- ============================================

-- Description:
-- Add composite indexes to improve query performance for:
-- 1. quiz_attempts - frequently queried by quiz, user, and completion status
-- 2. class_grades - frequently queried by user and sorted by total_pp

-- Note: MySQL doesn't support IF NOT EXISTS for CREATE INDEX
-- These will fail silently if index already exists (run one at a time if needed)

-- ============================================
-- QUIZ ATTEMPTS INDEXES
-- ============================================

-- Index for fetching user's attempts on a specific quiz
-- Used in: quiz_attempt.php (checking max attempts)
ALTER TABLE quiz_attempts ADD INDEX idx_quiz_attempts_quiz_user (id_quiz, id_nethera);

-- Index for fetching completed attempts with completion date
-- Used in: class.php (quiz results table)
ALTER TABLE quiz_attempts ADD INDEX idx_quiz_attempts_completed (completed_at);

-- Composite index for quiz results filtering
-- Used in: ClassController.php (quiz results with subject filter)
ALTER TABLE quiz_attempts ADD INDEX idx_quiz_attempts_quiz_completed (id_quiz, completed_at);

-- ============================================
-- CLASS GRADES INDEXES
-- ============================================

-- Index for user grade lookup
-- Used in: class.php (getting user's grades)
ALTER TABLE class_grades ADD INDEX idx_class_grades_nethera (id_nethera);

-- Index for ranking queries (sorted by total_pp)
-- Used in: class.php (sanctuary ranking, user rank calculation)
ALTER TABLE class_grades ADD INDEX idx_class_grades_total_pp (total_pp);

-- ============================================
-- VERIFICATION
-- ============================================
-- To verify indexes were created, run:
-- SHOW INDEX FROM quiz_attempts;
-- SHOW INDEX FROM class_grades;
