<?php

namespace App\Modules\User\Services;

use App\Modules\User\Models\SystemLogModel;
use Config\Services;

class ActivityLogService
{
    protected $logModel;
    protected $request;

    public function __construct()
    {
        $this->logModel = new SystemLogModel();
        $this->request = Services::request();
    }

    /**
     * Log a system activity
     * 
     * @param string $action The action performed (e.g., 'LOGIN', 'TRANSFER')
     * @param string $module The module where the action occurred (e.g., 'AUTH', 'BANK')
     * @param string $description Detailed description of the event
     * @param int|null $userId Optional user ID, defaults to session user if available
     */
    public function log(string $action, string $module, string $description, ?int $userId = null): void
    {
        if ($userId === null) {
            $session = Services::session();
            $userId = $session->get('id_nethera');
        }

        $this->logModel->insert([
            'user_id' => $userId,
            'action' => strtoupper($action),
            'module' => strtoupper($module),
            'description' => $description,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Prune old logs
     */
    public function cleanup(int $days = 30): int
    {
        return $this->logModel->prune($days);
    }
}
