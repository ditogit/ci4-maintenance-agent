<?php

namespace MaintenanceAgent\Services;

use CodeIgniter\CodeIgniter;
use MaintenanceAgent\Config\Maintenance;

class InfoService
{
    public function getInfo(): array
    {
        $maintenance = config(Maintenance::class);
        $sessionCfg  = config('App');
        $sessionDriver = 'unknown';

        try {
            $sess = config('Session') ?? config(\Config\Session::class);
            $sessionDriver = $sess->driver ?? $sess->sessionDriver ?? 'unknown';
        } catch (\Throwable $e) {
            $sessionDriver = 'unknown';
        }

        $dbDriver = 'unknown';
        try {
            $db = \Config\Database::connect();
            $dbDriver = $db->getPlatform() ?? 'MySQL';
        } catch (\Throwable $e) {
            $dbDriver = 'unknown';
        }

        return [
            'application'    => env('app.baseURL', 'CI4 Application'),
            'environment'    => $maintenance->environment ?? ENVIRONMENT ?? 'production',
            'ci_version'     => CodeIgniter::CI_VERSION,
            'php_version'    => PHP_VERSION,
            'database'       => $dbDriver,
            'session_driver' => $sessionDriver,
            'agent_version'  => Maintenance::VERSION,
            'api_version'    => Maintenance::API_VERSION,
        ];
    }
}
