<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBattleSessionTables extends Migration
{
    public function up()
    {
        // 1. battle_sessions
        if (!$this->db->tableExists('battle_sessions')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'battle_type' => ['type' => 'ENUM', 'constraint' => ['1v1', '3v3'], 'default' => '3v3'],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'victory', 'defeat'], 'default' => 'active'],
                'current_turn_user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'winner_user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'default' => null],
                'updated_at' => ['type' => 'TIMESTAMP', 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('status');
            $this->forge->createTable('battle_sessions');
        }

        // 2. battle_participants
        if (!$this->db->tableExists('battle_participants')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'battle_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'pet_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'team_index' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'is_ai' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('battle_id');
            $this->forge->addKey('user_id');
            $this->forge->addKey('pet_id');
            $this->forge->addForeignKey('battle_id', 'battle_sessions', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('battle_participants');
        }
    }

    public function down()
    {
        $this->forge->dropTable('battle_participants', true);
        $this->forge->dropTable('battle_sessions', true);
    }
}
