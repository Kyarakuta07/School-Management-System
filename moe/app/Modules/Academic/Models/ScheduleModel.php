<?php

namespace App\Modules\Academic\Models;

use CodeIgniter\Model;

class ScheduleModel extends Model
{
    protected $table = 'class_schedule';
    protected $primaryKey = 'id_schedule';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'class_name',
        'hakaes_name',
        'schedule_day',
        'schedule_time',
        'class_description',
        'class_image_url'
    ];

    /**
     * Get all schedules ordered by day of week and time.
     */
    public function getOrdered(): array
    {
        return $this->orderBy("FIELD(schedule_day, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')", '', false)
            ->orderBy('schedule_time', 'ASC')
            ->findAll();
    }

    /**
     * Get subject assigned to a Hakaes by their user ID.
     * Returns ['class_name' => '...'] or null.
     */
    public function getHakaesSubject(int $hakaesId): ?array
    {
        return $this->select('class_name')
            ->where('id_hakaes', $hakaesId)
            ->first();
    }
}
