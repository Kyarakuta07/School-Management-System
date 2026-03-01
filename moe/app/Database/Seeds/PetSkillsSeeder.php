<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PetSkillsSeeder extends Seeder
{
    public function run()
    {
        $skills = [
            // Emberpup (Fire, Common)
            ['species_id' => 1, 'skill_slot' => 1, 'skill_name' => 'Ember Bite', 'base_damage' => 25, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 1, 'skill_slot' => 2, 'skill_name' => 'Flame Paw', 'base_damage' => 35, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 1, 'skill_slot' => 3, 'skill_name' => 'Fire Howl', 'base_damage' => 50, 'skill_element' => 'Fire', 'is_special' => 1],
            ['species_id' => 1, 'skill_slot' => 4, 'skill_name' => 'Inferno Rush', 'base_damage' => 70, 'skill_element' => 'Fire', 'is_special' => 1],
            // Aquafin (Water, Common)
            ['species_id' => 2, 'skill_slot' => 1, 'skill_name' => 'Water Splash', 'base_damage' => 25, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 2, 'skill_slot' => 2, 'skill_name' => 'Tidal Wave', 'base_damage' => 35, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 2, 'skill_slot' => 3, 'skill_name' => 'Hydro Cannon', 'base_damage' => 50, 'skill_element' => 'Water', 'is_special' => 1],
            ['species_id' => 2, 'skill_slot' => 4, 'skill_name' => 'Tsunami', 'base_damage' => 70, 'skill_element' => 'Water', 'is_special' => 1],
            // Mudling (Earth, Common)
            ['species_id' => 3, 'skill_slot' => 1, 'skill_name' => 'Mud Slap', 'base_damage' => 25, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 3, 'skill_slot' => 2, 'skill_name' => 'Rock Throw', 'base_damage' => 35, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 3, 'skill_slot' => 3, 'skill_name' => 'Earthquake', 'base_damage' => 50, 'skill_element' => 'Earth', 'is_special' => 1],
            ['species_id' => 3, 'skill_slot' => 4, 'skill_name' => 'Terra Slam', 'base_damage' => 70, 'skill_element' => 'Earth', 'is_special' => 1],
            // Zephyrix (Air, Common)
            ['species_id' => 4, 'skill_slot' => 1, 'skill_name' => 'Wind Slash', 'base_damage' => 25, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 4, 'skill_slot' => 2, 'skill_name' => 'Gust Force', 'base_damage' => 35, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 4, 'skill_slot' => 3, 'skill_name' => 'Cyclone', 'base_damage' => 50, 'skill_element' => 'Air', 'is_special' => 1],
            ['species_id' => 4, 'skill_slot' => 4, 'skill_name' => 'Storm Fury', 'base_damage' => 70, 'skill_element' => 'Air', 'is_special' => 1],
            // Infernocat (Fire, Rare)
            ['species_id' => 5, 'skill_slot' => 1, 'skill_name' => 'Fire Scratch', 'base_damage' => 25, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 5, 'skill_slot' => 2, 'skill_name' => 'Flame Fang', 'base_damage' => 35, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 5, 'skill_slot' => 3, 'skill_name' => 'Blaze Rush', 'base_damage' => 50, 'skill_element' => 'Fire', 'is_special' => 1],
            ['species_id' => 5, 'skill_slot' => 4, 'skill_name' => 'Hellfire', 'base_damage' => 70, 'skill_element' => 'Fire', 'is_special' => 1],
            // Tidalwyrm (Water, Rare)
            ['species_id' => 6, 'skill_slot' => 1, 'skill_name' => 'Aqua Tail', 'base_damage' => 25, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 6, 'skill_slot' => 2, 'skill_name' => 'Dragon Pulse', 'base_damage' => 35, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 6, 'skill_slot' => 3, 'skill_name' => 'Whirlpool', 'base_damage' => 50, 'skill_element' => 'Water', 'is_special' => 1],
            ['species_id' => 6, 'skill_slot' => 4, 'skill_name' => 'Ocean Wrath', 'base_damage' => 70, 'skill_element' => 'Water', 'is_special' => 1],
            // Stonebear (Earth, Rare)
            ['species_id' => 7, 'skill_slot' => 1, 'skill_name' => 'Stone Punch', 'base_damage' => 25, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 7, 'skill_slot' => 2, 'skill_name' => 'Boulder Crush', 'base_damage' => 35, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 7, 'skill_slot' => 3, 'skill_name' => 'Mountain Force', 'base_damage' => 50, 'skill_element' => 'Earth', 'is_special' => 1],
            ['species_id' => 7, 'skill_slot' => 4, 'skill_name' => 'Titan Smash', 'base_damage' => 70, 'skill_element' => 'Earth', 'is_special' => 1],
            // Stormhawk (Air, Rare)
            ['species_id' => 8, 'skill_slot' => 1, 'skill_name' => 'Air Slash', 'base_damage' => 25, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 8, 'skill_slot' => 2, 'skill_name' => 'Sonic Dive', 'base_damage' => 35, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 8, 'skill_slot' => 3, 'skill_name' => 'Thunder Wing', 'base_damage' => 50, 'skill_element' => 'Air', 'is_special' => 1],
            ['species_id' => 8, 'skill_slot' => 4, 'skill_name' => 'Hurricane', 'base_damage' => 70, 'skill_element' => 'Air', 'is_special' => 1],
            // Shadowfox (Dark, Epic)
            ['species_id' => 9, 'skill_slot' => 1, 'skill_name' => 'Shadow Claw', 'base_damage' => 25, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 9, 'skill_slot' => 2, 'skill_name' => 'Night Slash', 'base_damage' => 35, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 9, 'skill_slot' => 3, 'skill_name' => 'Void Blast', 'base_damage' => 50, 'skill_element' => 'Dark', 'is_special' => 1],
            ['species_id' => 9, 'skill_slot' => 4, 'skill_name' => 'Eternal Night', 'base_damage' => 70, 'skill_element' => 'Dark', 'is_special' => 1],
            // Luminowl (Light, Epic)
            ['species_id' => 10, 'skill_slot' => 1, 'skill_name' => 'Light Beam', 'base_damage' => 25, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 10, 'skill_slot' => 2, 'skill_name' => 'Holy Strike', 'base_damage' => 35, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 10, 'skill_slot' => 3, 'skill_name' => 'Divine Ray', 'base_damage' => 50, 'skill_element' => 'Light', 'is_special' => 1],
            ['species_id' => 10, 'skill_slot' => 4, 'skill_name' => 'Radiant Burst', 'base_damage' => 70, 'skill_element' => 'Light', 'is_special' => 1],
            // Anubis Pup (Dark, Legendary)
            ['species_id' => 11, 'skill_slot' => 1, 'skill_name' => 'Dark Bite', 'base_damage' => 25, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 11, 'skill_slot' => 2, 'skill_name' => 'Soul Drain', 'base_damage' => 35, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 11, 'skill_slot' => 3, 'skill_name' => 'Underworld Gate', 'base_damage' => 50, 'skill_element' => 'Dark', 'is_special' => 1],
            ['species_id' => 11, 'skill_slot' => 4, 'skill_name' => 'Judgement', 'base_damage' => 70, 'skill_element' => 'Dark', 'is_special' => 1],
            // Phoenix Chick (Fire, Legendary)
            ['species_id' => 12, 'skill_slot' => 1, 'skill_name' => 'Ember Strike', 'base_damage' => 25, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 12, 'skill_slot' => 2, 'skill_name' => 'Flame Burst', 'base_damage' => 35, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 12, 'skill_slot' => 3, 'skill_name' => 'Inferno', 'base_damage' => 50, 'skill_element' => 'Fire', 'is_special' => 1],
            ['species_id' => 12, 'skill_slot' => 4, 'skill_name' => 'Phoenix Blaze', 'base_damage' => 70, 'skill_element' => 'Fire', 'is_special' => 1],
            // Basilisk (Dark, Mythical)
            ['species_id' => 13, 'skill_slot' => 1, 'skill_name' => 'Venomous Strike', 'base_damage' => 45, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 13, 'skill_slot' => 2, 'skill_name' => 'Gaze of Abyss', 'base_damage' => 60, 'skill_element' => 'Dark', 'is_special' => 0],

            // Glint-Beetle (Light, Common)
            ['species_id' => 14, 'skill_slot' => 1, 'skill_name' => 'Shell Bash', 'base_damage' => 25, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 14, 'skill_slot' => 2, 'skill_name' => 'Glint Strike', 'base_damage' => 35, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 14, 'skill_slot' => 3, 'skill_name' => 'Solar Beam', 'base_damage' => 50, 'skill_element' => 'Light', 'is_special' => 1],
            ['species_id' => 14, 'skill_slot' => 4, 'skill_name' => 'Radiant Carapace', 'base_damage' => 70, 'skill_element' => 'Light', 'is_special' => 1],

            // Cosmo-Whale (Light, Rare)
            ['species_id' => 15, 'skill_slot' => 1, 'skill_name' => 'Starfall Blow', 'base_damage' => 25, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 15, 'skill_slot' => 2, 'skill_name' => 'Cosmic Tide', 'base_damage' => 35, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 15, 'skill_slot' => 3, 'skill_name' => 'Nebula Crash', 'base_damage' => 50, 'skill_element' => 'Light', 'is_special' => 1],
            ['species_id' => 15, 'skill_slot' => 4, 'skill_name' => 'Galaxy Breach', 'base_damage' => 70, 'skill_element' => 'Light', 'is_special' => 1],

            // Dusk-Bat (Dark, Common)
            ['species_id' => 16, 'skill_slot' => 1, 'skill_name' => 'Shriek', 'base_damage' => 25, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 16, 'skill_slot' => 2, 'skill_name' => 'Wing Slash', 'base_damage' => 35, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 16, 'skill_slot' => 3, 'skill_name' => 'Dusk Dive', 'base_damage' => 50, 'skill_element' => 'Dark', 'is_special' => 1],
            ['species_id' => 16, 'skill_slot' => 4, 'skill_name' => 'Midnight Frenzy', 'base_damage' => 70, 'skill_element' => 'Dark', 'is_special' => 1],

            // Shadow-Gargoyle (Dark, Rare)
            ['species_id' => 17, 'skill_slot' => 1, 'skill_name' => 'Stone Grip', 'base_damage' => 25, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 17, 'skill_slot' => 2, 'skill_name' => 'Dark Screech', 'base_damage' => 35, 'skill_element' => 'Dark', 'is_special' => 0],
            ['species_id' => 17, 'skill_slot' => 3, 'skill_name' => 'Umbra Crush', 'base_damage' => 50, 'skill_element' => 'Dark', 'is_special' => 1],
            ['species_id' => 17, 'skill_slot' => 4, 'skill_name' => 'Abyss Smash', 'base_damage' => 70, 'skill_element' => 'Dark', 'is_special' => 1],

            // Magma-Gecko (Fire, Common)
            ['species_id' => 18, 'skill_slot' => 1, 'skill_name' => 'Lava Lick', 'base_damage' => 25, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 18, 'skill_slot' => 2, 'skill_name' => 'Magma Spit', 'base_damage' => 35, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 18, 'skill_slot' => 3, 'skill_name' => 'Scorched Dash', 'base_damage' => 50, 'skill_element' => 'Fire', 'is_special' => 1],
            ['species_id' => 18, 'skill_slot' => 4, 'skill_name' => 'Eruption Bite', 'base_damage' => 70, 'skill_element' => 'Fire', 'is_special' => 1],

            // Volcanic-Komodo (Fire, Rare)
            ['species_id' => 19, 'skill_slot' => 1, 'skill_name' => 'Ember Lunge', 'base_damage' => 25, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 19, 'skill_slot' => 2, 'skill_name' => 'Volcanic Bite', 'base_damage' => 35, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 19, 'skill_slot' => 3, 'skill_name' => 'Molten Fury', 'base_damage' => 50, 'skill_element' => 'Fire', 'is_special' => 1],
            ['species_id' => 19, 'skill_slot' => 4, 'skill_name' => 'Caldera Roar', 'base_damage' => 70, 'skill_element' => 'Fire', 'is_special' => 1],

            // Bubble-Crab (Water, Common)
            ['species_id' => 20, 'skill_slot' => 1, 'skill_name' => 'Pincer Snap', 'base_damage' => 25, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 20, 'skill_slot' => 2, 'skill_name' => 'Bubble Burst', 'base_damage' => 35, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 20, 'skill_slot' => 3, 'skill_name' => 'Foam Shield', 'base_damage' => 50, 'skill_element' => 'Water', 'is_special' => 1],
            ['species_id' => 20, 'skill_slot' => 4, 'skill_name' => 'Tide Crush', 'base_damage' => 70, 'skill_element' => 'Water', 'is_special' => 1],

            // Coral-Titan (Water, Rare)
            ['species_id' => 21, 'skill_slot' => 1, 'skill_name' => 'Reef Slam', 'base_damage' => 25, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 21, 'skill_slot' => 2, 'skill_name' => 'Coral Lance', 'base_damage' => 35, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 21, 'skill_slot' => 3, 'skill_name' => 'Deep Current', 'base_damage' => 50, 'skill_element' => 'Water', 'is_special' => 1],
            ['species_id' => 21, 'skill_slot' => 4, 'skill_name' => 'Abyssal Surge', 'base_damage' => 70, 'skill_element' => 'Water', 'is_special' => 1],

            // Root-Spider (Earth, Common)
            ['species_id' => 22, 'skill_slot' => 1, 'skill_name' => 'Web Snare', 'base_damage' => 25, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 22, 'skill_slot' => 2, 'skill_name' => 'Root Stab', 'base_damage' => 35, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 22, 'skill_slot' => 3, 'skill_name' => 'Vine Trap', 'base_damage' => 50, 'skill_element' => 'Earth', 'is_special' => 1],
            ['species_id' => 22, 'skill_slot' => 4, 'skill_name' => 'Thorned Frenzy', 'base_damage' => 70, 'skill_element' => 'Earth', 'is_special' => 1],

            // Boulder-Tarantula (Earth, Rare)
            ['species_id' => 23, 'skill_slot' => 1, 'skill_name' => 'Rock Stomp', 'base_damage' => 25, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 23, 'skill_slot' => 2, 'skill_name' => 'Boulder Fang', 'base_damage' => 35, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 23, 'skill_slot' => 3, 'skill_name' => 'Quake Web', 'base_damage' => 50, 'skill_element' => 'Earth', 'is_special' => 1],
            ['species_id' => 23, 'skill_slot' => 4, 'skill_name' => 'Avalanche Rush', 'base_damage' => 70, 'skill_element' => 'Earth', 'is_special' => 1],

            // Cloud-Moth (Air, Common)
            ['species_id' => 24, 'skill_slot' => 1, 'skill_name' => 'Wing Dust', 'base_damage' => 25, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 24, 'skill_slot' => 2, 'skill_name' => 'Mist Dive', 'base_damage' => 35, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 24, 'skill_slot' => 3, 'skill_name' => 'Cloud Splash', 'base_damage' => 50, 'skill_element' => 'Air', 'is_special' => 1],
            ['species_id' => 24, 'skill_slot' => 4, 'skill_name' => 'Nimbus Burst', 'base_damage' => 70, 'skill_element' => 'Air', 'is_special' => 1],

            // Tempest-Papillon (Air, Rare)
            ['species_id' => 25, 'skill_slot' => 1, 'skill_name' => 'Gale Wing', 'base_damage' => 25, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 25, 'skill_slot' => 2, 'skill_name' => 'Tempest Slash', 'base_damage' => 35, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 25, 'skill_slot' => 3, 'skill_name' => 'Vortex Dance', 'base_damage' => 50, 'skill_element' => 'Air', 'is_special' => 1],
            ['species_id' => 25, 'skill_slot' => 4, 'skill_name' => 'Storm Waltz', 'base_damage' => 70, 'skill_element' => 'Air', 'is_special' => 1],

            // Blaze-Chimera (Fire, Epic)
            ['species_id' => 26, 'skill_slot' => 1, 'skill_name' => 'Triple Fang', 'base_damage' => 25, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 26, 'skill_slot' => 2, 'skill_name' => 'Chimera Roar', 'base_damage' => 35, 'skill_element' => 'Fire', 'is_special' => 0],
            ['species_id' => 26, 'skill_slot' => 3, 'skill_name' => 'Infernal Charge', 'base_damage' => 50, 'skill_element' => 'Fire', 'is_special' => 1],
            ['species_id' => 26, 'skill_slot' => 4, 'skill_name' => 'Blaze Ultimate', 'base_damage' => 70, 'skill_element' => 'Fire', 'is_special' => 1],

            // Abyssal-Kraken (Water, Epic)
            ['species_id' => 27, 'skill_slot' => 1, 'skill_name' => 'Tentacle Grab', 'base_damage' => 25, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 27, 'skill_slot' => 2, 'skill_name' => 'Ink Blast', 'base_damage' => 35, 'skill_element' => 'Water', 'is_special' => 0],
            ['species_id' => 27, 'skill_slot' => 3, 'skill_name' => 'Kraken Crush', 'base_damage' => 50, 'skill_element' => 'Water', 'is_special' => 1],
            ['species_id' => 27, 'skill_slot' => 4, 'skill_name' => 'Abyss Vortex', 'base_damage' => 70, 'skill_element' => 'Water', 'is_special' => 1],

            // Titan-Golem (Earth, Epic)
            ['species_id' => 28, 'skill_slot' => 1, 'skill_name' => 'Iron Fist', 'base_damage' => 25, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 28, 'skill_slot' => 2, 'skill_name' => 'Golem Stomp', 'base_damage' => 35, 'skill_element' => 'Earth', 'is_special' => 0],
            ['species_id' => 28, 'skill_slot' => 3, 'skill_name' => 'Tectonic Slam', 'base_damage' => 50, 'skill_element' => 'Earth', 'is_special' => 1],
            ['species_id' => 28, 'skill_slot' => 4, 'skill_name' => 'Titan Impact', 'base_damage' => 70, 'skill_element' => 'Earth', 'is_special' => 1],

            // Zephyr-Wyvern (Air, Epic)
            ['species_id' => 29, 'skill_slot' => 1, 'skill_name' => 'Wyvern Claw', 'base_damage' => 25, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 29, 'skill_slot' => 2, 'skill_name' => 'Zephyr Dive', 'base_damage' => 35, 'skill_element' => 'Air', 'is_special' => 0],
            ['species_id' => 29, 'skill_slot' => 3, 'skill_name' => 'Thunder Gale', 'base_damage' => 50, 'skill_element' => 'Air', 'is_special' => 1],
            ['species_id' => 29, 'skill_slot' => 4, 'skill_name' => 'Sky Obliterate', 'base_damage' => 70, 'skill_element' => 'Air', 'is_special' => 1],

            // Aurora-Pegasus (Light, Epic)
            ['species_id' => 30, 'skill_slot' => 1, 'skill_name' => 'Aurora Kick', 'base_damage' => 25, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 30, 'skill_slot' => 2, 'skill_name' => 'Prism Charge', 'base_damage' => 35, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 30, 'skill_slot' => 3, 'skill_name' => 'Celestial Dash', 'base_damage' => 50, 'skill_element' => 'Light', 'is_special' => 1],
            ['species_id' => 30, 'skill_slot' => 4, 'skill_name' => 'Aurora Ascension', 'base_damage' => 70, 'skill_element' => 'Light', 'is_special' => 1],

            // Seraphim-Dragon (Light, Legendary)
            ['species_id' => 31, 'skill_slot' => 1, 'skill_name' => 'Divine Claw', 'base_damage' => 25, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 31, 'skill_slot' => 2, 'skill_name' => 'Holy Flame', 'base_damage' => 35, 'skill_element' => 'Light', 'is_special' => 0],
            ['species_id' => 31, 'skill_slot' => 3, 'skill_name' => 'Seraphic Roar', 'base_damage' => 50, 'skill_element' => 'Light', 'is_special' => 1],
            ['species_id' => 31, 'skill_slot' => 4, 'skill_name' => 'Heaven\'s Wrath', 'base_damage' => 70, 'skill_element' => 'Light', 'is_special' => 1],
        ];

        $table = $this->db->table('pet_skills');
        $inserted = 0;
        foreach ($skills as $row) {
            $exists = $this->db->table('pet_skills')
                ->where('species_id', $row['species_id'])
                ->where('skill_slot', $row['skill_slot'])
                ->countAllResults();
            if ($exists === 0) {
                $table->insert($row);
                $inserted++;
            }
        }

        echo "PetSkillsSeeder: {$inserted} skills seeded (total defined: " . count($skills) . ").\n";
    }
}
