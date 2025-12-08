<?php

namespace Tests\Services;

use App\Services\DeliveryNotes\DeliveryNoteService;
use App\Repositories\DeliveryNotes\DeliveryNoteRepository;
use App\Repositories\DeliveryNotes\DeliveryNoteItemRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Orders\OrderRepository;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Inventory\StockLedgerService;
use App\Services\Products\ProductBatchService;
use App\Services\Products\ProductSerialNumberService;
use App\Validators\DeliveryNoteValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * @agent-test: DeliveryNoteService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class DeliveryNoteServiceTest extends CIUnitTestCase
{
    private DeliveryNoteService $service;
    private DeliveryNoteServiceFakeRepo $noteRepo;
    private DeliveryNoteServiceFakeItemRepo $itemRepo;
    private DeliveryNoteServiceFakeOrderRepo $orderRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->noteRepo = new DeliveryNoteServiceFakeRepo();
        $this->itemRepo = new DeliveryNoteServiceFakeItemRepo();
        $this->orderRepo = new DeliveryNoteServiceFakeOrderRepo();

        $validator = new DeliveryNoteValidator();
        $inventoryRepo = new DeliveryNoteServiceFakeInventoryRepo();
        $movementLogger = new DeliveryNoteServiceFakeMovementLogger();
        $batchService = new DeliveryNoteServiceFakeBatchService();
        $serialService = new DeliveryNoteServiceFakeSerialService();
        $ledgerService = new DeliveryNoteServiceFakeLedgerService();

        $this->service = new DeliveryNoteService(
            $this->noteRepo,
            $this->itemRepo,
            $validator,
            $this->orderRepo,
            $inventoryRepo,
            $movementLogger,
            $batchService,
            $serialService,
            $ledgerService
        );
    }

    public function testListReturnsDeliveryNotes(): void
    {
        $result = $this->service->list([]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    public function testShowReturnsDeliveryNote(): void
    {
        $result = $this->service->show(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
        $this->assertSame('DN-1-000001', $result['data']['delivery_number']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Delivery note not found');

        $this->service->show(999);
    }

    public function testCreateReturnsNewDeliveryNote(): void
    {
        $input = [
            'branch_id' => 1,
            'customer_id' => 1,
            'delivery_date' => date('Y-m-d'),
            'items' => [
                ['product_id' => 1, 'quantity' => 5],
            ],
        ];

        $result = $this->service->create($input);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('draft', $result['data']['status']);
    }

    public function testCreateValidatesRequiredBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->create([
            'items' => [['product_id' => 1, 'quantity' => 5]],
        ]);
    }

    public function testCreateValidatesRequiredItems(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('items is required');

        $this->service->create([
            'branch_id' => 1,
            'items' => [],
        ]);
    }

    public function testCreateFromOrderReturnsDeliveryNote(): void
    {
        $input = [
            'order_id' => 1,
            'branch_id' => 1,
            'delivery_date' => date('Y-m-d'),
        ];

        $result = $this->service->createFromOrder($input);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['order_id']);
    }

    public function testCreateFromOrderThrowsWhenOrderNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Order not found');

        $this->service->createFromOrder([
            'order_id' => 999,
            'branch_id' => 1,
        ]);
    }

    public function testCreateFromOrderThrowsWhenOrderHasNoItems(): void
    {
        $this->orderRepo->fakeOrders[2]['items'] = [];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Order has no items');

        $this->service->createFromOrder([
            'order_id' => 2,
            'branch_id' => 1,
        ]);
    }

    public function testConfirmTransitionsDraftToConfirmed(): void
    {
        $result = $this->service->confirm(1, 1);

        $this->assertTrue($result['success']);
        $this->assertSame('confirmed', $result['data']['status']);
    }

    public function testConfirmThrowsWhenNotDraft(): void
    {
        $this->noteRepo->fakeNotes[1]['status'] = 'confirmed';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from confirmed to confirmed');

        $this->service->confirm(1, 1);
    }

    public function testShipUpdatesTrackingInfo(): void
    {
        $this->noteRepo->fakeNotes[1]['status'] = 'confirmed';

        $result = $this->service->ship(1, [
            'tracking_number' => 'TRK123456',
            'carrier' => 'GHTK',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('shipped', $result['data']['status']);
    }

    public function testShipThrowsWhenNotConfirmed(): void
    {
        $this->noteRepo->fakeNotes[1]['status'] = 'draft';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from draft to shipped');

        $this->service->ship(1, ['tracking_number' => 'TRK123']);
    }

    public function testDeliverUpdatesDeliveredQuantities(): void
    {
        $this->noteRepo->fakeNotes[1]['status'] = 'confirmed';

        $result = $this->service->deliver(1, [
            'delivered_by' => 1,
            'items' => [
                ['delivery_note_item_id' => 1, 'quantity' => 3],
            ],
        ]);

        $this->assertTrue($result['success']);
    }

    public function testDeliverThrowsWhenOverDelivery(): void
    {
        $this->noteRepo->fakeNotes[1]['status'] = 'confirmed';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Over delivery not allowed');

        $this->service->deliver(1, [
            'delivered_by' => 1,
            'items' => [
                ['delivery_note_item_id' => 1, 'quantity' => 100], // More than available
            ],
        ]);
    }

    public function testDeliverThrowsOnInvalidItem(): void
    {
        $this->noteRepo->fakeNotes[1]['status'] = 'confirmed';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Item not found');

        $this->service->deliver(1, [
            'delivered_by' => 1,
            'items' => [
                ['delivery_note_item_id' => 999, 'quantity' => 1],
            ],
        ]);
    }

    public function testCancelReturnsSuccess(): void
    {
        $result = $this->service->cancel(1, 1);

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $result['data']['status']);
    }

    public function testCancelAlreadyCancelledIsNoop(): void
    {
        $this->noteRepo->fakeNotes[1]['status'] = 'cancelled';

        $result = $this->service->cancel(1, 1);

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $result['data']['status']);
    }
}

