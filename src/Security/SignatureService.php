<?php

namespace MaintenanceAgent\Security;

class SignatureService
{
    public static function buildPayload(string $timestamp, string $nonce, string $method, string $path, string $body): string
    {
        return $timestamp . $nonce . strtoupper($method) . $path . $body;
    }

    public static function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public static function verify(string $payload, string $secret, string $signature): bool
    {
        $expected = self::sign($payload, $secret);

        return hash_equals($expected, $signature);
    }

    public static function generateNonce(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }
}
