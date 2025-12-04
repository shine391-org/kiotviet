<?php

namespace Tests\Filters;

use App\Filters\JwtAuthFilter;
use App\Libraries\JwtService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\UserAgents;

/**
 * @agent-test: JwtAuthFilter
 * @agent-pattern: Bearer token guard
 */
class JwtAuthFilterTest extends CIUnitTestCase
{
    private JwtAuthFilter $filter;
    private JwtService $jwt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new JwtAuthFilter();
        $this->jwt = new JwtService();
    }

    public function testBeforeAllowsWhitelistedPath(): void
    {
        $request = $this->makeRequest('get', '/api/auth/login');
        $this->assertNull($this->filter->before($request));
    }

    public function testBeforeRejectsMissingHeader(): void
    {
        $request = $this->makeRequest('get', '/api/products');
        $response = $this->filter->before($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testBeforeRejectsInvalidToken(): void
    {
        $request = $this->makeRequest('get', '/api/products', 'Bearer invalid');
        $response = $this->filter->before($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testBeforeAllowsValidToken(): void
    {
        $token = $this->jwt->generateToken(['id' => 1]);
        $request = $this->makeRequest('get', '/api/products', 'Bearer ' . $token);

        $this->assertNull($this->filter->before($request));
    }

    private function makeRequest(string $method, string $path, string $authHeader = ''): IncomingRequest
    {
        $config = config('App');
        $uri = new URI('http://example.com' . $path);
        $userAgent = new UserAgent(config(UserAgents::class));
        $request = new IncomingRequest($config, $uri, null, $userAgent);
        $request->setMethod($method);
        if ($authHeader !== '') {
            $request->setHeader('Authorization', $authHeader);
        }
        return $request;
    }
}
