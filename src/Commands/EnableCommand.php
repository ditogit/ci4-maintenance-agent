<?php

namespace MaintenanceAgent\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class EnableCommand extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'maintenance:enable';
    protected $description = 'Enable Maintenance Agent';

    public function run(array $params): void
    {
        $this->setEnv('MAINTENANCE_ENABLED', 'true');
        CLI::write('Maintenance Agent enabled (MAINTENANCE_ENABLED=true)', 'green');
    }

    private function setEnv(string $key, string $value): void
    {
        $env = ROOTPATH . '.env';
        if (! is_file($env)) {
            return;
        }
        $content = file_get_contents($env);
        if (str_contains($content, $key)) {
            $content = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $key . '=' . $value, $content);
            file_put_contents($env, $content);
        } else {
            file_put_contents($env, PHP_EOL . $key . '=' . $value . PHP_EOL, FILE_APPEND);
        }
    }
}
