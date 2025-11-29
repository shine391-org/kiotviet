<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Verify ecommerce webhook signatures.
 *
 * @agent-filter: Webhook auth
 * @agent-pattern: HMAC signature check
 */
class WebhookAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $secret = getenv('ECOM_WEBHOOK_SECRET') ?: 'ecom-secret';
        $signature = $request->getHeaderLine('X-Ecom-Signature');
        $body = (string) $request->getBody();
        if ($body === '' && method_exists($request, 'getJSON')) {
            $json = $request->getJSON(true);
            if (is_array($json)) {
                $body = json_encode($json);
            }
        }

        $jsonBody = method_exists($request, 'getJSON') ? $request->getJSON(true) : null;
        $expectedRaw = hash_hmac('sha256', $body, $secret);
        $expectedJson = is_array($jsonBody) ? hash_hmac('sha256', json_encode($jsonBody), $secret) : null;

        $valid = $signature && (hash_equals($expectedRaw, $signature) || ($expectedJson && hash_equals($expectedJson, $signature)));
        if (! $valid) {
            return service('response')->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
