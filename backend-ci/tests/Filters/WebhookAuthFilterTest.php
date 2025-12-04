<?php

namespace Tests\Filters;

use App\Filters\WebhookAuthFilter;
use App\Libraries\JwtService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use Config\UserAgents;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @agent-test: WebhookAuthFilter
 * @agent-pattern: Filter auth validation
 */
class WebhookAuthFilterTest extends CIUnitTestCase
{
    private WebhookAuthFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new WebhookAuthFilter();
    }

    public function testBeforeAllowsValidSignature(): void
    {
        $body = json_encode(['foo' => 'bar']);
        $request = $this->makeRequestWithSignature($body, $this->sign($body));

        $result = $this->filter->before($request);

        $this->assertNull($result, 'Valid signature should allow request');
    }

    public function testBeforeBlocksInvalidSignature(): void
    {
        $body = json_encode(['foo' => 'bar']);
        $request = $this->makeRequestWithSignature($body, 'invalid-signature');

        $response = $this->filter->before($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());
        $json = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success']);
        $this->assertSame('Invalid webhook signature', $json['message']);
    }

    private function makeRequestWithSignature(string $body, string $signature, string $method = 'post'): IncomingRequest
    {
        $config = config('App');
        $uri = new URI('http://example.com/webhooks/ecommerce/order');
        $userAgent = new UserAgent(config(UserAgents::class));
        $request = new IncomingRequest($config, $uri, $body, $userAgent);
        $request->setMethod($method);
        $request->setHeader('X-Ecom-Signature', $signature);
        $request->setHeader('Content-Type', 'application/json');
        return $request;
    }

    private function sign(string $body): string
    {
        return hash_hmac('sha256', $body, getenv('ECOM_WEBHOOK_SECRET') ?: 'ecom-secret');
    }
}