// Fake repository classes for testing

class DeliveryNoteServiceFakeRepo extends DeliveryNoteRepository
{
    public array $fakeNotes = [];
    private int $nextId = 3;

    public function __construct()
    {
        $this->fakeNotes = [
            1 => [
                'id' => 1,
                'delivery_number' => 'DN-1-000001',
                'order_id' => 1,
                'customer_id' => 1,
                'branch_id' => 1,
                'status' => 'draft',
                'delivery_date' => '2024-01-01',
                'shipping_address' => '123 Main St',
                'notes' => 'Test note',
                'items' => [
                    [
                        'id' => 1,
                        'delivery_note_id' => 1,
                        'product_id' => 1,
                        'variant_id' => null,
                        'batch_id' => null,
                        'serial_number' => null,
                        'quantity' => 5,
                        'delivered_quantity' => 0,
                    ]
                ],
            ],
            2 => [
                'id' => 2,
                'delivery_number' => 'DN-1-000002',
                'order_id' => 2,
                'customer_id' => 2,
                'branch_id' => 1,
                'status' => 'confirmed',
                'delivery_date' => '2024-01-02',
                'shipping_address' => '456 Oak Ave',
                'notes' => null,
                'items' => [],
            ],
        ];
    }

    public function db(): \CodeIgniter\Database\BaseConnection
    {
        // Return the test database connection for transaction support
        return \Config\Database::connect('tests');
    }

    public function list(array $filters = []): array
    {
        return array_values($this->fakeNotes);
    }

    public function find(int $id): ?array
    {
        return $this->fakeNotes[$id] ?? null;
    }

    public function findByNumber(string $number): ?array
    {
        foreach ($this->fakeNotes as $note) {
            if ($note['delivery_number'] === $number) {
                return $note;
            }
        }
        return null;
    }

