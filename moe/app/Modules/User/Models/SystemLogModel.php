<?php

namespace App\Modules\User\Models;

use CodeIgniter\Model;

class SystemLogModel extends Model
{
    protected $table = 'system_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    // Dates
    protected $useTimestamps = false; // We set created_at manually in service or use CI auto if named correctly

    /**
     * Delete logs older than X days
     */
    public function prune(int $days = 30): int
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->where('created_at <', $threshold)->delete();
    }
}
