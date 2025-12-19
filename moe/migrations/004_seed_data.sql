-- =====================================================
-- Migration: 004_seed_data.sql
-- Description: Initial seed data for shop items, species, achievements
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- =====================
-- SHOP ITEMS SEED DATA
-- =====================

INSERT INTO `shop_items` (`name`, `description`, `category`, `effect_type`, `effect_value`, `price`, `icon`, `is_purchasable`) VALUES
-- Food Items
('Basic Kibble', 'Standard pet food. Restores 20 hunger.', 'food', 'food', 20, 10, '🍖', 1),
('Premium Feast', 'Delicious meal. Restores 50 hunger.', 'food', 'food', 50, 25, '🍗', 1),
('Gourmet Banquet', 'Luxurious feast. Fully restores hunger.', 'food', 'food', 100, 50, '🍲', 1),

-- Potions
('Health Potion', 'Basic healing. Restores 30 HP.', 'potion', 'potion', 30, 20, '🧪', 1),
('Super Potion', 'Advanced healing. Restores 60 HP.', 'potion', 'potion', 60, 40, '⚗️', 1),
('Max Potion', 'Full restoration. Restores 100 HP.', 'potion', 'potion', 100, 80, '🏺', 1),

-- Special Items
('Revive Crystal', 'Resurrects a dead pet with 50% HP.', 'special', 'revive', 50, 100, '💎', 1),
('Shield Amulet', 'Blocks one attack in battle.', 'special', 'shield', 1, 75, '🛡️', 1),
('EXP Boost', 'Grants 100 EXP instantly.', 'special', 'exp_boost', 100, 50, '⭐', 1),

-- Gacha Tickets
('Normal Gacha Ticket', 'Standard summon ticket.', 'gacha', 'gacha_ticket', 1, 50, '🎟️', 1),
('Premium Gacha Ticket', 'Guaranteed Rare or better!', 'gacha', 'premium_ticket', 1, 150, '🎫', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================
-- PET SPECIES SEED DATA
-- =====================

INSERT INTO `pet_species` (`name`, `element`, `rarity`, `base_attack`, `base_defense`, `base_speed`, `description`) VALUES
-- Common Pets
('Flamepup', 'Fire', 'Common', 12, 8, 10, 'A playful fire puppy with a warm heart.'),
('Aquafish', 'Water', 'Common', 8, 12, 10, 'A gentle fish that loves to swim.'),
('Stoneling', 'Earth', 'Common', 10, 14, 6, 'A sturdy rock creature.'),
('Breezeling', 'Air', 'Common', 10, 8, 14, 'A swift wind spirit.'),

-- Rare Pets
('Inferno Fox', 'Fire', 'Rare', 18, 12, 16, 'A cunning fox wreathed in flames.'),
('Tide Serpent', 'Water', 'Rare', 14, 18, 14, 'A majestic sea serpent.'),
('Crystal Golem', 'Earth', 'Rare', 16, 22, 8, 'A powerful golem made of crystals.'),
('Storm Falcon', 'Air', 'Rare', 16, 10, 22, 'A falcon that rides the storms.'),
('Luminos', 'Light', 'Rare', 14, 14, 16, 'A gentle being of pure light.'),
('Shadowcat', 'Dark', 'Rare', 18, 10, 20, 'A mysterious feline of shadows.'),

-- Epic Pets
('Phoenix', 'Fire', 'Epic', 24, 18, 20, 'A legendary bird reborn from ashes.'),
('Leviathan', 'Water', 'Epic', 20, 26, 16, 'Ancient ruler of the deep seas.'),
('Terraclaw', 'Earth', 'Epic', 22, 28, 12, 'A massive earthen guardian.'),
('Zephyr Dragon', 'Air', 'Epic', 22, 16, 26, 'A dragon that commands the winds.'),
('Seraphim', 'Light', 'Epic', 20, 20, 22, 'An angelic protector.'),
('Nightmare', 'Dark', 'Epic', 26, 16, 22, 'A terrifying creature of darkness.'),

-- Legendary Pets
('Anubis', 'Dark', 'Legendary', 30, 28, 26, 'The jackal god of the underworld.'),
('Ra', 'Light', 'Legendary', 32, 24, 28, 'The mighty sun god.'),
('Apophis', 'Dark', 'Legendary', 34, 22, 28, 'The serpent of chaos.'),
('Horus', 'Air', 'Legendary', 28, 26, 32, 'The falcon-headed sky god.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================
-- ACHIEVEMENTS SEED DATA
-- =====================

INSERT INTO `achievements` (`name`, `description`, `category`, `icon`, `rarity`, `requirement_type`, `requirement_value`, `reward_gold`) VALUES
-- Collection
('First Friend', 'Obtain your first pet', 'collection', '🐣', 'bronze', 'pet_count', 1, 50),
('Pet Collector', 'Own 10 pets', 'collection', '🏠', 'silver', 'pet_count', 10, 150),
('Master Collector', 'Own 25 pets', 'collection', '👑', 'gold', 'pet_count', 25, 300),

-- Rarity
('Shiny Hunter', 'Obtain a shiny pet', 'gacha', '✨', 'gold', 'shiny_count', 1, 200),
('Rare Find', 'Obtain a Rare pet', 'gacha', '💫', 'bronze', 'rare_count', 1, 50),
('Epic Discovery', 'Obtain an Epic pet', 'gacha', '🌟', 'silver', 'epic_count', 1, 100),
('Legendary Owner', 'Obtain a Legendary pet', 'gacha', '🏆', 'platinum', 'legendary_count', 1, 500),

-- Battle
('First Victory', 'Win your first battle', 'battle', '⚔️', 'bronze', 'battle_wins', 1, 50),
('Battle Veteran', 'Win 10 battles', 'battle', '🗡️', 'silver', 'battle_wins', 10, 150),
('Battle Legend', 'Win 50 battles', 'battle', '🏅', 'platinum', 'battle_wins', 50, 500),

-- Level
('Growing Up', 'Reach level 10 with a pet', 'level', '📈', 'bronze', 'max_level', 10, 50),
('Seasoned Trainer', 'Reach level 50 with a pet', 'level', '💪', 'gold', 'max_level', 50, 300),
('Max Power', 'Reach level 99 with a pet', 'level', '🔥', 'platinum', 'max_level', 99, 1000),

-- Login
('Daily Visitor', 'Log in 7 days in a row', 'login', '📅', 'bronze', 'login_streak', 7, 100),
('Dedicated Player', 'Log in 30 days total', 'login', '🗓️', 'silver', 'total_logins', 30, 200)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
