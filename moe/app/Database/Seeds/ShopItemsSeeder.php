<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ShopItemsSeeder extends Seeder
{
    public function run()
    {
        $items = [
            ['id' => 1, 'name' => 'Basic Kibble', 'effect_type' => 'food', 'effect_value' => 20, 'price' => 10, 'description' => 'Simple pet food. Restores 20 hunger.', 'img_path' => 'items/default.png'],
            ['id' => 2, 'name' => 'Premium Feast', 'effect_type' => 'food', 'effect_value' => 50, 'price' => 25, 'description' => 'Gourmet pet food. Restores 50 hunger.', 'img_path' => 'items/default.png'],
            ['id' => 3, 'name' => 'Royal Banquet', 'effect_type' => 'food', 'effect_value' => 100, 'price' => 50, 'description' => 'Fit for royalty. Fully restores hunger.', 'img_path' => 'items/default.png'],
            ['id' => 4, 'name' => 'Health Elixir', 'effect_type' => 'potion', 'effect_value' => 30, 'price' => 30, 'description' => 'Restores 30 health points.', 'img_path' => 'items/default.png'],
            ['id' => 5, 'name' => 'Vitality Potion', 'effect_type' => 'potion', 'effect_value' => 60, 'price' => 55, 'description' => 'Restores 60 health points.', 'img_path' => 'items/default.png'],
            ['id' => 6, 'name' => 'Phoenix Tears', 'effect_type' => 'potion', 'effect_value' => 100, 'price' => 100, 'description' => 'Fully restores health.', 'img_path' => 'items/default.png'],
            ['id' => 7, 'name' => 'Soul Fragment', 'effect_type' => 'revive', 'effect_value' => 50, 'price' => 200, 'description' => 'Revives a dead pet with 50% stats.', 'img_path' => 'items/default.png'],
            ['id' => 8, 'name' => 'Ankh of Life', 'effect_type' => 'revive', 'effect_value' => 100, 'price' => 500, 'description' => 'Revives a dead pet with full stats.', 'img_path' => 'items/default.png'],
            ['id' => 9, 'name' => 'Wisdom Scroll', 'effect_type' => 'exp_boost', 'effect_value' => 200, 'price' => 60, 'description' => 'Grants 200 EXP to your pet.', 'img_path' => 'items/default.png'],
            ['id' => 10, 'name' => 'Ancient Tome', 'effect_type' => 'exp_boost', 'effect_value' => 500, 'price' => 150, 'description' => 'Grants 500 EXP to your pet.', 'img_path' => 'items/default.png'],
            ['id' => 16, 'name' => 'Divine Shield', 'effect_type' => 'shield', 'effect_value' => 1, 'price' => 200, 'description' => 'Protects your pet from 1 attack in battle.', 'img_path' => 'items/default.png'],
            ['id' => 22, 'name' => 'Arena Ticket', 'effect_type' => 'arena_reset', 'effect_value' => 0, 'price' => 500, 'description' => 'A Reset Token that restores your daily Arena battle quota.', 'img_path' => 'items/default.png'],
        ];

        $table = $this->db->table('shop_items');
        foreach ($items as $row) {
            $exists = $this->db->table('shop_items')->where('id', $row['id'])->countAllResults();
            if ($exists === 0) {
                $table->insert($row);
            }
        }

        echo "ShopItemsSeeder: " . count($items) . " items seeded.\n";
    }
}
