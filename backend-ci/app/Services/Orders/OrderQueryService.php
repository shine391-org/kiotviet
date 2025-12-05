<?php

namespace App\Services\Orders;

use App\Repositories\Orders\OrderQueryRepository;
use App\Validators\OrderListValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Read-only service for listing and viewing orders.
 *
 * @agent-service: Orders query
 * @agent-pattern: Validation + repository delegation
 * @agent-reusable: MEDIUM
 */
class OrderQueryService
{
    protected OrderQueryRepository $repo;
    protected OrderListValidator $validator;

    public function __construct(?OrderQueryRepository $repo = null, ?OrderListValidator $validator = null)
    {
        $this->repo = $repo ?? new OrderQueryRepository();
        $this->validator = $validator ?? new OrderListValidator();
    }

    /**
     * List orders with filters and totals.
     *
     * @agent-use: GET /api/orders
     * @agent-pattern: List + pagination + totals
     */
    public function list(array $input): array
    {
        $filters = $this->validator->validate($input);
        return $this->repo->list($filters);
    }

    /**
     * Get single order with items.
     */
    public function detail(int $orderId): array
    {
        if ($orderId < 1) {
            throw new InvalidArgumentException('Invalid order id');
        }
        $order = $this->repo->findWithItems($orderId);
        if (! $order) {
            throw new RuntimeException('Order not found');
        }
        return $order;
    }
}
