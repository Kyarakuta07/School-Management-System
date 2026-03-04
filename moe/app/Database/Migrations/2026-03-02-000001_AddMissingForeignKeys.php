<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add missing Foreign Key constraints to nethera and user_pets.
 *
 * This migration:
 *  1. Cleans orphaned rows (user/pet IDs that no longer exist in parent tables).
 *  2. Adds indexes on FK columns (required before adding FK).
 *  3. Adds FK constraints so the database enforces referential integrity.
 *
 * Strategy:
 *  - Nullable columns → SET NULL on DELETE (keep the row, clear the reference)
 *  - Non-nullable columns → CASCADE on DELETE (delete the child row)
 */
class AddMissingForeignKeys extends Migration
{
    public function up()
    {
        // ──────────────────────────────────────────────
        // STEP 1 — Clean orphaned data so FK creation won't fail
        // ──────────────────────────────────────────────

        // battle_participants: user_id (NOT NULL)
        $this->db->query("
            DELETE bp FROM battle_participants bp
            LEFT JOIN nethera n ON bp.user_id = n.id_nethera
            WHERE n.id_nethera IS NULL
        ");

        // battle_participants: pet_id (NOT NULL)
        $this->db->query("
            DELETE bp FROM battle_participants bp
            LEFT JOIN user_pets up ON bp.pet_id = up.id
            WHERE up.id IS NULL
        ");

        // battle_sessions: current_turn_user_id (NULLABLE)
        $this->db->query("
            UPDATE battle_sessions SET current_turn_user_id = NULL
            WHERE current_turn_user_id IS NOT NULL
            AND current_turn_user_id NOT IN (SELECT id_nethera FROM nethera)
        ");

        // battle_sessions: winner_user_id (NULLABLE)
        $this->db->query("
            UPDATE battle_sessions SET winner_user_id = NULL
            WHERE winner_user_id IS NOT NULL
            AND winner_user_id NOT IN (SELECT id_nethera FROM nethera)
        ");

        // user_achievements: user_id (NOT NULL)
        $this->db->query("
            DELETE ua FROM user_achievements ua
            LEFT JOIN nethera n ON ua.user_id = n.id_nethera
            WHERE n.id_nethera IS NULL
        ");

        // war_battles: winner_user_id (NULLABLE)
        $this->db->query("
            UPDATE war_battles SET winner_user_id = NULL
            WHERE winner_user_id IS NOT NULL
            AND winner_user_id NOT IN (SELECT id_nethera FROM nethera)
        ");

        // war_battles: user_pet_id (NOT NULL)
        $this->db->query("
            DELETE wb FROM war_battles wb
            LEFT JOIN user_pets up ON wb.user_pet_id = up.id
            WHERE up.id IS NULL
        ");

        // war_battles: opponent_pet_id (NOT NULL)
        $this->db->query("
            DELETE wb FROM war_battles wb
            LEFT JOIN user_pets up ON wb.opponent_pet_id = up.id
            WHERE up.id IS NULL
        ");

        // pet_battles: winner_pet_id (NULLABLE)
        $this->db->query("
            UPDATE pet_battles SET winner_pet_id = NULL
            WHERE winner_pet_id IS NOT NULL
            AND winner_pet_id NOT IN (SELECT id FROM user_pets)
        ");

        // pet_evolution_history: main_pet_id (NOT NULL)
        $this->db->query("
            DELETE peh FROM pet_evolution_history peh
            LEFT JOIN user_pets up ON peh.main_pet_id = up.id
            WHERE up.id IS NULL
        ");

        // idempotency_keys: user_id (NOT NULL)
        $this->db->query("
            DELETE ik FROM idempotency_keys ik
            LEFT JOIN nethera n ON ik.user_id = n.id_nethera
            WHERE n.id_nethera IS NULL
        ");

        // system_logs: user_id (NULLABLE)
        $this->db->query("
            UPDATE system_logs SET user_id = NULL
            WHERE user_id IS NOT NULL
            AND user_id NOT IN (SELECT id_nethera FROM nethera)
        ");

        // ──────────────────────────────────────────────
        // STEP 2 — Add indexes (required for FK) if not exists
        // ──────────────────────────────────────────────

        $this->addIndexIfNotExists('battle_sessions', 'current_turn_user_id');
        $this->addIndexIfNotExists('battle_sessions', 'winner_user_id');
        $this->addIndexIfNotExists('user_achievements', 'user_id');
        $this->addIndexIfNotExists('war_battles', 'winner_user_id');
        $this->addIndexIfNotExists('war_battles', 'user_pet_id');
        $this->addIndexIfNotExists('war_battles', 'opponent_pet_id');
        $this->addIndexIfNotExists('pet_battles', 'winner_pet_id');
        $this->addIndexIfNotExists('pet_evolution_history', 'main_pet_id');

        // ──────────────────────────────────────────────
        // STEP 3 — Add Foreign Keys to nethera.id_nethera
        // ──────────────────────────────────────────────

        $this->addFkIfNotExists(
            'battle_participants',
            'fk_bp_user',
            'user_id',
            'nethera',
            'id_nethera',
            'CASCADE'
        );

        $this->addFkIfNotExists(
            'battle_sessions',
            'fk_bs_current_user',
            'current_turn_user_id',
            'nethera',
            'id_nethera',
            'SET NULL'
        );

        $this->addFkIfNotExists(
            'battle_sessions',
            'fk_bs_winner',
            'winner_user_id',
            'nethera',
            'id_nethera',
            'SET NULL'
        );

        $this->addFkIfNotExists(
            'user_achievements',
            'fk_ua_user',
            'user_id',
            'nethera',
            'id_nethera',
            'CASCADE'
        );

        $this->addFkIfNotExists(
            'war_battles',
            'fk_wb_winner',
            'winner_user_id',
            'nethera',
            'id_nethera',
            'SET NULL'
        );

        $this->addFkIfNotExists(
            'idempotency_keys',
            'fk_ik_user',
            'user_id',
            'nethera',
            'id_nethera',
            'CASCADE'
        );

        $this->addFkIfNotExists(
            'system_logs',
            'fk_sl_user',
            'user_id',
            'nethera',
            'id_nethera',
            'SET NULL'
        );

        // ──────────────────────────────────────────────
        // STEP 4 — Add Foreign Keys to user_pets.id
        // ──────────────────────────────────────────────

        $this->addFkIfNotExists(
            'battle_participants',
            'fk_bp_pet',
            'pet_id',
            'user_pets',
            'id',
            'CASCADE'
        );

        $this->addFkIfNotExists(
            'pet_battles',
            'fk_pbt_winner_pet',
            'winner_pet_id',
            'user_pets',
            'id',
            'SET NULL'
        );

        $this->addFkIfNotExists(
            'pet_evolution_history',
            'fk_peh_main_pet',
            'main_pet_id',
            'user_pets',
            'id',
            'CASCADE'
        );

        $this->addFkIfNotExists(
            'war_battles',
            'fk_wb_user_pet',
            'user_pet_id',
            'user_pets',
            'id',
            'CASCADE'
        );

        $this->addFkIfNotExists(
            'war_battles',
            'fk_wb_opp_pet',
            'opponent_pet_id',
            'user_pets',
            'id',
            'CASCADE'
        );
    }

    public function down()
    {
        // Remove FKs to nethera
        $this->dropFkIfExists('battle_participants', 'fk_bp_user');
        $this->dropFkIfExists('battle_sessions', 'fk_bs_current_user');
        $this->dropFkIfExists('battle_sessions', 'fk_bs_winner');
        $this->dropFkIfExists('user_achievements', 'fk_ua_user');
        $this->dropFkIfExists('war_battles', 'fk_wb_winner');
        $this->dropFkIfExists('idempotency_keys', 'fk_ik_user');
        $this->dropFkIfExists('system_logs', 'fk_sl_user');

        // Remove FKs to user_pets
        $this->dropFkIfExists('battle_participants', 'fk_bp_pet');
        $this->dropFkIfExists('pet_battles', 'fk_pbt_winner_pet');
        $this->dropFkIfExists('pet_evolution_history', 'fk_peh_main_pet');
        $this->dropFkIfExists('war_battles', 'fk_wb_user_pet');
        $this->dropFkIfExists('war_battles', 'fk_wb_opp_pet');
    }

    // ──────────────────────────────────────────────
    // Helper methods
    // ──────────────────────────────────────────────

    /**
     * Add index on a column if it doesn't already have one.
     */
    private function addIndexIfNotExists(string $table, string $column): void
    {
        $result = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ", [$table, $column])->getRow();

        if ((int) $result->cnt === 0) {
            $this->db->query("ALTER TABLE `{$table}` ADD INDEX `idx_{$table}_{$column}` (`{$column}`)");
        }
    }

    /**
     * Add FK constraint only if it doesn't already exist.
     */
    private function addFkIfNotExists(
        string $table,
        string $fkName,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete = 'CASCADE'
    ): void {
        $result = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $fkName])->getRow();

        if ((int) $result->cnt === 0) {
            $this->db->query("
                ALTER TABLE `{$table}`
                ADD CONSTRAINT `{$fkName}`
                FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`{$refColumn}`)
                ON DELETE {$onDelete}
                ON UPDATE NO ACTION
            ");
        }
    }

    /**
     * Drop FK constraint only if it exists.
     */
    private function dropFkIfExists(string $table, string $fkName): void
    {
        $result = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $fkName])->getRow();

        if ((int) $result->cnt > 0) {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
        }
    }
}
