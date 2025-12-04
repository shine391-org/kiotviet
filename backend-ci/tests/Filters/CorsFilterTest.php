<?php

namespace Tests\Filters;

use App\Filters\CorsFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\UserAgents;

/**
 * @agent-test: CorsFilter
 * @agent-pattern: CORS header validation
 */
class CorsFilterTest extends CIUnitTestCase
{
    private CorsFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new CorsFilter();
    }

    public function testBeforeAddsHeaders(): void
    {
        $request = $this->makeRequest('get');
        $response = $this->filter->before($request);

        $res = $response ?? service('response');
        $this->assertSame('*', $res->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('Authorization', $res->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('GET', $res->getHeaderLine('Access-Control-Allow-Methods'));
    }

    public function testOptionsReturnsEarly(): void
    {
        $request = $this->makeRequest('options');
        $response = $this->filter->before($request);

        $this->assertNotNull($response);
        $this->assertSame(200, $response->getStatusCode());
    }

    private function makeRequest(string $method): IncomingRequest
    {
        $config = config('App');
        $uri = new URI('http://example.com/api/products');
        $userAgent = new UserAgent(config(UserAgents::class));
        $request = new IncomingRequest($config, $uri, null, $userAgent);
        $request->setMethod($method);
        return $request;
    }
}