    public function create(array $note, array $items): array
    {
        $id = $this->nextId++;
        $note['id'] = $id;
        $note['created_at'] = date('Y-m-d H:i:s');
        $note['updated_at'] = date('Y-m-d H:i:s');
        
        $noteItems = [];
        foreach ($items as $idx => $item) {
            $noteItems[] = $item + [
                'id' => $id * 100 + $idx,
                'delivery_note_id' => $id,
            ];
        }
        $note['items'] = $noteItems;
        $this->fakeNotes[$id] = $note;
        
        return $note;
    }

    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        if (isset($this->fakeNotes[$id])) {
            $this->fakeNotes[$id]['status'] = $status;
            $this->fakeNotes[$id] = array_merge($this->fakeNotes[$id], $extra);
        }
    }

    public function updateTracking(int $id, array $data): void
    {
        if (isset($this->fakeNotes[$id])) {
            $this->fakeNotes[$id] = array_merge($this->fakeNotes[$id], $data);
        }
    }

    public function updateDeliveredQuantity(int $itemId, float $delta): void
    {
        foreach ($this->fakeNotes as &$note) {
            foreach ($note['items'] as &$item) {
                if ($item['id'] === $itemId) {
                    $item['delivered_quantity'] = ($item['delivered_quantity'] ?? 0) + $delta;
                    return;
                }
            }
        }
    }

    public function nextNumber(int $branchId): string
    {
        $count = 0;
        foreach ($this->fakeNotes as $note) {
            if (($note['branch_id'] ?? 0) === $branchId) {
                $count++;
            }
        }
        return 'DN-' . $branchId . '-' . str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}

class DeliveryNoteServiceFakeItemRepo extends DeliveryNoteItemRepository
{
    public array $fakeItems = [];

    public function __construct()
    {
        // No parent call
    }

    public function find(int $id): ?array
    {
        return $this->fakeItems[$id] ?? null;
    }

    public function byDelivery(int $deliveryNoteId): array
    {
        return array_filter($this->fakeItems, fn($i) => $i['delivery_note_id'] === $deliveryNoteId);
    }

    public function update(int $id, array $data): bool
    {
        if (isset($this->fakeItems[$id])) {
            $this->fakeItems[$id] = array_merge($this->fakeItems[$id], $data);
            return true;
        }
        return false;
    }
}

class DeliveryNoteServiceFakeOrderRepo extends OrderRepository
{
    public array $fakeOrders = [];

    public function __construct()
    {
        $this->fakeOrders = [
            1 => [
                'id' => 1,
                'order_number' => 'ORD-001',
                'customer_id' => 1,
                'branch_id' => 1,
                'shipping_address' => '123 Main St',
                'items' => [
                    [
                        'id' => 1,
                        'product_id' => 1,
                        'variant_id' => null,
                        'quantity' => 5,
                    ]
                ],
            ],
            2 => [
                'id' => 2,
                'order_number' => 'ORD-002',
                'customer_id' => 2,
                'branch_id' => 1,
                'shipping_address' => '456 Oak Ave',
                'items' => [
                    [
                        'id' => 2,
                        'product_id' => 2,
                        'variant_id' => null,
                        'quantity' => 3,
                    ]
                ],
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fakeOrders[$id] ?? null;
    }
}

class DeliveryNoteServiceFakeInventoryRepo extends InventoryRepository
{
    public function __construct()
    {
        // No parent call
    }

    public function adjustStockWithLock(int $productId, ?int $variantId, int $warehouseId, float $deltaQty, ?int $branchId = null): array
    {
        return ['product_id' => $productId, 'new_quantity' => 100 + $deltaQty];
    }
}

class DeliveryNoteServiceFakeMovementLogger extends InventoryMovementLogger
{
    public function __construct()
    {
        // No parent call
    }

    public function log(
        int $branchId,
        int $productId,
        ?int $variantId,
        string $type,
        float $quantity,
        ?int $batchId = null,
        ?string $serialNumber = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): array {
        return ['id' => 1, 'branch_id' => $branchId, 'quantity' => $quantity];
    }
}

class DeliveryNoteServiceFakeBatchService extends ProductBatchService
{
    public function __construct()
    {
        // No parent call
    }

    public function adjustQuantity(int $id, array $data): array
    {
        return ['id' => $id, 'adjusted' => true];
    }
}

class DeliveryNoteServiceFakeSerialService extends ProductSerialNumberService
{
    public function __construct()
    {
        // No parent call
    }

    public function ensureAvailableForOrder(string $serialNumber, ?int $orderId = null): array
    {
        return ['available' => true, 'serial_number' => $serialNumber];
    }

    public function sell(array $data): array
    {
        return ['sold' => $data['serial_numbers'] ?? []];
    }

    public function markReturned(array $data): array
    {
        return ['returned' => $data['serial_numbers'] ?? []];
    }
}

class DeliveryNoteServiceFakeLedgerService extends StockLedgerService
{
    public function __construct()
    {
        // No parent call
    }

    public function record(array $data): array
    {
        return ['id' => 1] + $data;
    }
}
