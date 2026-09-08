<?php

namespace MaintenanceAgent\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use MaintenanceAgent\Config\Maintenance;

class StatusCommand extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'maintenance:status';
    protected $description = 'Show Maintenance Agent status';

    public function run(array $params): void
    {
        $cfg = config(Maintenance::class);

        CLI::write('Maintenance Agent', 'green');
        CLI::newLine();
        CLI::write('Status: ' . ($cfg->enabled ? 'Enabled' : 'Disabled'), $cfg->enabled ? 'green' : 'red');
        CLI::write('Version: ' . Maintenance::VERSION);
        CLI::write('API: ' . Maintenance::API_VERSION);
        CLI::write('Environment: ' . $cfg->environment);
        CLI::write('Session stats: ' . ($cfg->sessionStats ? 'Enabled' : 'Disabled'));
        CLI::write('Session cleanup: ' . ($cfg->sessionCleanup ? 'Enabled' : 'Disabled'));
        CLI::write('Session clear: ' . ($cfg->sessionClear ? 'Enabled' : 'Disabled'));
        CLI::write('Database: ' . ($cfg->databaseStats ? 'Enabled' : 'Disabled'));
        CLI::write('Storage: ' . ($cfg->storageStats ? 'Enabled' : 'Disabled'));
        CLI::write('API Key: ' . ($cfg->apiKey !== '' ? substr($cfg->apiKey, 0, 8) . '...' : 'NOT SET'));
    }
}
