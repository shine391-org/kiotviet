<?php

namespace App\Services\Orders;

use App\Repositories\OrderStatusLogs\OrderStatusLogRepository;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Orders\OrderPaymentRepository;
use App\Models\CashTransactionModel;
use App\Services\CashTransactions\CashTransactionService;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Inventory\InventoryService;
use App\Services\Products\ProductBatchService;
use App\Services\Products\ProductSerialNumberService;
use App\Services\Webhooks\WebhookDispatcher;
use App\Validators\CashTransactionReferenceValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Order status workflow.
 *
 * @agent-service: Order status management
 * @agent-pattern: State + side-effects
 * @agent-reusable: MEDIUM
 */
class OrderStatusService
{
    protected OrderRepository $orders;
    protected OrderStatusTransition $transition;
    protected OrderStatusLogRepository $logs;
    protected OrderPaymentRepository $orderPayments;
    protected \App\Repositories\Inventory\InventoryRepository $inventoryRepo;
    protected InventoryMovementLogger $movementLogger;
    protected ProductBatchService $batchService;
    protected ProductSerialNumberService $serialService;
    protected ?WebhookDispatcher $webhooks;

    public function __construct(
        ?OrderRepository $orders = null,
        ?OrderStatusTransition $transition = null,
        ?OrderStatusLogRepository $logs = null,
        ?OrderPaymentRepository $orderPayments = null,
        ?\App\Repositories\Inventory\InventoryRepository $inventoryRepo = null,
        ?InventoryMovementLogger $movementLogger = null,
        ?ProductBatchService $batchService = null,
        ?ProductSerialNumberService $serialService = null,
        ?WebhookDispatcher $webhooks = null
    ) {
        $this->orders = $orders ?? new OrderRepository();
        $this->transition = $transition ?? new OrderStatusTransition();
        $this->logs = $logs ?? new OrderStatusLogRepository();
        $this->orderPayments = $orderPayments ?? new OrderPaymentRepository();
        $this->inventoryRepo = $inventoryRepo ?? new \App\Repositories\Inventory\InventoryRepository();
        $this->movementLogger = $movementLogger ?? new InventoryMovementLogger();
        $this->batchService = $batchService ?? new ProductBatchService();
        $this->serialService = $serialService ?? new ProductSerialNumberService();
        $this->webhooks = $webhooks;
    }

    /** Update status with side effects. */
    public function updateStatus(int $orderId, string $toStatus, ?int $userId = null, ?string $notes = null): array
    {
        $order = $this->orders->findById($orderId);
        if (! $order) {
            throw new RuntimeException('Order not found');
        }

        $fromStatus = $order['status'] ?? 'draft';
        if (! $this->transition->isValid($fromStatus, $toStatus)) {
            throw new InvalidArgumentException("Cannot transition from {$fromStatus} to {$toStatus}");
        }

        // side effects before status change
        $this->applySideEffects($order, $fromStatus, $toStatus, $userId);

        // update status + timestamps
        $updates = ['status' => $toStatus, 'updated_at' => date('Y-m-d H:i:s')];
        $timestampField = $this->timestampField($toStatus);
        if ($timestampField) {
            $updates[$timestampField] = date('Y-m-d H:i:s');
        }
        if ($toStatus === 'cancelled' && $notes) {
            $updates['cancellation_reason'] = $notes;
        }
        if ($toStatus === 'completed' && ($order['payment_method'] ?? null) === 'COD') {
            $updates['paid_amount'] = $order['total'] ?? 0;
            $updates['debt_amount'] = 0;
            $updates['is_paid'] = 1;
            $updates['cod_collected'] = 1;
        }

        $this->orders->updateFields($orderId, $updates);

        // log status change
        $this->logs->create($orderId, $fromStatus, $toStatus, $userId, $notes);

        $updated = $this->orders->findById($orderId);

        // post-status side effects (requires updated status)
        if ($toStatus === 'completed') {
            $this->handleCompletedStatus($updated, $userId);
        }

        $this->emitStatus($toStatus, $updated);
        return ['success' => true, 'data' => $updated];
    }

