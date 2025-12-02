<?php

namespace App\Services\DeliveryNotes;

use App\Repositories\DeliveryNotes\DeliveryNoteItemRepository;
use App\Repositories\DeliveryNotes\DeliveryNoteRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Orders\OrderRepository;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Inventory\StockLedgerService;
use App\Services\Products\ProductBatchService;
use App\Services\Products\ProductSerialNumberService;
use App\Validators\DeliveryNoteValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Delivery note business logic.
 *
 * @agent-service: Delivery notes
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class DeliveryNoteService
{
    private DeliveryNoteRepository $notes;
    private DeliveryNoteItemRepository $items;
    private DeliveryNoteValidator $validator;
    private OrderRepository $orders;
    private InventoryRepository $inventory;
    private InventoryMovementLogger $movementLogger;
    private ProductBatchService $batchService;
    private ProductSerialNumberService $serialService;
    private StockLedgerService $ledger;

    public function __construct(
        ?DeliveryNoteRepository $notes = null,
        ?DeliveryNoteItemRepository $items = null,
        ?DeliveryNoteValidator $validator = null,
        ?OrderRepository $orders = null,
        ?InventoryRepository $inventory = null,
        ?InventoryMovementLogger $movementLogger = null,
        ?ProductBatchService $batchService = null,
        ?ProductSerialNumberService $serialService = null,
        ?StockLedgerService $ledger = null
    ) {
        $this->notes = $notes ?? new DeliveryNoteRepository();
        $this->items = $items ?? new DeliveryNoteItemRepository();
        $this->validator = $validator ?? new DeliveryNoteValidator();
        $this->orders = $orders ?? new OrderRepository();
        $this->inventory = $inventory ?? new InventoryRepository();
        $this->movementLogger = $movementLogger ?? new InventoryMovementLogger();
        $this->batchService = $batchService ?? new ProductBatchService();
        $this->serialService = $serialService ?? new ProductSerialNumberService();
        $this->ledger = $ledger ?? new StockLedgerService();
    }

    /** List delivery notes. @agent-use: GET /api/delivery-notes */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->notes->list($filters)];
    }

    /** Detail. @agent-use: GET /api/delivery-notes/{id} */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireNote($id)];
    }

    /** Create manually. */
    public function create(array $input): array
    {
        $payload = $this->validator->validateCreate($input);
        $number = $this->notes->nextNumber((int) $payload['branch_id']);
        $note = $this->notes->create([
            'delivery_number' => $number,
            'order_id' => $payload['order_id'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
            'branch_id' => $payload['branch_id'],
            'delivery_date' => $payload['delivery_date'],
            'expected_delivery_date' => $payload['expected_delivery_date'] ?? null,
            'shipping_address' => $payload['shipping_address'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'status' => 'draft',
        ], $payload['items']);

        return ['success' => true, 'data' => $this->requireNote($note['id'])];
    }

    /** Create from order (copies items). */
    public function createFromOrder(array $input): array
    {
        $payload = $this->validator->validateCreateFromOrder($input);
        $order = $this->orders->findById((int) $payload['order_id']);
        if (! $order) {
            throw new RuntimeException('Order not found');
        }
        $items = [];
        foreach ($order['items'] ?? [] as $item) {
            $items[] = [
                'order_item_id' => $item['id'] ?? null,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'batch_id' => null,
                'serial_number' => null,
                'quantity' => (float) ($item['quantity'] ?? 0),
                'delivered_quantity' => 0,
            ];
        }
        if (empty($items)) {
            throw new RuntimeException('Order has no items');
        }
        $number = $this->notes->nextNumber((int) $payload['branch_id']);
        $note = $this->notes->create([
            'delivery_number' => $number,
            'order_id' => (int) $payload['order_id'],
            'customer_id' => $order['customer_id'] ?? null,
            'branch_id' => $payload['branch_id'],
            'delivery_date' => $payload['delivery_date'],
            'expected_delivery_date' => $payload['expected_delivery_date'] ?? null,
            'shipping_address' => $payload['shipping_address'] ?? ($order['shipping_address'] ?? null),
            'notes' => $payload['notes'] ?? null,
            'status' => 'draft',
        ], $items);

        return ['success' => true, 'data' => $this->requireNote($note['id'])];
    }

    /** Confirm note. @agent-use: POST /api/delivery-notes/{id}/confirm */
    public function confirm(int $id, ?int $userId = null): array
    {
        $note = $this->requireNote($id);
        $this->assertStatus($note, ['draft'], 'confirmed');
        $this->notes->updateStatus($id, 'confirmed', [
            'confirmed_by' => $userId,
            'confirmed_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'data' => $this->requireNote($id)];
    }

    /** Update tracking/shipping info and status shipped. */
    public function ship(int $id, array $input): array
    {
        $note = $this->requireNote($id);
        $this->assertStatus($note, ['confirmed', 'shipped'], 'shipped');
        $tracking = $this->validator->validateShip($input);
        $this->notes->updateStatus($id, 'shipped', $tracking);
        return ['success' => true, 'data' => $this->requireNote($id)];
    }

    /** Deliver items (partial supported). */
    public function deliver(int $id, array $input): array
    {
        $note = $this->requireNote($id);
        $this->assertStatus($note, ['confirmed', 'shipped'], 'delivered');
        $payload = $this->validator->validateDeliver($input);
        $itemsById = [];
        foreach ($note['items'] as $item) {
            $itemsById[(int) $item['id']] = $item;
        }

        $branchId = (int) ($note['branch_id'] ?? 0);
        $orderId = $note['order_id'] ?? null;

        $db = $this->notes->db();
        $db->transBegin();
        try {
            foreach ($payload['items'] as $deliverItem) {
                $itemId = (int) $deliverItem['delivery_note_item_id'];
                $item = $itemsById[$itemId] ?? null;
                if (! $item) {
                    throw new RuntimeException('Item not found');
                }
                $delta = (float) $deliverItem['quantity'];
                $newDelivered = (float) ($item['delivered_quantity'] ?? 0) + $delta;
                if ($newDelivered - (float) $item['quantity'] > 0.0001) {
                    throw new InvalidArgumentException('Over delivery not allowed');
                }

                $this->notes->updateDeliveredQuantity($itemId, $delta);
                $this->adjustInventoryForItem($item, $delta, $branchId, $orderId, $payload['delivered_by'] ?? null);

                $itemsById[$itemId]['delivered_quantity'] = $newDelivered;
            }

            $allDelivered = true;
            foreach ($itemsById as $row) {
                if ((float) $row['delivered_quantity'] + 0.0001 < (float) $row['quantity']) {
                    $allDelivered = false;
                    break;
                }
            }
            $status = $allDelivered ? 'delivered' : 'shipped';
            $extra = [];
            if ($allDelivered) {
                $extra['delivered_by'] = $payload['delivered_by'] ?? null;
                $extra['delivered_at'] = date('Y-m-d H:i:s');
            }
            $this->notes->updateStatus($id, $status, $extra);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return ['success' => true, 'data' => $this->requireNote($id)];
    }

    /** Cancel note and rollback delivered quantities. */
    public function cancel(int $id, ?int $userId = null): array
    {
        $note = $this->requireNote($id);
        if ($note['status'] === 'cancelled') {
            return ['success' => true, 'data' => $note];
        }

        $branchId = (int) ($note['branch_id'] ?? 0);
        $db = $this->notes->db();
        $db->transBegin();
        try {
            foreach ($note['items'] as $item) {
                $delivered = (float) ($item['delivered_quantity'] ?? 0);
                if ($delivered <= 0) {
                    continue;
                }
                $this->adjustInventoryForItem($item, -$delivered, $branchId, $note['order_id'] ?? null, $userId, 'delivery_cancel');
                $this->items->update((int) $item['id'], ['delivered_quantity' => 0]);
            }
            $this->notes->updateStatus($id, 'cancelled', ['notes' => ($note['notes'] ?? null)]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return ['success' => true, 'data' => $this->requireNote($id)];
    }

    private function adjustInventoryForItem(array $item, float $delta, int $branchId, ?int $orderId, ?int $userId, string $type = 'delivery'): void
    {
        if ($branchId <= 0) {
            return;
        }
        $productId = (int) $item['product_id'];
        $variantId = $item['variant_id'] ? (int) $item['variant_id'] : null;
        $batchId = $item['batch_id'] ? (int) $item['batch_id'] : null;
        $serials = $this->serialsFromItem($item['serial_number'] ?? null);
        if ($batchId) {
            $this->batchService->adjustQuantity($batchId, [
                'quantity_delta' => -$delta, // delta >0 means deliver => reduce batch
                'branch_id' => $branchId,
                'warehouse_id' => $branchId,
                'reference_type' => 'delivery_note',
                'reference_id' => $item['delivery_note_id'] ?? null,
                'reason' => $type,
                'movement_type' => $type,
                'serial_number' => $serials ? implode(',', $serials) : null,
            ]);
        } else {
            $this->inventory->adjustStockWithLock($productId, $variantId, $branchId, -$delta, $branchId);
            $this->movementLogger->log(
                branchId: $branchId,
                productId: $productId,
                variantId: $variantId,
                type: $type,
                quantity: -$delta,
                batchId: $batchId,
                serialNumber: $serials ? implode(',', $serials) : null,
                referenceType: 'delivery_note',
                referenceId: $item['delivery_note_id'] ?? null,
                notes: null,
                createdBy: $userId
            );
        }

        $this->ledger->record([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'branch_id' => $branchId,
            'batch_id' => $batchId,
            'movement_date' => date('Y-m-d H:i:s'),
            'reference_type' => 'delivery_note',
            'reference_id' => (int) ($item['delivery_note_id'] ?? $orderId ?? 0),
            'reference_seq' => (int) ($item['id'] ?? 1),
            'qty_delta' => -$delta,
            'serial_number' => $serials ? implode(',', $serials) : null,
        ]);

        foreach ($serials as $serial) {
            $this->serialService->ensureAvailableForOrder($serial, $orderId);
            if ($delta > 0) {
                $this->serialService->sell([
                    'serial_numbers' => [$serial],
                    'order_id' => $orderId ?? 0,
                ]);
            } elseif ($delta < 0) {
                $this->serialService->markReturned([
                    'serial_numbers' => [$serial],
                    'order_id' => $orderId ?? 0,
                ]);
            }
        }
    }

    private function requireNote(int $id): array
    {
        $note = $this->notes->find($id);
        if (! $note) {
            throw new RuntimeException('Delivery note not found');
        }
        return $note;
    }

    private function assertStatus(array $note, array $allowedFrom, string $to): void
    {
        if (! in_array($note['status'], $allowedFrom, true)) {
            throw new InvalidArgumentException("Cannot transition from {$note['status']} to {$to}");
        }
    }

    private function serialsFromItem($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }
        if (! is_array($value)) {
            return [];
        }
        $serials = array_map(static fn ($s) => is_numeric($s) ? (string) $s : (is_string($s) ? trim($s) : ''), $value);
        $serials = array_filter($serials, static fn ($s) => $s !== '');
        return array_values(array_unique($serials));
    }
}
