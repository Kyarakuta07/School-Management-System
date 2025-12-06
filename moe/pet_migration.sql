-- ================================================
-- MOE Pet System - Database Migration
-- Run this AFTER pet_schema.sql
-- ================================================

-- Add gold column to nethera table if it doesn't exist
-- This is the user's currency for gacha and shop

-- Check if column exists before adding (MySQL 8.0+ syntax)
SET @dbname = DATABASE();
SET @tablename = 'nethera';
SET @columnname = 'gold';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  'ALTER TABLE nethera ADD COLUMN gold INT DEFAULT 500'
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Alternative simpler approach (will error if column exists, but safe to ignore):
-- ALTER TABLE nethera ADD COLUMN gold INT DEFAULT 500;

-- Give all existing users some starting gold
UPDATE nethera SET gold = 500 WHERE gold IS NULL OR gold = 0;
