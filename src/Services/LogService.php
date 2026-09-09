<?php

namespace MaintenanceAgent\Services;

use MaintenanceAgent\Config\Maintenance;

class LogService
{
    public function tail(?string $channel = null, int $limit = 100): array
    {
        $config = config(Maintenance::class);
        if (!$config->logViewer) {
            throw new \RuntimeException('Log viewer is disabled', 403);
        }

        $limit = max(1, min($limit, 500));
        $date = date('Y-m-d');
        $file = WRITEPATH . 'logs/log-' . $date . '.log';

        if (!is_file($file)) {
            return [
                'channel' => $channel,
                'file'    => basename($file),
                'exists'  => false,
                'lines'   => [],
                'count'   => 0,
            ];
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $lines = [];
        }

        if ($channel !== null && $channel !== '' && $channel !== 'all') {
            $channelLower = strtolower($channel);
            $lines = array_values(array_filter($lines, static function (string $line) use ($channelLower): bool {
                $lower = strtolower($line);
                if ($channelLower === 'error') {
                    return str_contains($lower, 'error') || str_contains($lower, 'critical') || str_contains($lower, 'alert') || str_contains($lower, 'emergency');
                }
                if ($channelLower === 'security') {
                    return str_contains($lower, 'security') || str_contains($lower, 'auth') || str_contains($lower, 'forbidden') || str_contains($lower, 'unauthorized');
                }
                return str_contains($lower, $channelLower);
            }));
        }

        $total = count($lines);
        $lines = array_slice($lines, -$limit);

        return [
            'channel' => $channel ?? 'all',
            'file'    => basename($file),
            'exists'  => true,
            'lines'   => $lines,
            'count'   => count($lines),
            'total'   => $total,
        ];
    }

    public function clear(string $confirm = ''): array
    {
        $config = config(Maintenance::class);
        if (!$config->logViewer) {
            throw new \RuntimeException('Log viewer is disabled', 403);
        }

        if ($confirm !== 'CLEAR LOGS') {
            throw new \RuntimeException('Confirmation required: send confirm=CLEAR LOGS', 422);
        }

        $lockKey = 'maintenance_logs_clear_lock';
        $cache = cache();
        if ($cache->get($lockKey) !== null) {
            throw new \RuntimeException('Logs clear already in progress', 409);
        }
        $cache->save($lockKey, 1, 30);

        $deleted = 0;
        $files = [];

        try {
            $logDir = WRITEPATH . 'logs/';
            $found = glob($logDir . 'log-*.log');
            if ($found !== false) {
                foreach ($found as $file) {
                    if (@unlink($file)) {
                        $deleted++;
                        $files[] = basename($file);
                    } else {
                        $content = @file_get_contents($file);
                        if ($content !== false) {
                            @file_put_contents($file, '');
                            $deleted++;
                            $files[] = basename($file);
                        }
                    }
                }
            }
        } finally {
            $cache->delete($lockKey);
        }

        try {
            $m = new \MaintenanceAgent\Models\MaintenanceAuditModel();
            $m->log('logs_clear', 'success', $deleted, ['files' => $files]);
        } catch (\Throwable $e) {
        }

        return [
            'deleted' => $deleted,
            'files'   => $files,
        ];
    }
}
