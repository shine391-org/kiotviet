<?php

namespace Tests\Support;

use App\Libraries\JwtService;

/**
 * @agent-test-helper: Provides JWT tokens for feature tests
 * @agent-pattern: Reusable auth helper
 * @agent-reusable: HIGH
 */
trait AuthTestTrait
{
    protected string $authToken;

    protected function setUpAuthToken(): void
    {
        $jwt = new JwtService();
        $this->authToken = $jwt->generateToken([
            'id'       => 'test-user',
            'username' => 'tester',
            'role'     => 'admin',
        ]);
    }

    protected function authHeaders(array $extra = []): array
    {
        return array_merge([
            'Authorization' => 'Bearer ' . $this->authToken,
        ], $extra);
    }
}
