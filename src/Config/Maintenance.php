<?php

namespace MaintenanceAgent\Config;

use CodeIgniter\Config\BaseConfig;

class Maintenance extends BaseConfig
{
    public const VERSION = '1.1.0';
    public const API_VERSION = 'v1';

    public bool $enabled = true;

    public string $apiKey = '';
    public string $apiSecret = '';

    public string $environment = 'production';

    public bool $sessionStats = true;
    public bool $sessionCleanup = true;
    public bool $sessionClear = false;

    public bool $databaseStats = true;

    public bool $storageStats = true;

    public bool $cacheClear = true;
    public bool $logViewer = true;
    public bool $backup = false;
    public bool $queue = false;

    public int $clockSkew = 300;

    public int $rateLimitRead = 60;
    public int $rateLimitMaintenance = 10;
    public int $rateLimitClear = 5;
    public int $rateLimitCache = 10;
    public int $rateLimitLogs = 30;

    public string $ipAllowlist = '';

    public int $sessionCleanupBatch = 5000;
    public int $sessionCleanupTimeout = 30;

    public array $permissions = [
        'maintenance.health.read',
        'maintenance.info.read',
        'maintenance.session.read',
        'maintenance.session.cleanup',
        'maintenance.session.clear',
        'maintenance.database.read',
        'maintenance.storage.read',
        'maintenance.cache.clear',
        'maintenance.logs.read',
        'maintenance.logs.clear',
        'maintenance.backup.create',
        'maintenance.queue.read',
        'maintenance.queue.retry',
        'maintenance.queue.clear',
    ];

    public string $auditDriver = 'database';

    public function __construct()
    {
        parent::__construct();

        $this->enabled               = (bool) (env('MAINTENANCE_ENABLED', $this->enabled) === true || env('MAINTENANCE_ENABLED', $this->enabled) === 'true' || env('MAINTENANCE_ENABLED', $this->enabled) === '1' ? true : (env('MAINTENANCE_ENABLED', null) !== null ? filter_var(env('MAINTENANCE_ENABLED', $this->enabled), FILTER_VALIDATE_BOOLEAN) : $this->enabled));
        $this->apiKey                = (string) env('MAINTENANCE_API_KEY', $this->apiKey);
        $this->apiSecret             = (string) env('MAINTENANCE_API_SECRET', $this->apiSecret);
        $this->environment           = (string) env('MAINTENANCE_ENVIRONMENT', $this->environment);
        $this->sessionStats          = filter_var(env('MAINTENANCE_SESSION_STATS', $this->sessionStats), FILTER_VALIDATE_BOOLEAN);
        $this->sessionCleanup        = filter_var(env('MAINTENANCE_SESSION_CLEANUP', $this->sessionCleanup), FILTER_VALIDATE_BOOLEAN);
        $this->sessionClear          = filter_var(env('MAINTENANCE_SESSION_CLEAR', $this->sessionClear), FILTER_VALIDATE_BOOLEAN);
        $this->databaseStats         = filter_var(env('MAINTENANCE_DATABASE_STATS', $this->databaseStats), FILTER_VALIDATE_BOOLEAN);
        $this->storageStats          = filter_var(env('MAINTENANCE_STORAGE_STATS', $this->storageStats), FILTER_VALIDATE_BOOLEAN);
        $this->cacheClear            = filter_var(env('MAINTENANCE_CACHE_CLEAR', $this->cacheClear), FILTER_VALIDATE_BOOLEAN);
        $this->logViewer             = filter_var(env('MAINTENANCE_LOG_VIEWER', $this->logViewer), FILTER_VALIDATE_BOOLEAN);
        $this->backup                = filter_var(env('MAINTENANCE_BACKUP', $this->backup), FILTER_VALIDATE_BOOLEAN);
        $this->queue                 = filter_var(env('MAINTENANCE_QUEUE', $this->queue), FILTER_VALIDATE_BOOLEAN);
        $this->clockSkew             = (int) env('MAINTENANCE_CLOCK_SKEW', $this->clockSkew);
        $this->rateLimitRead         = (int) env('MAINTENANCE_RATE_LIMIT_READ', $this->rateLimitRead);
        $this->rateLimitMaintenance  = (int) env('MAINTENANCE_RATE_LIMIT_MAINTENANCE', $this->rateLimitMaintenance);
        $this->rateLimitClear        = (int) env('MAINTENANCE_RATE_LIMIT_CLEAR', $this->rateLimitClear);
        $this->rateLimitCache        = (int) env('MAINTENANCE_RATE_LIMIT_CACHE', $this->rateLimitCache);
        $this->rateLimitLogs         = (int) env('MAINTENANCE_RATE_LIMIT_LOGS', $this->rateLimitLogs);
        $this->ipAllowlist           = (string) env('MAINTENANCE_IP_ALLOWLIST', $this->ipAllowlist);
        $this->sessionCleanupBatch   = (int) env('MAINTENANCE_SESSION_CLEANUP_BATCH', $this->sessionCleanupBatch);
        $this->sessionCleanupTimeout = (int) env('MAINTENANCE_SESSION_CLEANUP_TIMEOUT', $this->sessionCleanupTimeout);
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return match ($feature) {
            'session_stats'   => $this->sessionStats,
            'session_cleanup' => $this->sessionCleanup,
            'session_clear'   => $this->sessionClear,
            'database_stats'  => $this->databaseStats,
            'storage_stats'   => $this->storageStats,
            'cache_clear'     => $this->cacheClear,
            'log_viewer'      => $this->logViewer,
            'backup'          => $this->backup,
            'queue'           => $this->queue,
            default           => false,
        };
    }

    public function getAllowedIps(): array
    {
        if ($this->ipAllowlist === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->ipAllowlist)));
    }
}
