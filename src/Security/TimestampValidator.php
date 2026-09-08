<?php

namespace MaintenanceAgent\Security;

class TimestampValidator
{
    public static function isValid(string|int $timestamp, int $skewSeconds = 300): bool
    {
        $ts  = (int) $timestamp;
        $now = time();

        if ($ts <= 0) {
            return false;
        }

        return abs($now - $ts) <= $skewSeconds;
    }

    public static function isExpired(string|int $timestamp, int $skewSeconds = 300): bool
    {
        return ! self::isValid($timestamp, $skewSeconds);
    }
}
