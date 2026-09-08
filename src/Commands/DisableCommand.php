<?php

namespace MaintenanceAgent\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DisableCommand extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'maintenance:disable';
    protected $description = 'Disable Maintenance Agent (emergency)';

    public function run(array $params): void
    {
        $env = ROOTPATH . '.env';
        if (is_file($env)) {
            $content = file_get_contents($env);
            if (str_contains($content, 'MAINTENANCE_ENABLED')) {
                $content = preg_replace('/^MAINTENANCE_ENABLED=.*$/m', 'MAINTENANCE_ENABLED=false', $content);
                file_put_contents($env, $content);
            } else {
                file_put_contents($env, PHP_EOL . 'MAINTENANCE_ENABLED=false' . PHP_EOL, FILE_APPEND);
            }
        }
        CLI::write('Maintenance Agent disabled (MAINTENANCE_ENABLED=false)', 'yellow');
        CLI::write('All endpoints now return 503', 'white');
    }
}
