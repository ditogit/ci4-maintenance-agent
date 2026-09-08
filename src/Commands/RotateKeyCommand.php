<?php

namespace MaintenanceAgent\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RotateKeyCommand extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'maintenance:rotate-key';
    protected $description = 'Rotate API credentials';

    public function run(array $params): void
    {
        $newKey    = 'project_' . bin2hex(random_bytes(8));
        $newSecret = bin2hex(random_bytes(32));

        $env = ROOTPATH . '.env';
        if (is_file($env)) {
            $content = file_get_contents($env);
            $content = preg_replace('/^MAINTENANCE_API_KEY=.*$/m', 'MAINTENANCE_API_KEY=' . $newKey, $content);
            $content = preg_replace('/^MAINTENANCE_API_SECRET=.*$/m', 'MAINTENANCE_API_SECRET=' . $newSecret, $content);
            if (! str_contains($content, 'MAINTENANCE_API_KEY')) {
                $content .= PHP_EOL . "MAINTENANCE_API_KEY={$newKey}" . PHP_EOL . "MAINTENANCE_API_SECRET={$newSecret}" . PHP_EOL;
            }
            file_put_contents($env, $content);
        }

        CLI::write('API credentials rotated.', 'green');
        CLI::write('Old credentials are now invalid.', 'yellow');
        CLI::newLine();
        CLI::write("MAINTENANCE_API_KEY={$newKey}", 'cyan');
        CLI::write("MAINTENANCE_API_SECRET={$newSecret}", 'cyan');
        CLI::newLine();
        CLI::write('Update CI4 Project Hub with new credentials!', 'red');
    }
}
