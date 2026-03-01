<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDailyLoginStreak extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('daily_login_streak')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'current_day' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                ],
                'last_claim_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'total_logins' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('user_id');
            $this->forge->addForeignKey('user_id', 'nethera', 'id_nethera', 'CASCADE', 'NO ACTION');
            $this->forge->createTable('daily_login_streak');
        }
    }

    public function down()
    {
        $this->forge->dropTable('daily_login_streak', true);
    }
}
