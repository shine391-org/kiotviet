<?php

namespace Tests\Filters;

use App\Filters\WebhookAuthFilter;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @agent-test: WebhookAuthFilter
 * @agent-pattern: Filter auth test
 */
class WebhookAuthFilterTest extends CIUnitTestCase
{
    public function test_rejects_invalid_signature(): void
    {
        $request = $this->requestWithBody('{}', 'invalid');
        $filter = new WebhookAuthFilter();
        $result = $filter->before($request);
        $this->assertNotNull($result);
        $this->assertSame(401, $result->getStatusCode());
    }

    public function test_allows_valid_signature(): void
    {
        $body = '{"ok":true}';
        $secret = 'ecom-secret';
        $signature = hash_hmac('sha256', $body, $secret);
        putenv('ECOM_WEBHOOK_SECRET=' . $secret);

        $request = $this->requestWithBody($body, $signature);
        $filter = new WebhookAuthFilter();
        $result = $filter->before($request);
        $this->assertNull($result);
    }

    private function requestWithBody(string $body, string $signature): IncomingRequest
    {
        $config = config('App');
        $uri = new URI('http://localhost/api/webhooks/ecommerce/product');
        $uaConfig = config('UserAgents');
        $userAgent = new \CodeIgniter\HTTP\UserAgent($uaConfig);
        $request = new IncomingRequest($config, $uri, 'php://input', $userAgent);
        $request->setBody($body);
        $request->setHeader('X-Ecom-Signature', $signature);
        return $request;
    }
}
