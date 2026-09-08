<?php

namespace MaintenanceAgent\Services;

use MaintenanceAgent\Config\Maintenance;

class SessionService
{
    private string $table = 'ci_sessions';
    private string $driver = 'unknown';
    private int $expiration = 7200;

    public function __construct()
    {
        try {
            $cfg = config(\Config\Session::class);
            $this->table      = $cfg->sessionSavePath ?? $cfg->savePath ?? 'ci_sessions';
            if (str_contains($this->table, ':')) {
                $this->table = 'ci_sessions';
            }
            $this->driver     = $cfg->driver ?? $cfg->sessionDriver ?? 'DatabaseHandler';
            $this->expiration = (int) ($cfg->expiration ?? $cfg->sessionExpiration ?? 7200);
            if ($this->expiration === 0) {
                $this->expiration = 7200;
            }
        } catch (\Throwable $e) {
        }

        try {
            $appSess = config('Session');
            if (isset($appSess->expiration)) {
                $this->expiration = (int) $appSess->expiration;
            }
        } catch (\Throwable $e) {
        }
    }

    public function getStats(): array
    {
        $db = \Config\Database::connect();

        $total = 0;
        $expired = 0;
        $sizeMb = null;

        try {
            $total = (int) $db->table($this->table)->countAll();
        } catch (\Throwable $e) {
            return [
                'driver'  => $this->driver,
                'table'   => $this->table,
                'total'   => 0,
                'expired' => 0,
                'active'  => 0,
                'size_mb' => null,
                'error'   => $e->getMessage(),
            ];
        }

        $expiryTime = time() - $this->expiration;
        try {
            $expired = (int) $db->table($this->table)->where('timestamp <', $expiryTime)->countAllResults();
        } catch (\Throwable $e) {
            $expired = 0;
        }

        try {
            $row = $db->query("SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS sz FROM information_schema.TABLES WHERE table_schema = DATABASE() AND table_name = ?", [$this->table])->getRowArray();
            $sizeMb = $row['sz'] !== null ? (float) $row['sz'] : null;
        } catch (\Throwable $e) {
            $sizeMb = null;
        }

        return [
            'driver'  => $this->driver,
            'table'   => $this->table,
            'total'   => $total,
            'expired' => $expired,
            'active'  => max(0, $total - $expired),
            'size_mb' => $sizeMb,
        ];
    }

    public function cleanupExpired(): array
    {
        $start = microtime(true);
        $config = config(Maintenance::class);
        $db = \Config\Database::connect();
        $expiryTime = time() - $this->expiration;
        $batch = max(100, $config->sessionCleanupBatch);
        $timeout = $config->sessionCleanupTimeout;

        $lockKey = 'maintenance_session_cleanup_lock';
        $cache = cache();
        if ($cache->get($lockKey) !== null) {
            throw new \RuntimeException('Cleanup already in progress', 409);
        }
        $cache->save($lockKey, 1, $timeout + 10);

        $deleted = 0;
        $deadline = $start + $timeout;

        try {
            do {
                if (microtime(true) >= $deadline) {
                    break;
                }

                $affected = $db->table($this->table)->where('timestamp <', $expiryTime)->limit($batch)->delete();
                $count = $db->affectedRows();
                $deleted += $count;

                if ($count < $batch) {
                    break;
                }
            } while (true);
        } finally {
            $cache->delete($lockKey);
        }

        $remaining = 0;
        try {
            $remaining = (int) $db->table($this->table)->countAll();
        } catch (\Throwable $e) {
        }

        $timedOut = microtime(true) >= $deadline;

        return [
            'deleted'     => $deleted,
            'remaining'   => $remaining,
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            'timed_out'   => $timedOut,
        ];
    }

    public function clearAll(): array
    {
        $config = config(Maintenance::class);
        if (! $config->sessionClear) {
            throw new \RuntimeException('Session clear is disabled', 403);
        }

        $lockKey = 'maintenance_session_clear_lock';
        $cache = cache();
        if ($cache->get($lockKey) !== null) {
            throw new \RuntimeException('Clear already in progress', 409);
        }
        $cache->save($lockKey, 1, 30);

        try {
            $db = \Config\Database::connect();
            $total = (int) $db->table($this->table)->countAll();
            $db->table($this->table)->truncate();
            $deleted = $total;
        } finally {
            $cache->delete($lockKey);
        }

        return [
            'deleted' => $deleted,
            'warning' => 'All application sessions were invalidated',
        ];
    }
}
