<?php

namespace MaintenanceAgent\Services;

class DatabaseService
{
    public function getStats(): array
    {
        $start = microtime(true);
        $status = 'healthy';
        $driver = 'unknown';
        $responseMs = null;
        $sizeMb = null;
        $connection = 'healthy';

        try {
            $db = \Config\Database::connect();
            $driver = $db->getPlatform() ?? 'MySQL';
            $t0 = microtime(true);
            $db->query('SELECT 1');
            $responseMs = (int) ((microtime(true) - $t0) * 1000);
        } catch (\Throwable $e) {
            $status = 'unhealthy';
            $connection = 'unhealthy';

            return [
                'status'           => $status,
                'driver'           => $driver,
                'connection'       => $connection,
                'response_ms'      => $responseMs,
                'database_size_mb' => null,
                'error'            => $e->getMessage(),
            ];
        }

        try {
            $db = \Config\Database::connect();
            $row = $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS sz FROM information_schema.TABLES WHERE table_schema = DATABASE()")->getRowArray();
            $sizeMb = $row['sz'] !== null ? (float) $row['sz'] : null;
        } catch (\Throwable $e) {
            $sizeMb = null;
        }

        return [
            'status'           => $status,
            'driver'           => $driver,
            'connection'       => $connection,
            'response_ms'      => $responseMs,
            'database_size_mb' => $sizeMb,
        ];
    }
}
