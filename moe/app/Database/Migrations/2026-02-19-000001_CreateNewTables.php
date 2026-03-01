<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Wave 1 — Create all new tables that may not exist in production.
 * SAFE: Uses IF NOT EXISTS. Does not modify any existing data.
 */
class CreateNewTables extends Migration
{
    public function up()
    {
        // 1. element_status_resistance
        if (!$this->db->tableExists('element_status_resistance')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'element' => ['type' => 'ENUM', 'constraint' => ['Fire', 'Water', 'Earth', 'Air', 'Light', 'Dark'], 'null' => false],
                'resists_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
                'resistance_percent' => ['type' => 'INT', 'constraint' => 11, 'default' => 50],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['element', 'resists_status'], 'unique_element_status');
            $this->forge->createTable('element_status_resistance');
        }

        // 2. pet_evolution_history
        if (!$this->db->tableExists('pet_evolution_history')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'main_pet_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'fodder_pet_ids' => ['type' => 'LONGTEXT', 'null' => false],
                'gold_cost' => ['type' => 'INT', 'constraint' => 11, 'default' => 500],
                'evolution_date' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey(['user_id', 'evolution_date'], false, false, 'idx_user_evolutions');
            $this->forge->addForeignKey('user_id', 'nethera', 'id_nethera', 'CASCADE', 'NO ACTION');
            $this->forge->createTable('pet_evolution_history');
        }

        // 3. punishment_log
        if (!$this->db->tableExists('punishment_log')) {
            $this->forge->addField([
                'id_punishment' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'id_nethera' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'jenis_pelanggaran' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
                'deskripsi_pelanggaran' => ['type' => 'TEXT', 'null' => true],
                'jenis_hukuman' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
                'poin_pelanggaran' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'status_hukuman' => ['type' => 'ENUM', 'constraint' => ['active', 'completed', 'waived'], 'default' => 'active'],
                'tanggal_pelanggaran' => ['type' => 'DATETIME', 'null' => true],
                'tanggal_selesai' => ['type' => 'DATETIME', 'null' => true],
                'locked_features' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'trapeza,pet,class'],
                'given_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'released_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
                'updated_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id_punishment');
            $this->forge->addKey('id_nethera', false, false, 'idx_nethera');
            $this->forge->addKey('status_hukuman', false, false, 'idx_status');
            $this->forge->addKey('given_by', false, false, 'idx_given_by');
            $this->forge->addForeignKey('id_nethera', 'nethera', 'id_nethera', 'CASCADE', 'CASCADE');
            $this->forge->createTable('punishment_log');
        }

        // 4. leaderboard_history (depends on user_pets)
        if (!$this->db->tableExists('leaderboard_history')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'month_year' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
                'rank' => ['type' => 'TINYINT', 'constraint' => 4, 'default' => 1],
                'pet_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'sort_type' => ['type' => 'ENUM', 'constraint' => ['level', 'wins', 'power'], 'default' => 'level'],
                'score' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('month_year', false, false, 'idx_month');
            $this->forge->addKey('rank', false, false, 'idx_rank');
            $this->forge->addKey('pet_id');
            $this->forge->addForeignKey('pet_id', 'user_pets', 'id', 'CASCADE', 'NO ACTION');
            $this->forge->createTable('leaderboard_history');
        }

        // 5. sanctuary_daily_claims
        if (!$this->db->tableExists('sanctuary_daily_claims')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'sanctuary_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'last_claim' => ['type' => 'DATETIME', 'null' => false],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['user_id', 'sanctuary_id'], 'unique_user_sanctuary');
            $this->forge->addKey('sanctuary_id');
            $this->forge->addForeignKey('user_id', 'nethera', 'id_nethera', 'CASCADE', 'NO ACTION');
            $this->forge->addForeignKey('sanctuary_id', 'sanctuary', 'id_sanctuary', 'CASCADE', 'NO ACTION');
            $this->forge->createTable('sanctuary_daily_claims');
        }

