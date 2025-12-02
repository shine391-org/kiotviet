<?php

namespace App\Filters;

use App\Libraries\JwtService;
use CodeIgniter\Config\Services;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Enforces JWT authentication on API routes.
 *
 * @agent-filter: JWT authentication guard for APIs
 * @agent-pattern: Bearer token validation
 * @agent-reusable: HIGH
 */
class JwtAuthFilter implements FilterInterface
{
    /** @var list<string> */
    private const SKIP_PATHS = [
        'api/auth/login',
        'api/users/login',
        'api/health',
        'api/webhooks',
        'api/webhooks/ecommerce',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');

        if ($this->shouldBypass($request, $path)) {
            return;
        }

        $header = $request->getHeaderLine('Authorization');
        $token = $this->extractBearerToken($header);

        if ($token === null) {
            return $this->unauthorized('Missing or invalid Authorization header');
        }

        try {
            $jwt = new JwtService();
            $jwt->decode($token); // Will throw on invalid/expired token
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid or expired token');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }

    private function shouldBypass(RequestInterface $request, string $path): bool
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return true;
        }

        $pathLower = strtolower($path);
        foreach (self::SKIP_PATHS as $skip) {
            $skipLower = strtolower($skip);
            if ($pathLower === $skipLower || str_starts_with($pathLower, $skipLower . '/')) {
                return true;
            }
        }

        // Extra safety: bypass if path clearly targets health or auth login
        if (str_contains($pathLower, 'api/health') || str_contains($pathLower, 'api/auth/login') || str_contains($pathLower, 'api/users/login')) {
            return true;
        }

        return false;
    }

    private function extractBearerToken(string $header): ?string
    {
        if (empty($header)) {
            return null;
        }

        if (preg_match('/Bearer\\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function unauthorized(string $message)
    {
        return Services::response()
            ->setStatusCode(401)
            ->setJSON([
                'success' => false,
                'message' => $message,
            ]);
    }
}
