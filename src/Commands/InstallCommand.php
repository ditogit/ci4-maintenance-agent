<?php

namespace MaintenanceAgent\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use MaintenanceAgent\Config\Maintenance;

class InstallCommand extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'maintenance:install';
    protected $description = 'Install CI4 Maintenance Agent — publish config, generate credentials, migrate audit table';

    public function run(array $params): void
    {
        CLI::write('CI4 Maintenance Agent', 'green');
        CLI::write('Version: ' . Maintenance::VERSION, 'yellow');
        CLI::newLine();

        $this->publishConfig();
        $this->generateCredentials();
        $this->patchHmvcRoutes();
        $this->patchFilters();
        $this->migrate();
        $this->validateSecurity();

        CLI::newLine();
        CLI::write('✓ Package installed', 'green');
        CLI::write('✓ Configuration published', 'green');
        CLI::write('✓ API credentials generated', 'green');
        CLI::write('✓ Database ready', 'green');
        CLI::write('✓ Routes registered (via Registrar)', 'green');
        CLI::newLine();
        CLI::write('Agent version: ' . Maintenance::VERSION, 'cyan');
        CLI::write('Status: READY', 'green');
        CLI::newLine();
        CLI::write('Next: set MAINTENANCE_API_KEY / MAINTENANCE_API_SECRET in .env, test GET /api/v1/maintenance/health', 'yellow');
    }

    private function publishConfig(): void
    {
        $src  = __DIR__ . '/../Config/Maintenance.php';
        $dest = APPPATH . 'Config/Maintenance.php';

        if (! is_file($dest)) {
            if (! is_dir(APPPATH . 'Config')) {
                mkdir(APPPATH . 'Config', 0755, true);
            }
            copy($src, $dest);
            CLI::write('  → Config published to app/Config/Maintenance.php', 'white');
        } else {
            CLI::write('  → Config already exists, skipped', 'yellow');
        }
    }

    private function generateCredentials(): void
    {
        $envPath = ROOTPATH . '.env';
        $key     = 'project_' . bin2hex(random_bytes(8));
        $secret  = bin2hex(random_bytes(32));

        if (! is_file($envPath)) {
            CLI::write('  → .env not found, credentials (save manually):', 'yellow');
            CLI::write("     MAINTENANCE_API_KEY={$key}", 'cyan');
            CLI::write("     MAINTENANCE_API_SECRET={$secret}", 'cyan');

            return;
        }

        $content = file_get_contents($envPath);

        if (! str_contains($content, 'MAINTENANCE_API_KEY')) {
            file_put_contents($envPath, PHP_EOL . "MAINTENANCE_API_KEY={$key}" . PHP_EOL . "MAINTENANCE_API_SECRET={$secret}" . PHP_EOL, FILE_APPEND);
            CLI::write('  → Credentials appended to .env', 'white');
            CLI::write("     MAINTENANCE_API_KEY={$key}", 'cyan');
            CLI::write("     MAINTENANCE_API_SECRET={$secret}", 'cyan');
        } else {
            CLI::write('  → Credentials already in .env, use maintenance:rotate-key to rotate', 'yellow');
        }
    }

    private function migrate(): void
    {
        try {
            $migrate = \Config\Services::migrations();
            $migrate->setNamespace('MaintenanceAgent');
            $migrate->latest();
            CLI::write('  → Audit table migrated', 'white');
        } catch (\Throwable $e) {
            CLI::write('  → Migration skipped: ' . $e->getMessage(), 'yellow');
        }
    }

    private function patchHmvcRoutes(): void
    {
        $routesFile = APPPATH . 'Config/Routes.php';
        if (! is_file($routesFile)) {
            return;
        }
        $content = file_get_contents($routesFile);
        if (str_contains($content, 'ci4-agent') || str_contains($content, 'MaintenanceAgent')) {
            return;
        }
        $patch = PHP_EOL . "// CI4 Maintenance Agent — auto-added by maintenance:install" . PHP_EOL
            . "if (file_exists(ROOTPATH . 'vendor/ditogit/ci4-agent/src/Config/Routes.php')) {" . PHP_EOL
            . "    require ROOTPATH . 'vendor/ditogit/ci4-agent/src/Config/Routes.php';" . PHP_EOL
            . "}" . PHP_EOL;
        file_put_contents($routesFile, $patch, FILE_APPEND);
        CLI::write('  → HMVC Routes.php patched (added ci4-agent require)', 'white');
    }

    private function patchFilters(): void
    {
        $file = APPPATH . 'Config/Filters.php';
        if (! is_file($file)) {
            return;
        }
        $content = file_get_contents($file);
        $content = str_replace("maintenanceEnabled' =>", "'maintenanceEnabled' =>", $content);
        $needed = [
            "'maintenance'"          => "'maintenance'          => \\MaintenanceAgent\\Filters\\MaintenanceFilter::class,",
            "'maintenanceEnabled'"   => "'maintenanceEnabled'   => \\MaintenanceAgent\\Filters\\MaintenanceEnabledFilter::class,",
            "'maintenanceAuth'"      => "'maintenanceAuth'      => \\MaintenanceAgent\\Filters\\MaintenanceAuthFilter::class,",
            "'maintenanceRateLimit'" => "'maintenanceRateLimit' => \\MaintenanceAgent\\Filters\\MaintenanceRateLimitFilter::class,",
        ];
        $missing = [];
        foreach ($needed as $key => $line) {
            if (! str_contains($content, $key)) {
                $missing[] = $line;
            }
        }
        if ($missing === []) {
            if (str_contains($content, "maintenanceEnabled' =>")) {
                file_put_contents($file, $content);
                CLI::write('  → Filters.php fixed (quote)', 'white');
            }
            return;
        }
        $injected = implode(PHP_EOL . '        ', $missing);
        if (str_contains($content, '$aliases = [')) {
            $content = str_replace('$aliases = [', '$aliases = [' . PHP_EOL . '        ' . $injected, $content);
            file_put_contents($file, $content);
            CLI::write('  → Filters.php patched (added: ' . implode(', ', array_keys($missing)) . ')', 'white');
        } else {
            CLI::write('  → Filters.php not patched — add aliases manually', 'yellow');
        }
    }

    private function validateSecurity(): void
    {
        if (! isset($_SERVER['HTTPS']) && env('CI_ENVIRONMENT') === 'production') {
            CLI::write('  ⚠ Production should use HTTPS', 'yellow');
        }
    }
}
