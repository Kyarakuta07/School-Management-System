<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Wave 5 — Add missing indexes for performance.
 * SAFE: ADD INDEX only, no structural changes.
 */
class AddMissingIndexes extends Migration
{
    public function up()
    {
        // Helper: check if index exists before adding
        $addIndex = function (string $table, string $indexName, $columns) {
            $result = $this->db->query(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            )->getResultArray();

            if (empty($result)) {
                if (is_array($columns)) {
                    $cols = implode('`, `', $columns);
                } else {
                    $cols = $columns;
                }
                $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$cols}`)");
            }
        };

        // user_pets indexes
        $addIndex('user_pets', 'idx_user_status', ['user_id', 'status']);
        $addIndex('user_pets', 'idx_species', 'species_id');
        $addIndex('user_pets', 'idx_rank_points', 'rank_points');

        // pet_battles indexes
        $addIndex('pet_battles', 'idx_winner', 'winner_pet_id');
        $addIndex('pet_battles', 'idx_created_at', 'created_at');

        // nethera indexes
        $addIndex('nethera', 'idx_username_login', ['username', 'status_akun']);
        $addIndex('nethera', 'idx_email_lookup', 'email');
        $addIndex('nethera', 'idx_phone_lookup', 'noHP');
    }

    public function down()
    {
        // Drop indexes (safe, does not affect data)
        $drops = [
            ['user_pets', 'idx_user_status'],
            ['user_pets', 'idx_species'],
            ['user_pets', 'idx_rank_points'],
            ['pet_battles', 'idx_winner'],
            ['pet_battles', 'idx_created_at'],
            ['nethera', 'idx_username_login'],
            ['nethera', 'idx_email_lookup'],
            ['nethera', 'idx_phone_lookup'],
        ];

        foreach ($drops as [$table, $index]) {
            $result = $this->db->query(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$index]
            )->getResultArray();

            if (!empty($result)) {
                $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            }
        }
    }
}
