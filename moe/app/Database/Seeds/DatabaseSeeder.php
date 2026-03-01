<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Master Database Seeder
 * 
 * Runs all seeders in the correct order (respecting foreign key dependencies).
 * Safe to re-run — all child seeders use INSERT-IF-NOT-EXISTS logic.
 * 
 * Usage: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "========================================\n";
        echo "  MOE Database Seeder — Starting...\n";
        echo "========================================\n\n";

        // 1. Sanctuary (required by nethera.id_sanctuary FK)
        $this->call('App\Database\Seeds\SanctuarySeeder');

        // 2. Admin/Test Users (depends on sanctuary)
        $this->call('App\Database\Seeds\AdminUserSeeder');

        // 3. Pet Species (standalone lookup table)
        $this->call('App\Database\Seeds\PetSpeciesSeeder');

        // 4. Pet Skills (depends on pet_species)
        $this->call('App\Database\Seeds\PetSkillsSeeder');

        // 5. Shop Items (standalone lookup table)
        $this->call('App\Database\Seeds\ShopItemsSeeder');

        // 6. Element Resistances (standalone lookup table)
        $this->call('App\Database\Seeds\ElementResistanceSeeder');

        // 7. Achievements (standalone lookup table)
        $this->call('App\Database\Seeds\AchievementsSeeder');

        // 8. Class Schedule (standalone lookup table)
        $this->call('App\Database\Seeds\ClassScheduleSeeder');

        echo "\n========================================\n";
        echo "  MOE Database Seeder — Complete!\n";
        echo "========================================\n";
    }
}
