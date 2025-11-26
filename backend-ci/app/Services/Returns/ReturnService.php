<?php

namespace App\Services\Returns;

use App\Repositories\Returns\ReturnRepository;
use App\Transformers\ReturnTransformer;
use App\Validators\ReturnValidator;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Webhooks\WebhookDispatcher;
use InvalidArgumentException;
use RuntimeException;

/**
 * Return workflow service.
 *
 * @agent-service: Returns
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class ReturnService
{
    protected ReturnRepository $repo;
    protected ReturnValidator $validator;
    protected ReturnTransformer $transformer;
    protected InventoryMovementLogger $movements;
    protected \App\Repositories\Inventory\InventoryRepository $inventoryRepo;
    protected ?WebhookDispatcher $webhooks;
    protected int $returnWindowDays = 30;

    public function __construct(
        ?ReturnRepository $repo = null,
        ?ReturnValidator $validator = null,
        ?ReturnTransformer $transformer = null,
        ?InventoryMovementLogger $movements = null,
        ?WebhookDispatcher $webhooks = null,
        ?\App\Repositories\Inventory\InventoryRepository $inventoryRepo = null
    ) {
        $this->repo = $repo ?? new ReturnRepository();
        $this->validator = $validator ?? new ReturnValidator();
        $this->transformer = $transformer ?? new ReturnTransformer();
        $this->movements = $movements ?? new InventoryMovementLogger();
        $this->webhooks = $webhooks;
        $this->inventoryRepo = $inventoryRepo ?? new \App\Repositories\Inventory\InventoryRepository();
    }

    /** List returns. @agent-use: GET /api/returns */
    public function list(array $filters): array
    {
        $rows = $this->repo->findAll($filters);
        $total = $this->repo->count($filters);
        return [
            'success' => true,
            'data' => $this->transformer->transformList($rows),
            'pagination' => $this->pagination($filters, $total),
        ];
    }

    /** Get detail. @agent-use: GET /api/returns/{id} */
    public function get(int $id): array
    {
        $ret = $this->repo->findById($id);
        if (! $ret) {
            throw new RuntimeException('Return not found');
        }
        return ['success' => true, 'data' => $this->transformer->transform($ret)];
    }

    /** Create return request. @agent-use: POST /api/returns */
    public function create(array $payload): array
    {
        $validated = $this->validator->validateCreate($payload);
        $order = $this->repo->orderWithItems($validated['order_id']);
        if (! $order) {
            throw new InvalidArgumentException('Order not found');
        }
        $this->assertOrderCompleted($order);
        $this->assertWithinWindow($order);
        $this->assertCustomerMatch($order, $validated['customer_id']);

        $items = $this->buildReturnItems($order, $validated['items']);
        $returnAmount = array_sum(array_column($items, 'line_total'));
        $refundAmount = $returnAmount + ($validated['refund_shipping_fee'] ? 0 : 0); // shipping fee not stored; treated as 0

        $number = $this->repo->nextNumber($validated['order_id']);
        $returnRow = [
            'return_number' => $number,
            'order_id' => $validated['order_id'],
            'customer_id' => $validated['customer_id'],
            'return_amount' => $returnAmount,
            'refund_shipping_fee' => $validated['refund_shipping_fee'],
            'refund_amount' => $refundAmount,
            'refund_method' => $validated['refund_method'],
            'reason' => $validated['reason'],
            'reason_detail' => $validated['reason_detail'],
            'status' => 'pending',
            'created_by' => $validated['created_by'] ?? null,
        ];

        $rows = array_map(fn ($i) => [
            'order_item_id' => $i['order_item_id'],
            'quantity_returned' => $i['quantity_returned'],
            'item_condition' => $i['item_condition'],
        ], $items);

        $created = $this->repo->create($returnRow, $rows);
        $created['items'] = array_map(fn ($i) => [
            'order_item_id' => $i['order_item_id'],
            'quantity_returned' => $i['quantity_returned'],
            'condition' => $i['item_condition'],
            'line_total' => $i['line_total'],
        ], $items);

        $transformed = $this->transformer->transform($created);
        $this->emit('return.requested', $transformed);

        return ['success' => true, 'data' => $transformed];
    }

    /** Approve return. @agent-use: PATCH /api/returns/{id}/approve */
    public function approve(int $id, array $payload): array
    {
        $validated = $this->validator->validateApproval($payload);
        $ret = $this->repo->findById($id);
        if (! $ret) {
            throw new RuntimeException('Return not found');
        }
        if ($ret['status'] !== 'pending') {
            throw new InvalidArgumentException('Only pending returns can be approved');
        }

        $order = $this->repo->orderWithItems($ret['order_id']);
        $refundShipping = $validated['refund_shipping_fee'];
        if ($refundShipping === null) {
            $refundShipping = in_array($ret['reason'], ['defective', 'wrong_item'], true);
        }
        $shippingFee = (float) ($order['shipping_fee'] ?? 0);
        $refundAmount = $ret['return_amount'] + ($refundShipping ? $shippingFee : 0);

        $updated = $this->repo->transition(
            $id,
            'approved',
            [
                'approved_by' => $validated['user_id'],
                'approved_at' => date('Y-m-d H:i:s'),
                'refund_shipping_fee' => $refundShipping,
                'refund_amount' => $refundAmount,
                'refund_method' => $validated['refund_method'],
                'notes' => $validated['notes'],
            ],
            $validated['version']
        );
        $transformed = $this->transformer->transform($updated);
        $this->emit('return.approved', $transformed);
        return ['success' => true, 'data' => $transformed];
    }

    /** Reject return. @agent-use: PATCH /api/returns/{id}/reject */
    public function reject(int $id, array $payload): array
    {
        $validated = $this->validator->validateTransition($payload);
        $ret = $this->repo->findById($id);
        if (! $ret) {
            throw new RuntimeException('Return not found');
        }
        if ($ret['status'] !== 'pending') {
            throw new InvalidArgumentException('Only pending returns can be rejected');
        }
        $updated = $this->repo->transition(
            $id,
            'rejected',
            ['rejected_by' => $validated['user_id'], 'rejected_at' => date('Y-m-d H:i:s')],
            $validated['version']
        );
        $transformed = $this->transformer->transform($updated);
        $this->emit('return.rejected', $transformed);
        return ['success' => true, 'data' => $transformed];
    }

    /** Complete return (after restock/refund). @agent-use: PATCH /api/returns/{id}/complete */
    public function complete(int $id, array $payload): array
    {
        $validated = $this->validator->validateTransition($payload);
        $ret = $this->repo->findById($id);
        if (! $ret) {
            throw new RuntimeException('Return not found');
        }
        if (! in_array($ret['status'], ['approved'], true)) {
            throw new InvalidArgumentException('Only approved returns can be completed');
        }

        $order = $this->repo->orderWithItems($ret['order_id']);
        $this->restockItems($ret, $order);

        $updated = $this->repo->transition(
            $id,
            'completed',
            ['completed_at' => date('Y-m-d H:i:s')],
            $validated['version']
        );
        $transformed = $this->transformer->transform($updated);
        $this->emit('return.completed', $transformed);
        return ['success' => true, 'data' => $transformed];
    }

    private function buildReturnItems(array $order, array $items): array
    {
        $orderItems = [];
        foreach ($order['items'] as $oi) {
            $orderItems[$oi['id']] = $oi;
        }

        $result = [];
        foreach ($items as $item) {
            $orderItemId = $item['order_item_id'];
            if (! isset($orderItems[$orderItemId])) {
                throw new InvalidArgumentException("Order item {$orderItemId} not found in order");
            }
            $orderQty = (float) ($orderItems[$orderItemId]['quantity'] ?? 0);
            $already = $this->repo->alreadyReturnedQty($orderItemId);
            $available = $orderQty - $already;
            $qty = (float) $item['quantity_returned'];
            if ($qty > $available + 1e-6) {
                throw new InvalidArgumentException("Cannot return more than {$available} units for item {$orderItemId}");
            }
            $price = (float) ($orderItems[$orderItemId]['final_price'] ?? $orderItems[$orderItemId]['base_price'] ?? 0);
            $result[] = $item + ['line_total' => round($price * $qty, 2), 'item_condition' => $item['item_condition']];
        }
        return $result;
    }

    private function restockItems(array $return, ?array $order): void
    {
        if (! $order || empty($return['items'])) {
            return;
        }
        $branchId = $order['branch_id'] ?? null;
        if (! $branchId) { return; }

        $db = $this->repo->db(); // Use repo's db connection

        foreach ($return['items'] as $item) {
            $qty = (float) ($item['quantity_returned'] ?? 0);
            if ($qty <= 0) { continue; }

            // When items come from repo they don't include product_id; fetch from order_items table
            $orderItemId = (int) ($item['order_item_id'] ?? 0);
            $orderItem = $db->table('order_items')->where('id', $orderItemId)->get()->getRowArray();
            if (! $orderItem) { continue; }
            
            $productId = (int) $orderItem['product_id'];
            $variantId = isset($orderItem['variant_id']) ? (int) $orderItem['variant_id'] : null;

            try {
                $this->inventoryRepo->adjustStockWithLock($productId, $variantId, (int)$branchId, $qty);
            } catch (\Throwable $e) {
                log_message('error', 'Failed to restock item during return completion: ' . $e->getMessage());
                // Decide if we should re-throw or just log. For now, log and continue.
                continue;
            }

            $this->movements->log(
                branchId: (int)$branchId,
                productId: $productId,
                variantId: $variantId,
                type: 'return',
                quantity: $qty,
                referenceType: 'return',
                referenceId: (int) $return['id'],
                notes: sprintf('Restock from return %s', $return['return_number'] ?? ''),
                createdBy: $return['approved_by'] ?? null
            );
        }
    }

    private function assertOrderCompleted(array $order): void
    {
        if (($order['status'] ?? null) !== 'completed') {
            throw new InvalidArgumentException('Can only return completed orders');
        }
    }

    private function assertWithinWindow(array $order): void
    {
        $completedAt = $order['completed_at'] ?? $order['updated_at'] ?? $order['created_at'] ?? null;
        if (! $completedAt) {
            return;
        }
        $diffDays = (int) floor((time() - strtotime($completedAt)) / 86400);
        if ($diffDays > $this->returnWindowDays) {
            throw new InvalidArgumentException('Return window expired (30 days)');
        }
    }

    private function assertCustomerMatch(array $order, int $customerId): void
    {
        if (isset($order['customer_id']) && $order['customer_id'] && (int) $order['customer_id'] !== $customerId) {
            throw new InvalidArgumentException('Customer does not match order');
        }
    }

    private function emit(string $event, array $payload): void
    {
        if (! $this->webhooks) { return; }
        try {
            $this->webhooks->dispatch($event, $payload);
        } catch (\Throwable $e) {
            log_message('error', 'Webhook dispatch failed: ' . $e->getMessage());
        }
    }

    private function pagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $totalPages = (int) ceil($total / ($limit ?: 1));
        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }
}
