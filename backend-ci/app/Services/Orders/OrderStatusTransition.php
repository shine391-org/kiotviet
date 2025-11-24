<?php

namespace App\Services\Orders;

/**
 * State machine for order statuses.
 *
 * @agent-service: Order status transition
 * @agent-pattern: State machine
 * @agent-reusable: HIGH
 */
class OrderStatusTransition
{
    private const MAP = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipping', 'cancelled'],
        'shipping' => ['delivered', 'cancelled'],
        'delivered' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function isValid(string $from, string $to): bool
    {
        return in_array($to, self::MAP[$from] ?? [], true);
    }

    public function allowed(string $status): array
    {
        return self::MAP[$status] ?? [];
    }
}
