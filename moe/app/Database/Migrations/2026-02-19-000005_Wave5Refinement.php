<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Wave5Refinement extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. STAT CONSOLIDATION: Drop legacy wins/losses from user_pets
        if ($this->db->fieldExists('wins', 'user_pets')) {
            $this->forge->dropColumn('user_pets', 'wins');
        }
        if ($this->db->fieldExists('losses', 'user_pets')) {
            $this->forge->dropColumn('user_pets', 'losses');
        }

        // 2. ADD MISSING FOREIGN KEYS

        // trapeza_transactions.receiver_id -> nethera.id_nethera
        try {
            $db->query("ALTER TABLE trapeza_transactions 
                ADD CONSTRAINT fk_trapeza_receiver FOREIGN KEY (receiver_id) 
                REFERENCES nethera(id_nethera) ON DELETE CASCADE");
        } catch (\Exception $e) {
            // Log if it's not a 'duplicate key' error, or just continue if it is
        }

        // punishment_log.given_by -> nethera.id_nethera
        try {
            $db->query("ALTER TABLE punishment_log 
                ADD CONSTRAINT fk_punishment_giver FOREIGN KEY (given_by) 
                REFERENCES nethera(id_nethera) ON DELETE SET NULL");
        } catch (\Exception $e) {
        }

        // security_logs.user_id -> nethera.id_nethera
        try {
            $db->query("ALTER TABLE security_logs 
                ADD CONSTRAINT fk_security_log_user FOREIGN KEY (user_id) 
                REFERENCES nethera(id_nethera) ON DELETE SET NULL");
        } catch (\Exception $e) {
        }

        // admin_activity_log.admin_id -> nethera.id_nethera
        try {
            $db->query("ALTER TABLE admin_activity_log 
                ADD CONSTRAINT fk_admin_log_user FOREIGN KEY (admin_id) 
                REFERENCES nethera(id_nethera) ON DELETE CASCADE");
        } catch (\Exception $e) {
        }

        // 3. ADD SUGGESTED INDEXES
        $db->query("CREATE INDEX idx_user_status ON user_pets (user_id, status)");
        $db->query("CREATE INDEX idx_nethera_status ON punishment_log (id_nethera, status_hukuman)");
        $db->query("CREATE INDEX idx_role ON nethera (role)");
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Drop Indexes
        $db->query("DROP INDEX idx_user_status ON user_pets");
        $db->query("DROP INDEX idx_nethera_status ON punishment_log");
        $db->query("DROP INDEX idx_role ON nethera");

        // Drop Foreign Keys
        $db->query("ALTER TABLE trapeza_transactions DROP FOREIGN KEY fk_trapeza_receiver");
        $db->query("ALTER TABLE punishment_log DROP FOREIGN KEY fk_punishment_giver");
        $db->query("ALTER TABLE security_logs DROP FOREIGN KEY fk_security_log_user");
        $db->query("ALTER TABLE admin_activity_log DROP FOREIGN KEY fk_admin_log_user");

        // Restore columns
        $this->forge->addColumn('user_pets', [
            'wins' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'losses' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
    }
}
