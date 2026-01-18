-- ================================================
-- Sanctuary War System
-- Migration: 011_sanctuary_war.sql
-- ================================================

-- 1. War sessions table
CREATE TABLE IF NOT EXISTS sanctuary_wars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    war_date DATE UNIQUE NOT NULL,
    status ENUM('active', 'finished') DEFAULT 'active',
    winner_sanctuary_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (winner_sanctuary_id) REFERENCES sanctuary(id_sanctuary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Sanctuary scores per war
CREATE TABLE IF NOT EXISTS sanctuary_war_scores (
    war_id INT NOT NULL,
    sanctuary_id INT NOT NULL,
    total_points INT DEFAULT 0,
    wins INT DEFAULT 0,
    losses INT DEFAULT 0,
    ties INT DEFAULT 0,
    PRIMARY KEY (war_id, sanctuary_id),
    FOREIGN KEY (war_id) REFERENCES sanctuary_wars(id) ON DELETE CASCADE,
    FOREIGN KEY (sanctuary_id) REFERENCES sanctuary(id_sanctuary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Individual battle logs
CREATE TABLE IF NOT EXISTS war_battles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    war_id INT NOT NULL,
    user_id INT NOT NULL,
    opponent_id INT NOT NULL,
    user_sanctuary_id INT NOT NULL,
    opponent_sanctuary_id INT NOT NULL,
    user_pet_id INT NOT NULL,
    opponent_pet_id INT NOT NULL,
    winner_user_id INT NULL,
    points_earned INT DEFAULT 0,
    gold_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (war_id) REFERENCES sanctuary_wars(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES nethera(id_nethera),
    FOREIGN KEY (opponent_id) REFERENCES nethera(id_nethera),
    FOREIGN KEY (user_sanctuary_id) REFERENCES sanctuary(id_sanctuary),
    FOREIGN KEY (opponent_sanctuary_id) REFERENCES sanctuary(id_sanctuary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. User tickets per war (track usage)
CREATE TABLE IF NOT EXISTS war_user_tickets (
    war_id INT NOT NULL,
    user_id INT NOT NULL,
    tickets_used INT DEFAULT 0,
    PRIMARY KEY (war_id, user_id),
    FOREIGN KEY (war_id) REFERENCES sanctuary_wars(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES nethera(id_nethera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for performance
CREATE INDEX idx_war_battles_war ON war_battles(war_id);
CREATE INDEX idx_war_battles_user ON war_battles(user_id);
CREATE INDEX idx_war_battles_created ON war_battles(created_at);
