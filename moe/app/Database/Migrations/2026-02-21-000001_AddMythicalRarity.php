<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMythicalRarity extends Migration
{
    public function up()
    {
        // Modify rarity column in pet_species
        $this->db->query("ALTER TABLE pet_species MODIFY COLUMN rarity ENUM('Common', 'Rare', 'Epic', 'Legendary', 'Mythical') NOT NULL");
    }

    public function down()
    {
        // Revert rarity column in pet_species
        $this->db->query("ALTER TABLE pet_species MODIFY COLUMN rarity ENUM('Common', 'Rare', 'Epic', 'Legendary') NOT NULL");
    }
}