    private function timestampField(string $status): ?string
    {
        return match ($status) {
            'confirmed' => 'confirmed_at',
            'processing' => 'processing_at',
            'shipping' => 'shipping_at',
            'delivered' => 'delivered_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
            default => null,
        };
    }

    private function applySideEffects(array $order, string $from, string $to, ?int $userId): void
    {
        // Deduct inventory when entering processing
        if ($to === 'processing' && $from !== 'processing') {
            $this->deductInventory($order, $userId);
        }

        // Restore inventory when cancelling after deduction
        if ($to === 'cancelled' && in_array($from, ['processing', 'shipping'], true)) {
            $this->restoreInventory($order, $userId);
        }
    }

    /**
     * Auto-create cash receipt when order completes (CASH only).
     */
    protected function handleCompletedStatus(array $order, ?int $userId): void
    {
        $this->markSerialsSold($order);
        $db = \Config\Database::connect();
        $cashService = new CashTransactionService(null, null, new CashTransactionReferenceValidator($db));
        $creatorId = $userId ?? ($order['created_by'] ?? 1);
        $payments = $this->orderPayments->findByOrder((int) $order['id']);

        // Fallback: no payment rows yet but order is cash -> treat order total as one payment
        if (empty($payments) && (($order['payment_method'] ?? '') === 'CASH')) {
            $payments = [[
                'id' => null,
                'payment_method' => 'CASH',
                'amount' => (float) ($order['total'] ?? 0),
            ]];
        }

        foreach ($payments as $payment) {
            if (($payment['payment_method'] ?? '') !== 'CASH') {
                continue;
            }
            $referenceType = $payment['id'] ? CashTransactionModel::REFERENCE_ORDER_PAYMENT : CashTransactionModel::REFERENCE_ORDER;
            $referenceId = $payment['id'] ? (int) $payment['id'] : (int) $order['id'];
            try {
                $cashService->createReceipt([
                    'branch_id' => (int) $order['branch_id'],
                    'category' => CashTransactionModel::CATEGORY_SALES,
                    'amount' => (float) $payment['amount'],
                    'payment_method' => 'cash',
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'reference_code' => $order['order_number'] ?? ($order['code'] ?? null),
                    'description' => 'Thu tiền đơn #' . ($order['order_number'] ?? $order['id']) . ' - phần tiền mặt',
                    'transaction_date' => date('Y-m-d'),
                    'created_by' => (int) $creatorId,
                ]);
            } catch (\InvalidArgumentException $e) {
                // skip duplicates/validation errors for already-created receipts
            }
        }
    }

    private function deductInventory(array $order, ?int $userId): void
    {
        $items = $order['items'] ?? [];
        if (! $items) {
            return;
        }
        foreach ($items as $item) {
            $this->adjustInventory($order, $item, -($item['quantity'] ?? 0), 'sale', $userId);
        }
    }

    private function restoreInventory(array $order, ?int $userId): void
    {
        $items = $order['items'] ?? [];
        if (! $items) {
            return;
        }
        foreach ($items as $item) {
            $this->adjustInventory($order, $item, ($item['quantity'] ?? 0), 'adjustment', $userId, 'Cancel order restore');
        }
    }

