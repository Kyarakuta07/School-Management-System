-- ================================================
-- Migration: Update Class Subjects
-- ================================================
-- Removes: herbology
-- Adds: pop_culture, mythology, history_of_egypt
-- ================================================

-- Step 1: Drop the herbology column
ALTER TABLE class_grades DROP COLUMN IF EXISTS herbology;

-- Step 2: Add new subject columns
ALTER TABLE class_grades ADD COLUMN pop_culture INT DEFAULT 0;
ALTER TABLE class_grades ADD COLUMN mythology INT DEFAULT 0;
ALTER TABLE class_grades ADD COLUMN history_of_egypt INT DEFAULT 0;

-- Step 3: Recalculate total_pp for all existing records
-- Formula: history + oceanology + astronomy + pop_culture + mythology + history_of_egypt
UPDATE class_grades 
SET total_pp = COALESCE(history, 0) + COALESCE(oceanology, 0) + COALESCE(astronomy, 0) 
             + COALESCE(pop_culture, 0) + COALESCE(mythology, 0) + COALESCE(history_of_egypt, 0);