        // 6. sanctuary_upgrades
        if (!$this->db->tableExists('sanctuary_upgrades')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'sanctuary_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'upgrade_type' => ['type' => 'ENUM', 'constraint' => ['training_dummy', 'beastiary', 'crystal_vault'], 'null' => false],
                'level' => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['sanctuary_id', 'upgrade_type'], 'unique_upgrade');
            $this->forge->addForeignKey('sanctuary_id', 'sanctuary', 'id_sanctuary', 'CASCADE', 'NO ACTION');
            $this->forge->createTable('sanctuary_upgrades');
        }

        // 7. sanctuary_wars
        if (!$this->db->tableExists('sanctuary_wars')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'war_date' => ['type' => 'DATE', 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'finished'], 'default' => 'active'],
                'winner_sanctuary_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey('war_date');
            $this->forge->addKey('winner_sanctuary_id');
            $this->forge->createTable('sanctuary_wars');
        }

        // 8. sanctuary_war_scores (depends on sanctuary_wars)
        if (!$this->db->tableExists('sanctuary_war_scores')) {
            $this->forge->addField([
                'war_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'sanctuary_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'total_points' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'wins' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'losses' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'ties' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            ]);
            $this->forge->addPrimaryKey(['war_id', 'sanctuary_id']);
            $this->forge->addKey('sanctuary_id');
            $this->forge->addForeignKey('war_id', 'sanctuary_wars', 'id', 'CASCADE', 'NO ACTION');
            $this->forge->addForeignKey('sanctuary_id', 'sanctuary', 'id_sanctuary', 'NO ACTION', 'NO ACTION');
            $this->forge->createTable('sanctuary_war_scores');
        }

        // 9. war_battles (depends on sanctuary_wars)
        if (!$this->db->tableExists('war_battles')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'war_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'opponent_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'user_sanctuary_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'opponent_sanctuary_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'user_pet_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'opponent_pet_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'winner_user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'points_earned' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'gold_earned' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('war_id', false, false, 'idx_war_battles_war');
            $this->forge->addKey('user_id', false, false, 'idx_war_battles_user');
            $this->forge->addKey('opponent_id');
            $this->forge->addKey('user_sanctuary_id');
            $this->forge->addKey('opponent_sanctuary_id');
            $this->forge->addKey('created_at', false, false, 'idx_war_battles_created');
            $this->forge->addForeignKey('war_id', 'sanctuary_wars', 'id', 'CASCADE', 'NO ACTION');
            $this->forge->addForeignKey('user_id', 'nethera', 'id_nethera');
            $this->forge->addForeignKey('opponent_id', 'nethera', 'id_nethera');
            $this->forge->addForeignKey('user_sanctuary_id', 'sanctuary', 'id_sanctuary');
            $this->forge->addForeignKey('opponent_sanctuary_id', 'sanctuary', 'id_sanctuary');
            $this->forge->createTable('war_battles');
        }

        // 10. war_user_tickets
        if (!$this->db->tableExists('war_user_tickets')) {
            $this->forge->addField([
                'war_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'tickets_used' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            ]);
            $this->forge->addPrimaryKey(['war_id', 'user_id']);
            $this->forge->addKey('user_id');
            $this->forge->addForeignKey('war_id', 'sanctuary_wars', 'id', 'CASCADE', 'NO ACTION');
            $this->forge->addForeignKey('user_id', 'nethera', 'id_nethera');
            $this->forge->createTable('war_user_tickets');
        }

        // 11. rate_limits
        if (!$this->db->tableExists('rate_limits')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'identifier' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'action' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'attempts' => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'last_attempt' => ['type' => 'DATETIME', 'null' => true],
                'locked_until' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey(['identifier', 'action'], false, false, 'idx_identifier_action');
            $this->forge->addKey('locked_until', false, false, 'idx_locked');
            $this->forge->createTable('rate_limits');
        }

        // 12. security_logs
        if (!$this->db->tableExists('security_logs')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'event_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'user_agent' => ['type' => 'TEXT', 'null' => true],
                'details' => ['type' => 'TEXT', 'null' => true],
                'severity' => ['type' => 'ENUM', 'constraint' => ['info', 'warning', 'critical'], 'default' => 'info'],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('event_type', false, false, 'idx_event_type');
            $this->forge->addKey('user_id', false, false, 'idx_user_id');
            $this->forge->addKey('severity', false, false, 'idx_severity');
            $this->forge->addKey('created_at', false, false, 'idx_created_at');
            $this->forge->createTable('security_logs');
        }

        // 13. admin_activity_log
        if (!$this->db->tableExists('admin_activity_log')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'admin_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'admin_username' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'action' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
                'entity' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'entity_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'description' => ['type' => 'TEXT', 'null' => true],
                'changes' => ['type' => 'LONGTEXT', 'null' => true],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('admin_id', false, false, 'idx_admin_id');
            $this->forge->addKey('action', false, false, 'idx_action');
            $this->forge->addKey('entity', false, false, 'idx_entity');
            $this->forge->addKey('created_at', false, false, 'idx_created_at');
            $this->forge->createTable('admin_activity_log');
        }

        // 14. Rhythm game tables
        if (!$this->db->tableExists('rhythm_songs')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'title' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'artist' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'Unknown'],
                'audio_file' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'cover_image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'bpm' => ['type' => 'INT', 'constraint' => 11, 'default' => 120],
                'duration_sec' => ['type' => 'INT', 'constraint' => 11, 'default' => 120],
                'difficulty' => ['type' => 'ENUM', 'constraint' => ['Easy', 'Medium', 'Hard', 'Expert'], 'default' => 'Medium'],
                'base_xp' => ['type' => 'INT', 'constraint' => 11, 'default' => 50],
                'base_gold' => ['type' => 'INT', 'constraint' => 11, 'default' => 100],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->createTable('rhythm_songs');
        }

        if (!$this->db->tableExists('rhythm_beatmaps')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'song_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'difficulty_name' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Normal'],
                'note_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
                'beatmap_data' => ['type' => 'LONGTEXT', 'null' => false],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('song_id');
            $this->forge->addForeignKey('song_id', 'rhythm_songs', 'id', 'CASCADE', 'NO ACTION');
            $this->forge->createTable('rhythm_beatmaps');
        }

        if (!$this->db->tableExists('rhythm_scores')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'song_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'score' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'max_combo' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'perfect_plus_hits' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'perfect_hits' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'great_hits' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'good_hits' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'miss_hits' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'rank_grade' => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'F'],
                'gold_earned' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'exp_earned' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('song_id');
            $this->forge->addKey(['user_id', 'song_id'], false, false, 'idx_user_song');
            $this->forge->addKey('score', false, false, 'idx_score');
            $this->forge->addForeignKey('user_id', 'nethera', 'id_nethera', 'CASCADE', 'NO ACTION');
            $this->forge->addForeignKey('song_id', 'rhythm_songs', 'id', 'CASCADE', 'NO ACTION');
            $this->forge->createTable('rhythm_scores');
        }
    }

    public function down()
    {
        // Reverse order (respect FK dependencies)
        $this->forge->dropTable('rhythm_scores', true);
        $this->forge->dropTable('rhythm_beatmaps', true);
        $this->forge->dropTable('rhythm_songs', true);
        $this->forge->dropTable('admin_activity_log', true);
        $this->forge->dropTable('security_logs', true);
        $this->forge->dropTable('rate_limits', true);
        $this->forge->dropTable('war_user_tickets', true);
        $this->forge->dropTable('war_battles', true);
        $this->forge->dropTable('sanctuary_war_scores', true);
        $this->forge->dropTable('sanctuary_wars', true);
        $this->forge->dropTable('sanctuary_upgrades', true);
        $this->forge->dropTable('sanctuary_daily_claims', true);
        $this->forge->dropTable('leaderboard_history', true);
        $this->forge->dropTable('punishment_log', true);
        $this->forge->dropTable('pet_evolution_history', true);
        $this->forge->dropTable('element_status_resistance', true);
    }
}
