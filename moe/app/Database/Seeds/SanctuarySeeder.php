<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SanctuarySeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id_sanctuary' => 1,
                'nama_sanctuary' => 'AMMIT',
                'deskripsi' => "Sanctuary Ammit, the Bastion of Judgment. Forged for those with hearts as light as Ma'at's feather and spirits unyielding as the Devourer herself.",
                'gold' => 0,
            ],
            [
                'id_sanctuary' => 2,
                'nama_sanctuary' => 'HATHOR',
                'deskripsi' => 'Sanctuary Hathor, House of Love and Beauty, blessed by the goddess of joy and motherhood. Its members embody grace, creativity, and harmony in all things.',
                'gold' => 0,
            ],
            [
                'id_sanctuary' => 3,
                'nama_sanctuary' => 'HORUS',
                'deskripsi' => 'Sanctuary Horus, House of the Sky God, the falcon-headed avenger. Its members soar above the ordinary, blessed with keen vision and unwavering focus.',
                'gold' => 0,
            ],
            [
                'id_sanctuary' => 4,
                'nama_sanctuary' => 'KHONSU',
                'deskripsi' => 'Sanctuary Khonsu, blessed by the Moon God who traverses the night sky. Its children are guided by lunar wisdom, mastering the flow of time and natural cycles.',
                'gold' => 0,
            ],
            [
                'id_sanctuary' => 5,
                'nama_sanctuary' => 'OSIRIS',
                'deskripsi' => 'Sanctuary Osiris, guardian of the sacred Underworld and lord of resurrection. Its children understand the profound mysteries of transformation, rebirth, and the eternal balance.',
                'gold' => 0,
            ],
        ];

        $table = $this->db->table('sanctuary');
        foreach ($data as $row) {
            // Upsert: insert or update if exists
            $exists = $this->db->table('sanctuary')->where('id_sanctuary', $row['id_sanctuary'])->countAllResults();
            if ($exists === 0) {
                $table->insert($row);
            }
        }

        echo "SanctuarySeeder: 5 sanctuaries seeded.\n";
    }
}
