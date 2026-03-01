<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Phase2Cleanup extends Migration
{
    public function up()
    {
        // 1. DROP LEGACY COLUMNS FROM nethera
        // We do this first because we verified code no longer uses them.
        $this->forge->dropColumn('nethera', ['failed_login_attempts', 'account_locked_until']);

        // 2. DROP DUPLICATE FOREIGN KEYS
        // These are the auto-named constraints that duplicate our named ones.
        $db = \Config\Database::connect();

        // Pet Battles Duplicates
        $db->query("ALTER TABLE pet_battles DROP FOREIGN KEY IF EXISTS pet_battles_ibfk_1");
        $db->query("ALTER TABLE pet_battles DROP FOREIGN KEY IF EXISTS pet_battles_ibfk_2");

        // User Inventory Duplicates
        $db->query("ALTER TABLE user_inventory DROP FOREIGN KEY IF EXISTS user_inventory_ibfk_1");

        // User Pets Duplicates
        $db->query("ALTER TABLE user_pets DROP FOREIGN KEY IF EXISTS user_pets_ibfk_1");

        // 3. DROP REDUNDANT INDEXES
        $db->query("ALTER TABLE pet_battles DROP INDEX IF EXISTS idx_battles_attacker");
        $db->query("ALTER TABLE pet_battles DROP INDEX IF EXISTS idx_battles_defender");
        $db->query("ALTER TABLE user_pets DROP INDEX IF EXISTS idx_user_pets_active");
        $db->query("ALTER TABLE user_inventory DROP INDEX IF EXISTS idx_user_item");
        $db->query("ALTER TABLE class_grades DROP INDEX IF EXISTS id_nethera");

        // 4. STANDARDIZE ROLE COLUMN TO ENUM
        $db->query("ALTER TABLE nethera MODIFY COLUMN role ENUM('Nethera', 'Hakaes', 'Vasiki', 'Anubis') NOT NULL DEFAULT 'Nethera'");

        // 5. RENAME LEGACY PUNISHMENT TABLE
        if ($db->tableExists('punishment')) {
            $db->query("RENAME TABLE punishment TO punishment_legacy");
        }
    }

    public function down()
    {
        // Rollback is complex for drops, but we can restore columns
        $this->forge->addColumn('nethera', [
            'failed_login_attempts' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'account_locked_until' => ['type' => 'DATETIME', 'null' => true],
        ]);

        // Restore role to varchar
        $db = \Config\Database::connect();
        $db->query("ALTER TABLE nethera MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'Nethera'");

        // Restore punishment name
        if ($db->tableExists('punishment_legacy')) {
            $db->query("RENAME TABLE punishment_legacy TO punishment");
        }
    }
}
