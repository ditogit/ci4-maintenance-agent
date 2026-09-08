<?php

namespace MaintenanceAgent\Services;

use MaintenanceAgent\Config\Maintenance;

class HealthService
{
    public function check(): array
    {
        $start = microtime(true);

        $dbStatus = 'healthy';
        $responseMs = null;
        try {
            $db = \Config\Database::connect();
            $t0 = microtime(true);
            $db->query('SELECT 1');
            $responseMs = (int) ((microtime(true) - $t0) * 1000);
        } catch (\Throwable $e) {
            $dbStatus = 'unhealthy';
        }

        $appStatus = $dbStatus === 'unhealthy' ? 'degraded' : 'healthy';
        $overall   = $dbStatus === 'unhealthy' ? 'degraded' : 'healthy';

        $elapsed = (int) ((microtime(true) - $start) * 1000);

        return [
            'status'      => $overall,
            'agent'       => 'healthy',
            'application' => $appStatus,
            'database'    => $dbStatus,
            'timestamp'   => date('c'),
            'response_ms' => $elapsed,
            'database_response_ms' => $responseMs,
            'agent_version' => Maintenance::VERSION,
            'api_version'   => Maintenance::API_VERSION,
        ];
    }
}
