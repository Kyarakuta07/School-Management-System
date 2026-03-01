<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PetSpeciesSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['id' => 1, 'name' => 'Emberpup', 'element' => 'Fire', 'rarity' => 'Common', 'base_attack' => 55, 'base_defense' => 40, 'base_speed' => 60, 'base_health' => 100, 'img_egg' => 'fire/emberpup_egg.png', 'img_baby' => 'fire/emberpup_baby.png', 'img_adult' => 'fire/emberpup_adult.png'],
            ['id' => 2, 'name' => 'Aquafin', 'element' => 'Water', 'rarity' => 'Common', 'base_attack' => 45, 'base_defense' => 55, 'base_speed' => 50, 'base_health' => 100, 'img_egg' => 'water/aquafin_egg.png', 'img_baby' => 'water/aquafin_baby.png', 'img_adult' => 'water/aquafin_adult.png'],
            ['id' => 3, 'name' => 'Mudling', 'element' => 'Earth', 'rarity' => 'Common', 'base_attack' => 40, 'base_defense' => 65, 'base_speed' => 45, 'base_health' => 100, 'img_egg' => 'earth/mudling_egg.png', 'img_baby' => 'earth/mudling_baby.png', 'img_adult' => 'earth/mudling_adult.png'],
            ['id' => 4, 'name' => 'Zephyrix', 'element' => 'Air', 'rarity' => 'Common', 'base_attack' => 50, 'base_defense' => 45, 'base_speed' => 65, 'base_health' => 100, 'img_egg' => 'air/zephyrix_egg.png', 'img_baby' => 'air/zephyrix_baby.png', 'img_adult' => 'air/zephyrix_adult.png'],
            ['id' => 5, 'name' => 'Infernocat', 'element' => 'Fire', 'rarity' => 'Rare', 'base_attack' => 70, 'base_defense' => 50, 'base_speed' => 65, 'base_health' => 100, 'img_egg' => 'fire/infernocat_egg.png', 'img_baby' => 'fire/infernocat_baby.png', 'img_adult' => 'fire/infernocat_adult.png'],
            ['id' => 6, 'name' => 'Tidalwyrm', 'element' => 'Water', 'rarity' => 'Rare', 'base_attack' => 60, 'base_defense' => 70, 'base_speed' => 55, 'base_health' => 100, 'img_egg' => 'water/tidalwyrm_egg.png', 'img_baby' => 'water/tidalwyrm_baby.png', 'img_adult' => 'water/tidalwyrm_adult.png'],
            ['id' => 7, 'name' => 'Stonebear', 'element' => 'Earth', 'rarity' => 'Rare', 'base_attack' => 55, 'base_defense' => 80, 'base_speed' => 40, 'base_health' => 100, 'img_egg' => 'earth/stonebear_egg.png', 'img_baby' => 'earth/stonebear_baby.png', 'img_adult' => 'earth/stonebear_adult.png'],
            ['id' => 8, 'name' => 'Stormhawk', 'element' => 'Air', 'rarity' => 'Rare', 'base_attack' => 65, 'base_defense' => 55, 'base_speed' => 80, 'base_health' => 100, 'img_egg' => 'air/stormhawk_egg.png', 'img_baby' => 'air/stormhawk_baby.png', 'img_adult' => 'air/stormhawk_adult.png'],
            ['id' => 9, 'name' => 'Shadowfox', 'element' => 'Dark', 'rarity' => 'Epic', 'base_attack' => 75, 'base_defense' => 65, 'base_speed' => 85, 'base_health' => 100, 'img_egg' => 'dark/shadowfox_egg.png', 'img_baby' => 'dark/shadowfox_baby.png', 'img_adult' => 'dark/shadowfox_adult.png'],
            ['id' => 10, 'name' => 'Luminowl', 'element' => 'Light', 'rarity' => 'Epic', 'base_attack' => 70, 'base_defense' => 75, 'base_speed' => 70, 'base_health' => 100, 'img_egg' => 'light/luminowl_egg.png', 'img_baby' => 'light/luminowl_baby.png', 'img_adult' => 'light/luminowl_adult.png'],
            ['id' => 11, 'name' => 'Anubis Pup', 'element' => 'Dark', 'rarity' => 'Legendary', 'base_attack' => 90, 'base_defense' => 85, 'base_speed' => 90, 'base_health' => 100, 'img_egg' => 'dark/anubis_egg.png', 'img_baby' => 'dark/anubis_baby.png', 'img_adult' => 'dark/anubis_adult.png'],
            ['id' => 12, 'name' => 'Phoenix Chick', 'element' => 'Fire', 'rarity' => 'Legendary', 'base_attack' => 95, 'base_defense' => 80, 'base_speed' => 95, 'base_health' => 100, 'img_egg' => 'fire/phoenix_egg.png', 'img_baby' => 'fire/phoenix_baby.png', 'img_adult' => 'fire/phoenix_adult.png'],
            ['id' => 13, 'name' => 'Basilisk', 'element' => 'Dark', 'rarity' => 'Mythical', 'base_attack' => 85, 'base_defense' => 75, 'base_speed' => 70, 'base_health' => 120, 'img_egg' => 'dark/basilisk/basilisk_egg.png', 'img_baby' => 'dark/basilisk/basilisk_adult.png', 'img_adult' => 'dark/basilisk/basilisk_king.png'],

            // --- NEW PETS (Light - Common/Rare) ---
            ['id' => 14, 'name' => 'Glint-Beetle', 'element' => 'Light', 'rarity' => 'Common', 'base_attack' => 50, 'base_defense' => 45, 'base_speed' => 60, 'base_health' => 100, 'img_egg' => 'light/glintbeetle_egg.png', 'img_baby' => 'light/glintbeetle_baby.png', 'img_adult' => 'light/glintbeetle_adult.png'],
            ['id' => 15, 'name' => 'Cosmo-Whale', 'element' => 'Light', 'rarity' => 'Rare', 'base_attack' => 60, 'base_defense' => 65, 'base_speed' => 55, 'base_health' => 100, 'img_egg' => 'light/cosmowhale_egg.png', 'img_baby' => 'light/cosmowhale_baby.png', 'img_adult' => 'light/cosmowhale_adult.png'],

            // --- NEW PETS (Dark - Common/Rare) ---
            ['id' => 16, 'name' => 'Dusk-Bat', 'element' => 'Dark', 'rarity' => 'Common', 'base_attack' => 52, 'base_defense' => 40, 'base_speed' => 65, 'base_health' => 100, 'img_egg' => 'dark/duskbat_egg.png', 'img_baby' => 'dark/duskbat_baby.png', 'img_adult' => 'dark/duskbat_adult.png'],
            ['id' => 17, 'name' => 'Shadow-Gargoyle', 'element' => 'Dark', 'rarity' => 'Rare', 'base_attack' => 68, 'base_defense' => 62, 'base_speed' => 58, 'base_health' => 100, 'img_egg' => 'dark/shadowgargoyle_egg.png', 'img_baby' => 'dark/shadowgargoyle_baby.png', 'img_adult' => 'dark/shadowgargoyle_adult.png'],

            // --- NEW PETS (Fire - Common/Rare) ---
            ['id' => 18, 'name' => 'Magma-Gecko', 'element' => 'Fire', 'rarity' => 'Common', 'base_attack' => 55, 'base_defense' => 40, 'base_speed' => 62, 'base_health' => 100, 'img_egg' => 'fire/magmagecko_egg.png', 'img_baby' => 'fire/magmagecko_baby.png', 'img_adult' => 'fire/magmagecko_adult.png'],
            ['id' => 19, 'name' => 'Volcanic-Komodo', 'element' => 'Fire', 'rarity' => 'Rare', 'base_attack' => 70, 'base_defense' => 55, 'base_speed' => 60, 'base_health' => 100, 'img_egg' => 'fire/volcanickomodo_egg.png', 'img_baby' => 'fire/volcanickomodo_baby.png', 'img_adult' => 'fire/volcanickomodo_adult.png'],

            // --- NEW PETS (Water - Common/Rare) ---
            ['id' => 20, 'name' => 'Bubble-Crab', 'element' => 'Water', 'rarity' => 'Common', 'base_attack' => 45, 'base_defense' => 58, 'base_speed' => 50, 'base_health' => 100, 'img_egg' => 'water/bubblecrab_egg.png', 'img_baby' => 'water/bubblecrab_baby.png', 'img_adult' => 'water/bubblecrab_adult.png'],
            ['id' => 21, 'name' => 'Coral-Titan', 'element' => 'Water', 'rarity' => 'Rare', 'base_attack' => 62, 'base_defense' => 72, 'base_speed' => 52, 'base_health' => 100, 'img_egg' => 'water/coraltitan_egg.png', 'img_baby' => 'water/coraltitan_baby.png', 'img_adult' => 'water/coraltitan_adult.png'],

            // --- NEW PETS (Earth - Common/Rare) ---
            ['id' => 22, 'name' => 'Root-Spider', 'element' => 'Earth', 'rarity' => 'Common', 'base_attack' => 48, 'base_defense' => 52, 'base_speed' => 55, 'base_health' => 100, 'img_egg' => 'earth/rootspider_egg.png', 'img_baby' => 'earth/rootspider_baby.png', 'img_adult' => 'earth/rootspider_adult.png'],
            ['id' => 23, 'name' => 'Boulder-Tarantula', 'element' => 'Earth', 'rarity' => 'Rare', 'base_attack' => 58, 'base_defense' => 75, 'base_speed' => 42, 'base_health' => 100, 'img_egg' => 'earth/bouldertarantula_egg.png', 'img_baby' => 'earth/bouldertarantula_baby.png', 'img_adult' => 'earth/bouldertarantula_adult.png'],

            // --- NEW PETS (Air - Common/Rare) ---
            ['id' => 24, 'name' => 'Cloud-Moth', 'element' => 'Air', 'rarity' => 'Common', 'base_attack' => 48, 'base_defense' => 43, 'base_speed' => 68, 'base_health' => 100, 'img_egg' => 'air/cloudmoth_egg.png', 'img_baby' => 'air/cloudmoth_baby.png', 'img_adult' => 'air/cloudmoth_adult.png'],
            ['id' => 25, 'name' => 'Tempest-Papillon', 'element' => 'Air', 'rarity' => 'Rare', 'base_attack' => 63, 'base_defense' => 52, 'base_speed' => 78, 'base_health' => 100, 'img_egg' => 'air/tempestpapillon_egg.png', 'img_baby' => 'air/tempestpapillon_baby.png', 'img_adult' => 'air/tempestpapillon_adult.png'],

            // --- NEW PETS (Epic) ---
            ['id' => 26, 'name' => 'Blaze-Chimera', 'element' => 'Fire', 'rarity' => 'Epic', 'base_attack' => 82, 'base_defense' => 68, 'base_speed' => 78, 'base_health' => 120, 'img_egg' => 'fire/blazechimera_egg.png', 'img_baby' => 'fire/blazechimera_baby.png', 'img_adult' => 'fire/blazechimera_adult.png'],
            ['id' => 27, 'name' => 'Abyssal-Kraken', 'element' => 'Water', 'rarity' => 'Epic', 'base_attack' => 78, 'base_defense' => 80, 'base_speed' => 65, 'base_health' => 120, 'img_egg' => 'water/abyssalkraken_egg.png', 'img_baby' => 'water/abyssalkraken_baby.png', 'img_adult' => 'water/abyssalkraken_adult.png'],
            ['id' => 28, 'name' => 'Titan-Golem', 'element' => 'Earth', 'rarity' => 'Epic', 'base_attack' => 75, 'base_defense' => 88, 'base_speed' => 48, 'base_health' => 120, 'img_egg' => 'earth/titangolem_egg.png', 'img_baby' => 'earth/titangolem_baby.png', 'img_adult' => 'earth/titangolem_adult.png'],
            ['id' => 29, 'name' => 'Zephyr-Wyvern', 'element' => 'Air', 'rarity' => 'Epic', 'base_attack' => 80, 'base_defense' => 65, 'base_speed' => 90, 'base_health' => 120, 'img_egg' => 'air/zephyrwyvern_egg.png', 'img_baby' => 'air/zephyrwyvern_baby.png', 'img_adult' => 'air/zephyrwyvern_adult.png'],
            ['id' => 30, 'name' => 'Aurora-Pegasus', 'element' => 'Light', 'rarity' => 'Epic', 'base_attack' => 80, 'base_defense' => 72, 'base_speed' => 85, 'base_health' => 120, 'img_egg' => 'light/aurorapegasus_egg.png', 'img_baby' => 'light/aurorapegasus_baby.png', 'img_adult' => 'light/aurorapegasus_adult.png'],

            // --- NEW PETS (Legendary) ---
            ['id' => 31, 'name' => 'Seraphim-Dragon', 'element' => 'Light', 'rarity' => 'Legendary', 'base_attack' => 95, 'base_defense' => 90, 'base_speed' => 92, 'base_health' => 130, 'img_egg' => 'light/seraphimdragon_egg.png', 'img_baby' => 'light/seraphimdragon_baby.png', 'img_adult' => 'light/seraphimdragon_adult.png'],
        ];

        $table = $this->db->table('pet_species');
        foreach ($data as $row) {
            $exists = $this->db->table('pet_species')->where('id', $row['id'])->countAllResults();
            if ($exists === 0) {
                $table->insert($row);
            }
        }

        // Invalidate Bestiary cache so new species appear immediately
        \Config\Services::cache()->delete('all_pet_species');

        echo "PetSpeciesSeeder: 31 species seeded (cache invalidated).\n";
    }
}
