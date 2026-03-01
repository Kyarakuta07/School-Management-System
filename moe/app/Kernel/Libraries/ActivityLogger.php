<?php

namespace App\Kernel\Libraries;

use Config\Database;

/**
 * Activity Logger
 *
 * Provides audit trail for admin actions.
 * Ported from legacy moe/core/activity_logger.php.
 */
class ActivityLogger
{
    protected $db;

    // Action constants
    public const CREATE = 'CREATE';
    public const UPDATE = 'UPDATE';
    public const DELETE = 'DELETE';
    public const LOGIN = 'LOGIN';
    public const LOGOUT = 'LOGOUT';
    public const APPROVE = 'APPROVE';
    public const REJECT = 'REJECT';

    public function __construct()
    {
        $this->db = Database::connect();
        $this->ensureTableExists();
    }

    /**
     * Log an admin activity.
     */
    public function log(
        string $action,
        string $entity,
        ?int $entityId = null,
        string $description = '',
        ?array $oldData = null,
        ?array $newData = null
    ): bool {
        $adminId = session('id_nethera');
        $adminName = session('nama_lengkap') ?? 'System';
        $ip = service('request')->getIPAddress();

        $data = [
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'description' => $description,
            'old_data' => $oldData ? json_encode($oldData) : null,
            'new_data' => $newData ? json_encode($newData) : null,
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->db->table('admin_activity_log')->insert($data);
    }

    /** Shorthand: log a CREATE action. */
    public function logCreate(string $entity, ?int $entityId, string $desc, ?array $newData = null): bool
    {
        return $this->log(self::CREATE, $entity, $entityId, $desc, null, $newData);
    }

    /** Shorthand: log an UPDATE action. */
    public function logUpdate(string $entity, ?int $entityId, string $desc, ?array $oldData = null, ?array $newData = null): bool
    {
        return $this->log(self::UPDATE, $entity, $entityId, $desc, $oldData, $newData);
    }

    /** Shorthand: log a DELETE action. */
    public function logDelete(string $entity, ?int $entityId, string $desc, ?array $oldData = null): bool
    {
        return $this->log(self::DELETE, $entity, $entityId, $desc, $oldData);
    }

    /** Shorthand: log a LOGIN / LOGOUT. */
    public function logAuth(string $action, string $desc): bool
    {
        return $this->log($action, 'auth', null, $desc);
    }

    /**
     * Retrieve activity logs with optional filters.
     */
    public function getLogs(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        $builder = $this->db->table('admin_activity_log')->orderBy('created_at', 'DESC');

        if (!empty($filters['action'])) {
            $builder->where('action', $filters['action']);
        }
        if (!empty($filters['entity'])) {
            $builder->where('entity', $filters['entity']);
        }
        if (!empty($filters['admin_id'])) {
            $builder->where('admin_id', $filters['admin_id']);
        }
        if (!empty($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }

        return $builder->limit($limit, $offset)->get()->getResultArray();
    }

    /**
     * Return basic statistics for the last N days.
     */
    public function getStats(int $days = 30): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $total = $this->db->table('admin_activity_log')
            ->where('created_at >=', $since)
            ->countAllResults();

        $byAction = $this->db->table('admin_activity_log')
            ->select('action, COUNT(*) as cnt')
            ->where('created_at >=', $since)
            ->groupBy('action')
            ->get()
            ->getResultArray();

        return ['total' => $total, 'by_action' => $byAction];
    }

    /**
     * Auto-create the log table if it does not exist.
     */
    private function ensureTableExists(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS admin_activity_log (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            admin_id    INT NULL,
            admin_name  VARCHAR(100),
            action      VARCHAR(20)  NOT NULL,
            entity      VARCHAR(50)  NOT NULL,
            entity_id   INT NULL,
            description TEXT,
            old_data    JSON NULL,
            new_data    JSON NULL,
            ip_address  VARCHAR(45),
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action   (action),
            INDEX idx_entity   (entity),
            INDEX idx_admin_id (admin_id),
            INDEX idx_created  (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
