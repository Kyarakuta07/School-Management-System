-- ================================================
-- MOE Pet System Database Schema
-- Mediterranean of Egypt - Virtual Pet Companion
-- ================================================

-- Drop tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS pet_battles;
DROP TABLE IF EXISTS user_inventory;
DROP TABLE IF EXISTS user_pets;
DROP TABLE IF EXISTS shop_items;
DROP TABLE IF EXISTS pet_species;

-- ================================================
-- 1. PET SPECIES TABLE (Static Data)
-- Stores all available pet types with their stats
-- ================================================
CREATE TABLE pet_species (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    element ENUM('Fire', 'Water', 'Earth', 'Air', 'Dark', 'Light') NOT NULL,
    rarity ENUM('Common', 'Rare', 'Epic', 'Legendary') NOT NULL,
    
    -- Image paths (relative to /moe/assets/pets/)
    img_egg VARCHAR(255) DEFAULT 'default/egg.png',
    img_baby VARCHAR(255) DEFAULT 'default/baby.png',
    img_adult VARCHAR(255) DEFAULT 'default/adult.png',
    
    -- Passive buff for school activities
    passive_buff_type VARCHAR(50) COMMENT 'e.g., sports_exp, study_exp, art_exp',
    passive_buff_value INT DEFAULT 10 COMMENT 'Buff percentage (10 = +10%)',
    
    -- Base battle stats
    base_attack INT DEFAULT 50,
    base_defense INT DEFAULT 50,
    base_speed INT DEFAULT 50,
    
    -- Description
    description TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- 2. USER PETS TABLE (Dynamic Data)
-- Stores pets owned by users with live stats
-- ================================================
CREATE TABLE user_pets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'References nethera.id_nethera',
    species_id INT NOT NULL,
    
    nickname VARCHAR(50) DEFAULT NULL,
    level INT DEFAULT 1,
    exp INT DEFAULT 0,
    
    -- Dynamic stats (0-100 scale)
    health INT DEFAULT 100,
    hunger INT DEFAULT 100,
    mood INT DEFAULT 100,
    
    -- Pet status
    status ENUM('ALIVE', 'DEAD', 'BUSY', 'SHELTER') DEFAULT 'ALIVE',
    
    -- Shiny variant (CSS hue-rotate applied)
    is_shiny TINYINT(1) DEFAULT 0,
    shiny_hue INT DEFAULT 0 COMMENT 'Hue rotation degrees (0-360)',
    
    -- Timestamp for lazy calculation
    last_update_timestamp INT NOT NULL COMMENT 'UNIX timestamp',
    
    -- Active pet flag (only one pet can be active)
    is_active TINYINT(1) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (species_id) REFERENCES pet_species(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index for faster user lookups
CREATE INDEX idx_user_pets_user ON user_pets(user_id);
CREATE INDEX idx_user_pets_active ON user_pets(user_id, is_active);

-- ================================================
-- 3. SHOP ITEMS TABLE
-- Purchasable items for pet care
-- ================================================
CREATE TABLE shop_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    
    -- Item effects
    effect_type ENUM('food', 'potion', 'revive', 'exp_boost', 'gacha_ticket') NOT NULL,
    effect_value INT NOT NULL COMMENT 'Amount of stat restored/boosted',
    
    -- Pricing
    price INT NOT NULL,
    
    -- Image path
    img_path VARCHAR(255) DEFAULT 'items/default.png',
    
    -- Availability
    is_available TINYINT(1) DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- 4. USER INVENTORY TABLE
-- Items owned by users
-- ================================================
CREATE TABLE user_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'References nethera.id_nethera',
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_item (user_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- 5. PET BATTLES TABLE (Async PVP)
-- Battle logs between pets
-- ================================================
CREATE TABLE pet_battles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    attacker_pet_id INT NOT NULL,
    defender_pet_id INT NOT NULL,
    
    -- Results
    winner_pet_id INT DEFAULT NULL,
    battle_log TEXT COMMENT 'JSON log of battle events',
    
    -- Rewards
    reward_gold INT DEFAULT 0,
    reward_exp INT DEFAULT 0,
    
    -- Status
    is_read_by_attacker TINYINT(1) DEFAULT 0,
    is_read_by_defender TINYINT(1) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (attacker_pet_id) REFERENCES user_pets(id) ON DELETE CASCADE,
    FOREIGN KEY (defender_pet_id) REFERENCES user_pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index for battle lookups
CREATE INDEX idx_battles_attacker ON pet_battles(attacker_pet_id);
CREATE INDEX idx_battles_defender ON pet_battles(defender_pet_id);

-- ================================================
-- SEED DATA: Initial Pet Species
-- ================================================
INSERT INTO pet_species (name, element, rarity, img_egg, img_baby, img_adult, passive_buff_type, passive_buff_value, base_attack, base_defense, base_speed, description) VALUES
-- Common Pets (60% drop rate)
('Emberpup', 'Fire', 'Common', 'fire/emberpup_egg.png', 'fire/emberpup_baby.png', 'fire/emberpup_adult.png', 'sports_exp', 10, 55, 40, 60, 'A small flame-tailed puppy that loves to run. Boosts Sports EXP.'),
('Aquafin', 'Water', 'Common', 'water/aquafin_egg.png', 'water/aquafin_baby.png', 'water/aquafin_adult.png', 'study_exp', 10, 45, 55, 50, 'A calm fish companion that helps with focus. Boosts Study EXP.'),
('Mudling', 'Earth', 'Common', 'earth/mudling_egg.png', 'earth/mudling_baby.png', 'earth/mudling_adult.png', 'art_exp', 10, 40, 65, 45, 'A playful clay creature. Boosts Art EXP.'),
('Zephyrix', 'Air', 'Common', 'air/zephyrix_egg.png', 'air/zephyrix_baby.png', 'air/zephyrix_adult.png', 'music_exp', 10, 50, 45, 65, 'A swift wind sprite. Boosts Music EXP.'),

-- Rare Pets (25% drop rate)
('Infernocat', 'Fire', 'Rare', 'fire/infernocat_egg.png', 'fire/infernocat_baby.png', 'fire/infernocat_adult.png', 'sports_exp', 15, 70, 50, 65, 'A fierce feline wreathed in flames. Strong attacker.'),
('Tidalwyrm', 'Water', 'Rare', 'water/tidalwyrm_egg.png', 'water/tidalwyrm_baby.png', 'water/tidalwyrm_adult.png', 'study_exp', 15, 60, 70, 55, 'A serpentine water dragon. Great defender.'),
('Stonebear', 'Earth', 'Rare', 'earth/stonebear_egg.png', 'earth/stonebear_baby.png', 'earth/stonebear_adult.png', 'art_exp', 15, 55, 80, 40, 'A sturdy bear made of living rock. Unbreakable defense.'),
('Stormhawk', 'Air', 'Rare', 'air/stormhawk_egg.png', 'air/stormhawk_baby.png', 'air/stormhawk_adult.png', 'music_exp', 15, 65, 55, 80, 'A lightning-fast raptor. Fastest of all pets.'),

-- Epic Pets (12% drop rate)
('Shadowfox', 'Dark', 'Epic', 'dark/shadowfox_egg.png', 'dark/shadowfox_baby.png', 'dark/shadowfox_adult.png', 'all_exp', 12, 75, 65, 85, 'A mysterious fox that walks between shadows. Boosts all EXP slightly.'),
('Luminowl', 'Light', 'Epic', 'light/luminowl_egg.png', 'light/luminowl_baby.png', 'light/luminowl_adult.png', 'all_exp', 12, 70, 75, 70, 'A radiant owl of pure light. Balanced stats and all EXP boost.'),

-- Legendary Pets (3% drop rate)
('Anubis Pup', 'Dark', 'Legendary', 'dark/anubis_egg.png', 'dark/anubis_baby.png', 'dark/anubis_adult.png', 'all_exp', 20, 90, 85, 90, 'The legendary guardian of the underworld. Extremely rare and powerful.'),
('Phoenix Chick', 'Fire', 'Legendary', 'fire/phoenix_egg.png', 'fire/phoenix_baby.png', 'fire/phoenix_adult.png', 'all_exp', 20, 95, 80, 95, 'Born from eternal flames. Revives once upon death.');

-- ================================================
-- SEED DATA: Shop Items
-- ================================================
INSERT INTO shop_items (name, description, effect_type, effect_value, price, img_path) VALUES
-- Food Items
('Basic Kibble', 'Simple pet food. Restores 20 hunger.', 'food', 20, 10, 'items/kibble.png'),
('Premium Feast', 'Gourmet pet food. Restores 50 hunger.', 'food', 50, 25, 'items/feast.png'),
('Royal Banquet', 'Fit for royalty. Fully restores hunger.', 'food', 100, 50, 'items/banquet.png'),

-- Potions
('Health Elixir', 'Restores 30 health points.', 'potion', 30, 30, 'items/health_elixir.png'),
('Vitality Potion', 'Restores 60 health points.', 'potion', 60, 55, 'items/vitality_potion.png'),
('Phoenix Tears', 'Fully restores health.', 'potion', 100, 100, 'items/phoenix_tears.png'),

-- Revival
('Soul Fragment', 'Revives a dead pet with 50% stats.', 'revive', 50, 200, 'items/soul_fragment.png'),
('Ankh of Life', 'Revives a dead pet with full stats.', 'revive', 100, 500, 'items/ankh.png'),

-- EXP Boosters
('Wisdom Scroll', 'Grants 50 EXP to your pet.', 'exp_boost', 50, 40, 'items/wisdom_scroll.png'),
('Ancient Tome', 'Grants 150 EXP to your pet.', 'exp_boost', 150, 100, 'items/ancient_tome.png'),

-- Gacha
('Bronze Egg', 'A mysterious egg. Common rarity guaranteed.', 'gacha_ticket', 1, 50, 'items/bronze_egg.png'),
('Silver Egg', 'A shimmering egg. Rare or better guaranteed.', 'gacha_ticket', 2, 150, 'items/silver_egg.png'),
('Golden Egg', 'A radiant egg. Epic or better guaranteed.', 'gacha_ticket', 3, 500, 'items/golden_egg.png');
