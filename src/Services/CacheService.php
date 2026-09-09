<?php

namespace MaintenanceAgent\Services;

use MaintenanceAgent\Config\Maintenance;

class CacheService
{
    public function clear(string $type = 'all'): array
    {
        $config = config(Maintenance::class);
        if (!$config->cacheClear) {
            throw new \RuntimeException('Cache clear is disabled', 403);
        }

        $start = microtime(true);
        $lockKey = 'maintenance_cache_clear_lock';
        $cache = cache();
        if ($cache->get($lockKey) !== null) {
            throw new \RuntimeException('Cache clear already in progress', 409);
        }
        $cache->save($lockKey, 1, 30);

        $cleared = [];

        try {
            if ($type === 'all' || $type === 'default') {
                try {
                    $cache->clean();
                    $cleared[] = 'cache';
                } catch (\Throwable $e) {
                }
            }

            $paths = [];
            if ($type === 'all' || $type === 'file') {
                $paths[] = WRITEPATH . 'cache/';
            }
            if ($type === 'all' || $type === 'config') {
                if (is_file(WRITEPATH . 'cache/config_cache.php')) {
                    $paths[] = WRITEPATH . 'cache/config_cache.php';
                }
            }
            if ($type === 'all' || $type === 'route') {
                if (is_file(WRITEPATH . 'cache/route_cache.php')) {
                    $paths[] = WRITEPATH . 'cache/route_cache.php';
                }
                if (is_file(WRITEPATH . 'cache/routes_cache.php')) {
                    $paths[] = WRITEPATH . 'cache/routes_cache.php';
                }
            }

            foreach ($paths as $path) {
                try {
                    if (is_file($path)) {
                        @unlink($path);
                        $cleared[] = $path;
                    } elseif (is_dir($path)) {
                        $this->deleteFiles($path);
                        $cleared[] = $path;
                    }
                } catch (\Throwable $e) {
                }
            }
        } finally {
            $cache->delete($lockKey);
        }

        return [
            'type'        => $type,
            'cleared'     => $cleared,
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
        ];
    }

    private function deleteFiles(string $dir): void
    {
        $files = glob(rtrim($dir, '/') . '/*');
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteFiles($file);
                @rmdir($file);
            } else {
                @unlink($file);
            }
        }
    }
}
