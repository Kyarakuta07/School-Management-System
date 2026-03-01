<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdempotencyKeys extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'action'], 'uq_user_action');
        $this->forge->addKey('created_at', false, false, 'idx_created');

        $this->forge->createTable('idempotency_keys', true); // TRUE means IF NOT EXISTS
    }

    public function down()
    {
        $this->forge->dropTable('idempotency_keys', true);
    }
}
