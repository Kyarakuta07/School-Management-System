<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ClassScheduleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['id_schedule' => 1, 'class_name' => 'Oceanology', 'hakaes_name' => 'Hakaes Name 1', 'schedule_day' => 'Senin', 'schedule_time' => '19:00 WIB', 'class_image_url' => 'url_gambar_oceanology.jpg'],
            ['id_schedule' => 2, 'class_name' => 'Astronomy', 'hakaes_name' => 'Hakaes Name 2', 'schedule_day' => 'Selasa', 'schedule_time' => '20:00 WIB', 'class_image_url' => 'url_gambar_english.jpg'],
            ['id_schedule' => 3, 'class_name' => 'Herbology', 'hakaes_name' => 'Hakaes Name 3', 'schedule_day' => 'Rabu', 'schedule_time' => '19:30 WIB', 'class_image_url' => 'url_gambar_herbology.jpg'],
            ['id_schedule' => 4, 'class_name' => 'History', 'hakaes_name' => 'Hakaes Name 4', 'schedule_day' => 'Kamis', 'schedule_time' => '20:30 WIB', 'class_image_url' => 'url_gambar_history.jpg'],
        ];

        $table = $this->db->table('class_schedule');
        foreach ($data as $row) {
            $exists = $this->db->table('class_schedule')->where('id_schedule', $row['id_schedule'])->countAllResults();
            if ($exists === 0) {
                $table->insert($row);
            }
        }

        echo "ClassScheduleSeeder: " . count($data) . " schedules seeded.\n";
    }
}
