<?php

namespace App\Services\POS;

/**
 * @agent-service: POS idempotency helper
 * @agent-pattern: Deterministic key builder
 * @agent-reusable: MEDIUM
 */
class POSIdempotencyService
{
    public function buildKey(string $tempId, string $deviceId): string
    {
        return sha1(strtoupper($deviceId) . '|' . strtoupper($tempId));
    }
}
