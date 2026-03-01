<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddArenaTicket extends Migration
{
    public function up()
    {
        // Only insert if no arena_reset item exists (avoid duplicate with seeder)
        $exists = $this->db->table('shop_items')->where('effect_type', 'arena_reset')->countAllResults();
        if ($exists === 0) {
            $data = [
                [
                    'name' => 'Arena Ticket',
                    'description' => 'A mystical document that resets your daily Arena battle quota, allowing for more challenges.',
                    'price' => 500,
                    'effect_type' => 'arena_reset',
                    'effect_value' => '0',
                    'img_path' => 'assets/items/item_arena_ticket.png',
                    'is_available' => 1
                ]
            ];
            $this->db->table('shop_items')->insertBatch($data);
        }
    }

    public function down()
    {
        $this->db->table('shop_items')->where('effect_type', 'arena_reset')->delete();
    }
}