    private function adjustInventory(array $order, array $item, float $delta, string $type, ?int $userId, ?string $notes = null): void
    {
        $branchId = $order['branch_id'] ?? null;
        $productId = $item['product_id'] ?? null;
        $variantId = $item['variant_id'] ?? null;
        $batchId = isset($item['batch_id']) ? (int) $item['batch_id'] : null;
        $serials = $this->serialsFromItem($item);
        $serialString = $serials ? implode(',', $serials) : null;
        if (! $branchId || ! $productId) {
            return;
        }

        try {
            if ($batchId) {
                $this->batchService->adjustQuantity($batchId, [
                    'quantity_delta' => $delta,
                    'reference_type' => 'order',
                    'reference_id' => (int) $order['id'],
                    'reason' => $notes,
                    'branch_id' => (int) $branchId,
                    'warehouse_id' => (int) $branchId,
                    'serial_number' => $serialString,
                    'movement_type' => $type,
                ]);
            } else {
                $this->inventoryRepo->adjustStockWithLock((int)$productId, $variantId ? (int)$variantId : null, (int)$branchId, $delta, (int) $branchId);
                $this->movementLogger->log(
                    branchId: (int) $branchId,
                    productId: (int) $productId,
                    variantId: $variantId ? (int) $variantId : null,
                    batchId: null,
                    serialNumber: $serialString,
                    type: $type,
                    quantity: $delta,
                    referenceType: 'order',
                    referenceId: (int) $order['id'],
                    notes: $notes,
                    createdBy: $userId
                );
            }
        } catch (\Throwable $e) {
            // If locking fails or stock is insufficient, rethrow.
            throw new RuntimeException('Failed to adjust inventory: ' . $e->getMessage(), 0, $e);
        }

        if ($delta < 0 && $serials) {
            $this->serialService->reserve([
                'serial_numbers' => $serials,
                'order_id' => (int) $order['id'],
            ]);
        } elseif ($delta > 0 && $serials) {
            $this->serialService->release($serials);
        }

        $this->emitInventoryIfNeeded((int) $branchId, (int) $productId, $variantId ? (int) $variantId : null);
    }

    private function emitInventoryIfNeeded(int $branchId, int $productId, ?int $variantId): void
    {
        if (! $this->webhooks) {
            return;
        }
        $db = \Config\Database::connect();
        if (! $db->tableExists('inventory_stock')) {
            return;
        }
        $row = $db->table('inventory_stock')
            ->select('quantity_on_hand, quantity_reserved, minimum_stock')
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->get()->getRowArray();
        if (! $row) {
            return;
        }
        $available = (float) ($row['quantity_on_hand'] ?? 0) - (float) ($row['quantity_reserved'] ?? 0);
        $minimum = isset($row['minimum_stock']) ? (float) $row['minimum_stock'] : null;

        $payload = [
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'available' => $available,
            'minimum_stock' => $minimum,
        ];

        $eventName = null;
        if ($available <= 0) {
            $eventName = 'inventory.out_of_stock';
        } elseif ($minimum !== null && $available <= $minimum) {
            $eventName = 'inventory.low_stock';
        }

        if ($eventName) {
            try {
                $this->webhooks->dispatch($eventName, $payload);
            } catch (\Throwable $e) {
                log_message('error', 'Webhook dispatch failed: ' . $e->getMessage());
            }
        }
    }

    private function emitStatus(string $status, array $order): void
    {
        if (! $this->webhooks) {
            return;
        }
        $eventName = match ($status) {
            'confirmed' => 'order.confirmed',
            'processing' => 'order.processing',
            'shipping' => 'order.shipping',
            'delivered' => 'order.delivered',
            'completed' => 'order.completed',
            'cancelled' => 'order.cancelled',
            default => null,
        };
        if (! $eventName) {
            return;
        }
        try {
            $this->webhooks->dispatch($eventName, $order);
        } catch (\Throwable $e) {
            log_message('error', 'Webhook dispatch failed: ' . $e->getMessage());
        }
    }

    private function serialsFromItem(array $item): array
    {
        $serials = $item['serial_numbers'] ?? [];
        if (is_string($serials)) {
            $decoded = json_decode($serials, true);
            if (is_array($decoded)) {
                $serials = $decoded;
            } else {
                $serials = array_filter(array_map('trim', explode(',', $serials)));
            }
        }
        if (! is_array($serials)) {
            return [];
        }
        $serials = array_map(static fn ($s) => is_numeric($s) ? (string) $s : (is_string($s) ? trim($s) : ''), $serials);
        $serials = array_filter($serials, static fn ($s) => $s !== '');
        return array_values(array_unique($serials));
    }

    private function markSerialsSold(array $order): void
    {
        $items = $order['items'] ?? [];
        if (empty($items)) {
            return;
        }
        foreach ($items as $item) {
            $serials = $this->serialsFromItem($item);
            if (empty($serials)) {
                continue;
            }
            $this->serialService->sell([
                'serial_numbers' => $serials,
                'order_id' => (int) ($order['id'] ?? 0),
            ]);
        }
    }
}
