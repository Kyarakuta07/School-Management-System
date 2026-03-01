<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $nethera = $this->db->table('nethera');

        // Admin (Vasiki role)
        $adminExists = $this->db->table('nethera')->where('email', 'admin@moe.local')->countAllResults();
        if ($adminExists === 0) {
            $nethera->insert([
                'no_registrasi' => 'ADM-001',
                'nama_lengkap' => 'Admin MOE',
                'username' => 'admin',
                'email' => 'admin@moe.local',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'role' => 'Vasiki',
                'status_akun' => 'Aktif',
                'gold' => 10000,
                'id_sanctuary' => 1,
                'sanctuary_role' => 'hosa',
            ]);
            echo "AdminUserSeeder: Admin user created.\n";
        }

        // Test Student (Nethera role)
        $studentExists = $this->db->table('nethera')->where('email', 'student@moe.local')->countAllResults();
        if ($studentExists === 0) {
            $nethera->insert([
                'no_registrasi' => 'STD-001',
                'nama_lengkap' => 'Test Student',
                'username' => 'student',
                'email' => 'student@moe.local',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'role' => 'Nethera',
                'status_akun' => 'Aktif',
                'gold' => 500,
                'id_sanctuary' => 2,
                'sanctuary_role' => 'member',
            ]);
            echo "AdminUserSeeder: Test student created.\n";
        }

        // Teacher (Hakaes role)
        $teacherExists = $this->db->table('nethera')->where('email', 'teacher@moe.local')->countAllResults();
        if ($teacherExists === 0) {
            $nethera->insert([
                'no_registrasi' => 'HKS-001',
                'nama_lengkap' => 'Test Teacher',
                'username' => 'teacher',
                'email' => 'teacher@moe.local',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'role' => 'Hakaes',
                'status_akun' => 'Aktif',
                'gold' => 1000,
                'id_sanctuary' => 3,
                'sanctuary_role' => 'vizier',
            ]);
            echo "AdminUserSeeder: Test teacher created.\n";
        }
    }
}
