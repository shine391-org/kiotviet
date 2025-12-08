<?php

namespace Tests\Libraries;

use App\Libraries\JwtService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @agent-test: JwtService
 * @agent-pattern: Token encode/decode
 */
class JwtServiceTest extends CIUnitTestCase
{
    public function testGenerateAndDecodeReturnsSamePayload(): void
    {
        $jwt = new JwtService();
        $token = $jwt->generateToken(['id' => 10, 'role' => 'admin']);

        $decoded = $jwt->decode($token);

        $this->assertSame(10, $decoded['data']['id']);
        $this->assertSame('admin', $decoded['data']['role']);
        $this->assertGreaterThan(time(), $decoded['exp']);
        $this->assertLessThanOrEqual(time(), $decoded['iat']);
    }
}
