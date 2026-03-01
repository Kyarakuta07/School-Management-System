<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ElementResistanceSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['element' => 'Fire', 'resists_status' => 'burn', 'resistance_percent' => 100],
            ['element' => 'Fire', 'resists_status' => 'freeze', 'resistance_percent' => 75],
            ['element' => 'Water', 'resists_status' => 'burn', 'resistance_percent' => 75],
            ['element' => 'Earth', 'resists_status' => 'stun', 'resistance_percent' => 50],
            ['element' => 'Earth', 'resists_status' => 'poison', 'resistance_percent' => 50],
            ['element' => 'Air', 'resists_status' => 'stun', 'resistance_percent' => 75],
            ['element' => 'Light', 'resists_status' => 'atk_down', 'resistance_percent' => 50],
            ['element' => 'Light', 'resists_status' => 'def_down', 'resistance_percent' => 50],
            ['element' => 'Dark', 'resists_status' => 'poison', 'resistance_percent' => 75],
        ];

        $table = $this->db->table('element_status_resistance');
        foreach ($data as $row) {
            $exists = $this->db->table('element_status_resistance')
                ->where('element', $row['element'])
                ->where('resists_status', $row['resists_status'])
                ->countAllResults();
            if ($exists === 0) {
                $table->insert($row);
            }
        }

        echo "ElementResistanceSeeder: " . count($data) . " resistances seeded.\n";
    }
}
