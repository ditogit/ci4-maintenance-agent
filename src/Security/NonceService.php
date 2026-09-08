<?php

namespace MaintenanceAgent\Security;

class NonceService
{
    private const CACHE_PREFIX = 'maintenance_nonce_';

    public static function isReplay(string $nonce, int $ttl = 600): bool
    {
        if ($nonce === '') {
            return false;
        }

        $cache = cache();
        $key   = self::CACHE_PREFIX . $nonce;

        if ($cache->get($key) !== null) {
            return true;
        }

        $cache->save($key, 1, $ttl);

        return false;
    }
}
