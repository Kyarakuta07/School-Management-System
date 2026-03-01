<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Modules\User\Services\ActivityLogService;

class LogCleanup extends BaseCommand
{
    protected $group = 'System';
    protected $name = 'logs:cleanup';
    protected $description = 'Prunes system logs older than a specified number of days.';
    protected $usage = 'logs:cleanup [days]';
    protected $arguments = [
        'days' => 'Number of days to keep logs (default: 30)',
    ];

    public function run(array $params)
    {
        $days = $params[0] ?? 30;

        if (!is_numeric($days)) {
            CLI::error('Error: [days] must be a number.');
            return;
        }

        $days = (int) $days;
        CLI::write("Pruning logs older than {$days} days...", 'yellow');

        $logService = new ActivityLogService();
        $deleted = $logService->cleanup($days);

        CLI::write("Successfully deleted {$deleted} log entries.", 'green');
    }
}
