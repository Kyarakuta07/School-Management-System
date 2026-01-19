-- =====================================================
-- Migration: 013_reward_improvements.sql
-- Description: Add reward improvements - first win, streak, achievements
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- Add tracking columns to nethera
ALTER TABLE nethera 
ADD COLUMN last_battle_win_date DATE DEFAULT NULL,
ADD COLUMN current_win_streak INT DEFAULT 0;

-- Insert battle achievements
INSERT INTO achievements (name, description, category, icon, rarity, requirement_type, requirement_value, reward_gold) VALUES
('First Blood', 'Win your first battle', 'battle', '⚔️', 'bronze', 'battle_wins', 1, 50),
('Warrior', 'Win 10 battles', 'battle', '🗡️', 'bronze', 'battle_wins', 10, 100),
('Champion', 'Win 50 battles', 'battle', '🏆', 'silver', 'battle_wins', 50, 300),
('Legend', 'Win 100 battles', 'battle', '👑', 'gold', 'battle_wins', 100, 500),
('Hot Streak', 'Win 3 battles in a row', 'battle', '🔥', 'bronze', 'win_streak', 3, 75),
('Unstoppable', 'Win 5 battles in a row', 'battle', '💪', 'silver', 'win_streak', 5, 150),
('Dominator', 'Win 10 battles in a row', 'battle', '⚡', 'gold', 'win_streak', 10, 400),
('Invincible', 'Win 20 battles in a row', 'battle', '🌟', 'platinum', 'win_streak', 20, 1000)
ON DUPLICATE KEY UPDATE reward_gold = VALUES(reward_gold);
