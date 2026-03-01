<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AchievementsSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // Collection
            ['id' => 1, 'name' => 'First Friend', 'description' => 'Obtain your first pet', 'category' => 'collection', 'icon' => '🐣', 'rarity' => 'bronze', 'requirement_type' => 'pets_owned', 'requirement_value' => 1, 'reward_gold' => 10],
            ['id' => 2, 'name' => 'Pet Collector', 'description' => 'Own 5 different pets', 'category' => 'collection', 'icon' => '🎪', 'rarity' => 'silver', 'requirement_type' => 'pets_owned', 'requirement_value' => 5, 'reward_gold' => 25],
            ['id' => 3, 'name' => 'Pet Master', 'description' => 'Own 10 different pets', 'category' => 'collection', 'icon' => '👑', 'rarity' => 'gold', 'requirement_type' => 'pets_owned', 'requirement_value' => 10, 'reward_gold' => 250],
            ['id' => 4, 'name' => 'Shiny Hunter', 'description' => 'Obtain a shiny pet', 'category' => 'collection', 'icon' => '✨', 'rarity' => 'gold', 'requirement_type' => 'shiny_pets', 'requirement_value' => 1, 'reward_gold' => 30],
            ['id' => 5, 'name' => 'Legendary Owner', 'description' => 'Obtain a legendary pet', 'category' => 'collection', 'icon' => '🌟', 'rarity' => 'platinum', 'requirement_type' => 'legendary_pets', 'requirement_value' => 1, 'reward_gold' => 75],
            // Battle (original)
            ['id' => 6, 'name' => 'First Blood', 'description' => 'Win your first battle', 'category' => 'battle', 'icon' => '⚔️', 'rarity' => 'bronze', 'requirement_type' => 'battle_wins', 'requirement_value' => 1, 'reward_gold' => 50],
            ['id' => 7, 'name' => 'Warrior', 'description' => 'Win 10 battles', 'category' => 'battle', 'icon' => '🗡️', 'rarity' => 'silver', 'requirement_type' => 'battle_wins', 'requirement_value' => 10, 'reward_gold' => 150],
            ['id' => 8, 'name' => 'Champion', 'description' => 'Win 25 battles', 'category' => 'battle', 'icon' => '🏆', 'rarity' => 'gold', 'requirement_type' => 'battle_wins', 'requirement_value' => 25, 'reward_gold' => 300],
            ['id' => 9, 'name' => 'Battle Legend', 'description' => 'Win 50 battles', 'category' => 'battle', 'icon' => '👊', 'rarity' => 'platinum', 'requirement_type' => 'battle_wins', 'requirement_value' => 50, 'reward_gold' => 75],
            // Level
            ['id' => 10, 'name' => 'Growing Strong', 'description' => 'Reach pet level 10', 'category' => 'level', 'icon' => '📈', 'rarity' => 'bronze', 'requirement_type' => 'max_pet_level', 'requirement_value' => 10, 'reward_gold' => 75],
            ['id' => 11, 'name' => 'Experienced', 'description' => 'Reach pet level 25', 'category' => 'level', 'icon' => '💪', 'rarity' => 'silver', 'requirement_type' => 'max_pet_level', 'requirement_value' => 25, 'reward_gold' => 200],
            ['id' => 12, 'name' => 'Elite Trainer', 'description' => 'Reach pet level 50', 'category' => 'level', 'icon' => '🔥', 'rarity' => 'gold', 'requirement_type' => 'max_pet_level', 'requirement_value' => 50, 'reward_gold' => 400],
            ['id' => 13, 'name' => 'Max Power', 'description' => 'Reach pet level 99', 'category' => 'level', 'icon' => '⭐', 'rarity' => 'platinum', 'requirement_type' => 'max_pet_level', 'requirement_value' => 99, 'reward_gold' => 150],
            // Gacha
            ['id' => 14, 'name' => 'Lucky Draw', 'description' => 'Perform 10 gacha rolls', 'category' => 'gacha', 'icon' => '🎰', 'rarity' => 'bronze', 'requirement_type' => 'gacha_rolls', 'requirement_value' => 10, 'reward_gold' => 50],
            ['id' => 15, 'name' => 'Fortune Seeker', 'description' => 'Perform 50 gacha rolls', 'category' => 'gacha', 'icon' => '🎲', 'rarity' => 'silver', 'requirement_type' => 'gacha_rolls', 'requirement_value' => 50, 'reward_gold' => 150],
            ['id' => 16, 'name' => 'Gacha Addict', 'description' => 'Perform 100 gacha rolls', 'category' => 'gacha', 'icon' => '💎', 'rarity' => 'gold', 'requirement_type' => 'gacha_rolls', 'requirement_value' => 100, 'reward_gold' => 300],
            // Login
            ['id' => 17, 'name' => 'Dedicated', 'description' => '7-day login streak', 'category' => 'login', 'icon' => '📅', 'rarity' => 'bronze', 'requirement_type' => 'login_streak', 'requirement_value' => 7, 'reward_gold' => 100],
            ['id' => 18, 'name' => 'Committed', 'description' => '14-day login streak', 'category' => 'login', 'icon' => '🗓️', 'rarity' => 'silver', 'requirement_type' => 'login_streak', 'requirement_value' => 14, 'reward_gold' => 200],
            ['id' => 19, 'name' => 'Loyal Player', 'description' => '30-day login streak', 'category' => 'login', 'icon' => '🏅', 'rarity' => 'gold', 'requirement_type' => 'login_streak', 'requirement_value' => 30, 'reward_gold' => 500],
            // Special
            ['id' => 20, 'name' => 'Rhythm Master', 'description' => 'Complete rhythm game 10 times', 'category' => 'special', 'icon' => '🎵', 'rarity' => 'silver', 'requirement_type' => 'rhythm_games', 'requirement_value' => 10, 'reward_gold' => 150],
            ['id' => 21, 'name' => 'Gold Digger', 'description' => 'Earn 1000 gold total', 'category' => 'special', 'icon' => '💰', 'rarity' => 'silver', 'requirement_type' => 'total_gold_earned', 'requirement_value' => 1000, 'reward_gold' => 100],
            ['id' => 22, 'name' => 'Resurrection', 'description' => 'Revive a dead pet', 'category' => 'special', 'icon' => '💫', 'rarity' => 'gold', 'requirement_type' => 'pets_revived', 'requirement_value' => 1, 'reward_gold' => 200],
            // Battle expanded (v2)
            ['id' => 23, 'name' => 'First Blood', 'description' => 'Win your first battle', 'category' => 'battle', 'icon' => '⚔️', 'rarity' => 'bronze', 'requirement_type' => 'battle_wins', 'requirement_value' => 1, 'reward_gold' => 50],
            ['id' => 24, 'name' => 'Warrior', 'description' => 'Win 10 battles', 'category' => 'battle', 'icon' => '🗡️', 'rarity' => 'bronze', 'requirement_type' => 'battle_wins', 'requirement_value' => 10, 'reward_gold' => 100],
            ['id' => 25, 'name' => 'Champion', 'description' => 'Win 50 battles', 'category' => 'battle', 'icon' => '🏆', 'rarity' => 'silver', 'requirement_type' => 'battle_wins', 'requirement_value' => 50, 'reward_gold' => 300],
            ['id' => 26, 'name' => 'Legend', 'description' => 'Win 100 battles', 'category' => 'battle', 'icon' => '👑', 'rarity' => 'gold', 'requirement_type' => 'battle_wins', 'requirement_value' => 100, 'reward_gold' => 500],
            // Streak achievements
            ['id' => 27, 'name' => 'Hot Streak', 'description' => 'Win 3 battles in a row', 'category' => 'battle', 'icon' => '🔥', 'rarity' => 'bronze', 'requirement_type' => 'win_streak', 'requirement_value' => 3, 'reward_gold' => 75],
            ['id' => 28, 'name' => 'Unstoppable', 'description' => 'Win 5 battles in a row', 'category' => 'battle', 'icon' => '💪', 'rarity' => 'silver', 'requirement_type' => 'win_streak', 'requirement_value' => 5, 'reward_gold' => 150],
            ['id' => 29, 'name' => 'Dominator', 'description' => 'Win 10 battles in a row', 'category' => 'battle', 'icon' => '⚡', 'rarity' => 'gold', 'requirement_type' => 'win_streak', 'requirement_value' => 10, 'reward_gold' => 400],
            ['id' => 30, 'name' => 'Invincible', 'description' => 'Win 20 battles in a row', 'category' => 'battle', 'icon' => '🌟', 'rarity' => 'platinum', 'requirement_type' => 'win_streak', 'requirement_value' => 20, 'reward_gold' => 1000],
        ];

        $table = $this->db->table('achievements');
        foreach ($data as $row) {
            $exists = $this->db->table('achievements')->where('id', $row['id'])->countAllResults();
            if ($exists === 0) {
                $table->insert($row);
            }
        }

        echo "AchievementsSeeder: " . count($data) . " achievements seeded.\n";
    }
}
