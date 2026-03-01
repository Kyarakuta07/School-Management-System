<?php

namespace App\Kernel\Libraries;

use Config\Database;

/**
 * Security Logger
 *
 * Logs security-related events for audit trail.
 * Ported from legacy moe/core/security_logger.php.
 */
class SecurityLogger
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->ensureTableExists();
    }

    /**
     * Log a security event.
     */
    public function logEvent(
        string $eventType,
        ?int $userId,
        string $details,
        string $severity = 'info'
    ): bool {
        $valid = ['info', 'warning', 'critical'];
        if (!in_array($severity, $valid, true)) {
            $severity = 'info';
        }

        $request = service('request');
        $ip = $request->getIPAddress();
        $userAgent = substr($request->getUserAgent()->getAgentString() ?? 'unknown', 0, 500);

        return $this->db->table('security_logs')->insert([
            'event_type' => $eventType,
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'details' => $details,
            'severity' => $severity,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Retrieve recent security logs.
     */
    public function getLogs(int $limit = 100, ?string $eventType = null, ?string $severity = null): array
    {
        $builder = $this->db->table('security_logs sl')
            ->select('sl.*, n.username, n.nama_lengkap')
            ->join('nethera n', 'sl.user_id = n.id_nethera', 'left')
            ->orderBy('sl.created_at', 'DESC');

        if ($eventType) {
            $builder->where('sl.event_type', $eventType);
        }
        if ($severity) {
            $builder->where('sl.severity', $severity);
        }

        return $builder->limit($limit)->get()->getResultArray();
    }

    /**
     * Delete logs older than N days.
     *
     * @return int Number of deleted rows
     */
    public function cleanup(int $days = 90): int
    {
        $this->db->table('security_logs')
            ->where('created_at <', date('Y-m-d H:i:s', strtotime("-{$days} days")))
            ->delete();

        return $this->db->affectedRows();
    }

    /**
     * Auto-create the security_logs table if it does not exist.
     */
    private function ensureTableExists(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS security_logs (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            event_type  VARCHAR(50) NOT NULL,
            user_id     INT NULL,
            ip_address  VARCHAR(45),
            user_agent  TEXT,
            details     TEXT,
            severity    ENUM('info','warning','critical') DEFAULT 'info',
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_user_id    (user_id),
            INDEX idx_severity   (severity),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
