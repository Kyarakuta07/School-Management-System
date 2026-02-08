-- Add rank_points column to user_pets for Season Ranking
-- Default 1000 (Base ELO)

ALTER TABLE user_pets
ADD COLUMN rank_points INT DEFAULT 1000 AFTER total_losses;

-- Index for fast leaderboard sorting
CREATE INDEX idx_rank_points ON user_pets(rank_points);
