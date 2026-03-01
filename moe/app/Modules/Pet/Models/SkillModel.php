<?php

namespace App\Modules\Pet\Models;

use CodeIgniter\Model;

class SkillModel extends Model
{
    protected $table = 'pet_skills';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'species_id',
        'skill_name',
        'skill_desc',
        'skill_type',
        'base_damage',
        'skill_element',
        'skill_slot',
        'status_effect',
        'status_chance',
        'status_duration'
    ];

    /**
     * Get all skills for a species
     */
    public function getBySpecies(int $speciesId)
    {
        return $this->where('species_id', $speciesId)
            ->orderBy('skill_slot', 'ASC')
            ->findAll();
    }
}
