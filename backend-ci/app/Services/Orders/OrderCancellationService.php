<?php

namespace App\Services\Orders;

use InvalidArgumentException;

/**
 * Handle order cancellation with validation + side effects via OrderStatusService.
 *
 * @agent-service: Order cancellation
 * @agent-pattern: Facade over status service
 * @agent-reusable: MEDIUM
 */
class OrderCancellationService
{
    private const CANCELLABLE = ['draft', 'confirmed', 'processing', 'shipping'];

    protected OrderStatusService $statusService;
    protected OrderRepositoryAdapter $orders;

    public function __construct(
        ?OrderStatusService $statusService = null,
        ?OrderRepositoryAdapter $orders = null
    ) {
        $this->statusService = $statusService ?? service('orderStatusService');
        $this->orders = $orders ?? new OrderRepositoryAdapter();
    }

    /** Cancel order with reason. @agent-use: POST /api/orders/{id}/cancel */
    public function cancel(int $orderId, ?string $reason = null, ?int $userId = null): array
    {
        $order = $this->orders->find($orderId);
        if (! $order) {
            throw new InvalidArgumentException('Order not found');
        }
        if (! in_array($order['status'] ?? 'draft', self::CANCELLABLE, true)) {
            throw new InvalidArgumentException('Order cannot be cancelled from current status');
        }

        return $this->statusService->updateStatus($orderId, 'cancelled', $userId, $reason);
    }
}

/**
 * Tiny adapter to avoid direct dependency cycle in services config.
 */
class OrderRepositoryAdapter
{
    public function find(int $id): ?array
    {
        return service('orderRepository')->findById($id);
    }
}
