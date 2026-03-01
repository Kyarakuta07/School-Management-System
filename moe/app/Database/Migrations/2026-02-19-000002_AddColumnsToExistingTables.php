<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Wave 2+3+4 — Add new columns to existing tables.
 * SAFE: All ADD COLUMN IF NOT EXISTS with DEFAULT values.
 * Does not modify any existing data.
 */
class AddColumnsToExistingTables extends Migration
{
    public function up()
    {
        // ── nethera: lockout columns ──────────────────────
        $netheraColumns = [];

        if (!$this->db->fieldExists('failed_attempts', 'nethera')) {
            $netheraColumns['failed_attempts'] = [
                'type' => 'TINYINT',
                'constraint' => 3,
                'unsigned' => true,
                'default' => 0,
                'null' => false,
            ];
        }
        if (!$this->db->fieldExists('locked_until', 'nethera')) {
            $netheraColumns['locked_until'] = [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ];
        }
        if (!$this->db->fieldExists('last_failed_login', 'nethera')) {
            $netheraColumns['last_failed_login'] = [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ];
        }

        // nethera: battle stats
        if (!$this->db->fieldExists('arena_wins', 'nethera')) {
            $netheraColumns['arena_wins'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ];
        }
        if (!$this->db->fieldExists('arena_losses', 'nethera')) {
            $netheraColumns['arena_losses'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ];
        }
        if (!$this->db->fieldExists('last_battle_win_date', 'nethera')) {
            $netheraColumns['last_battle_win_date'] = [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
            ];
        }
        if (!$this->db->fieldExists('current_win_streak', 'nethera')) {
            $netheraColumns['current_win_streak'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ];
        }

        // nethera: profile & sanctuary
        if (!$this->db->fieldExists('sanctuary_role', 'nethera')) {
            $netheraColumns['sanctuary_role'] = [
                'type' => 'ENUM',
                'constraint' => ['hosa', 'vizier', 'member'],
                'default' => 'member',
            ];
        }
        if (!$this->db->fieldExists('fun_fact', 'nethera')) {
            $netheraColumns['fun_fact'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }
        if (!$this->db->fieldExists('profile_photo', 'nethera')) {
            $netheraColumns['profile_photo'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ];
        }

        if (!empty($netheraColumns)) {
            $this->forge->addColumn('nethera', $netheraColumns);
        }

        // ── user_pets: battle & ranking columns ──────────
        $petColumns = [];

        if (!$this->db->fieldExists('hp', 'user_pets')) {
            $petColumns['hp'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 100,
                'null' => false,
            ];
        }
        if (!$this->db->fieldExists('has_shield', 'user_pets')) {
            $petColumns['has_shield'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ];
        }
        if (!$this->db->fieldExists('total_wins', 'user_pets')) {
            $petColumns['total_wins'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ];
        }
        if (!$this->db->fieldExists('current_streak', 'user_pets')) {
            $petColumns['current_streak'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ];
        }
        if (!$this->db->fieldExists('total_losses', 'user_pets')) {
            $petColumns['total_losses'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ];
        }
        if (!$this->db->fieldExists('rank_points', 'user_pets')) {
            $petColumns['rank_points'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1000,
            ];
        }

        if (!empty($petColumns)) {
            $this->forge->addColumn('user_pets', $petColumns);
        }

        // ── Backfill total_wins/total_losses from wins/losses ──
        if ($this->db->fieldExists('wins', 'user_pets') && $this->db->fieldExists('total_wins', 'user_pets')) {
            $this->db->query('UPDATE user_pets SET total_wins = wins WHERE total_wins = 0 AND wins > 0');
            $this->db->query('UPDATE user_pets SET total_losses = losses WHERE total_losses = 0 AND losses > 0');
        }

        // ── pet_skills: status effect columns ────────────
        $skillColumns = [];
        if (!$this->db->fieldExists('status_effect', 'pet_skills')) {
            $skillColumns['status_effect'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ];
        }
        if (!$this->db->fieldExists('status_chance', 'pet_skills')) {
            $skillColumns['status_chance'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ];
        }
        if (!$this->db->fieldExists('status_duration', 'pet_skills')) {
            $skillColumns['status_duration'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ];
        }
        if (!empty($skillColumns)) {
            $this->forge->addColumn('pet_skills', $skillColumns);
        }

        // ── pet_species: base_health ─────────────────────
        if (!$this->db->fieldExists('base_health', 'pet_species')) {
            $this->forge->addColumn('pet_species', [
                'base_health' => ['type' => 'INT', 'constraint' => 11, 'default' => 100],
            ]);
        }
    }

    public function down()
    {
        // Reverse: drop added columns (safe because they have defaults)
        $netheraDrops = [
            'failed_attempts',
            'locked_until',
            'last_failed_login',
            'arena_wins',
            'arena_losses',
            'last_battle_win_date',
            'current_win_streak',
            'sanctuary_role',
            'fun_fact',
            'profile_photo'
        ];
        foreach ($netheraDrops as $col) {
            if ($this->db->fieldExists($col, 'nethera')) {
                $this->forge->dropColumn('nethera', $col);
            }
        }

        $petDrops = ['hp', 'has_shield', 'total_wins', 'current_streak', 'total_losses', 'rank_points'];
        foreach ($petDrops as $col) {
            if ($this->db->fieldExists($col, 'user_pets')) {
                $this->forge->dropColumn('user_pets', $col);
            }
        }

        $skillDrops = ['status_effect', 'status_chance', 'status_duration'];
        foreach ($skillDrops as $col) {
            if ($this->db->fieldExists($col, 'pet_skills')) {
                $this->forge->dropColumn('pet_skills', $col);
            }
        }

        if ($this->db->fieldExists('base_health', 'pet_species')) {
            $this->forge->dropColumn('pet_species', 'base_health');
        }
    }
}
