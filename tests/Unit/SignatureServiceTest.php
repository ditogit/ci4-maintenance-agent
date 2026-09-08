<?php

use MaintenanceAgent\Security\SignatureService;
use MaintenanceAgent\Security\TimestampValidator;
use PHPUnit\Framework\TestCase;

class SignatureServiceTest extends TestCase
{
    public function testSignAndVerify(): void
    {
        $payload = SignatureService::buildPayload('1700000000', 'abc', 'GET', '/api/v1/maintenance/health', '');
        $secret  = 'supersecret';
        $sig     = SignatureService::sign($payload, $secret);
        $this->assertTrue(SignatureService::verify($payload, $secret, $sig));
        $this->assertFalse(SignatureService::verify($payload, $secret, 'bad'));
    }

    public function testTimestampValid(): void
    {
        $this->assertTrue(TimestampValidator::isValid((string) time(), 300));
        $this->assertFalse(TimestampValidator::isValid((string) (time() - 600), 300));
        $this->assertFalse(TimestampValidator::isValid('0', 300));
    }

    public function testPayloadOrder(): void
    {
        $p1 = SignatureService::buildPayload('1', 'n', 'get', '/path', '{"a":1}');
        $p2 = SignatureService::buildPayload('1', 'n', 'GET', '/path', '{"a":1}');
        $this->assertSame($p1, $p2);
    }
}
