<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Auth Security Fields Migration
 *
 * - Widen otp_code VARCHAR(6) → VARCHAR(64) for SHA-256 hash storage
 * - Add otp_attempts for per-user OTP brute-force protection
 * - Add approved_at / approved_by for admin approval audit trail
 * - Backfill email_verified_at for existing Aktif users
 */
class AuthSecurityFields extends Migration
{
    public function up()
    {
        // 1. Widen otp_code to hold SHA-256 hashes (64 hex chars)
        $this->forge->modifyColumn('nethera', [
            'otp_code' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'default' => null,
            ],
        ]);

        // 2. Add missing security columns
        $this->forge->addColumn('nethera', [
            'otp_attempts' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
                'after' => 'otp_expires',
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
                'after' => 'status_akun',
            ],
            'approved_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
                'after' => 'approved_at',
            ],
        ]);

        // 3. Backfill email_verified_at for currently-active users
        //    (column exists but is NULL — must be set to avoid lockouts
        //     when login starts checking email_verified_at IS NOT NULL)
        $this->db->query("
            UPDATE nethera
               SET email_verified_at = created_at,
                   approved_at       = created_at
             WHERE status_akun = 'Aktif'
               AND email_verified_at IS NULL
        ");
    }

    public function down()
    {
        // Remove added columns
        $this->forge->dropColumn('nethera', ['otp_attempts', 'approved_at', 'approved_by']);

        // Revert otp_code width
        $this->forge->modifyColumn('nethera', [
            'otp_code' => [
                'type' => 'VARCHAR',
                'constraint' => 6,
                'null' => true,
                'default' => null,
            ],
        ]);
    }
}
