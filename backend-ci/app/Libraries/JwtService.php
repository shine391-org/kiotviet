<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    protected string $secret;
    protected int $ttl;

    public function __construct()
    {
        $this->secret = env('jwt.key', 'change-me');
        $this->ttl    = (int) env('jwt.ttl', 86400);
    }

    public function generateToken(array $data): string
    {
        $now = time();
        $payload = [
            'iat'  => $now,
            'exp'  => $now + $this->ttl,
            'data' => $data,
        ];
        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function decode(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
        return json_decode(json_encode($decoded), true);
    }
}
