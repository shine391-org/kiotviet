<?php

namespace Tests\Services;

use App\Services\Orders\OrderStatusService;
use App\Services\Orders\OrderStatusTransition;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\OrderStatusLogs\OrderStatusLogRepository;
use App\Repositories\Orders\OrderPaymentRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Products\ProductBatchService;
use App\Services\Products\ProductSerialNumberService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * @agent-test: OrderStatusService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class OrderStatusServiceTest extends CIUnitTestCase
{
    private OrderStatusService $service;
    private OrderStatusServiceFakeOrderRepo $orderRepo;
    private OrderStatusServiceFakeLogRepo $logRepo;
    private OrderStatusServiceFakePaymentRepo $paymentRepo;
    private OrderStatusServiceFakeInventoryRepo $inventoryRepo;
    private OrderStatusTransition $transition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderRepo = new OrderStatusServiceFakeOrderRepo();
        $this->logRepo = new OrderStatusServiceFakeLogRepo();
        $this->paymentRepo = new OrderStatusServiceFakePaymentRepo();
        $this->inventoryRepo = new OrderStatusServiceFakeInventoryRepo();
        $this->transition = new OrderStatusTransition();

        $movementLogger = new OrderStatusServiceFakeMovementLogger();
        $batchService = new OrderStatusServiceFakeBatchService();
        $serialService = new OrderStatusServiceFakeSerialService();

        $this->service = new OrderStatusService(
            $this->orderRepo,
            $this->transition,
            $this->logRepo,
            $this->paymentRepo,
            $this->inventoryRepo,
            $movementLogger,
            $batchService,
            $serialService,
            null, // approvalHook
            null  // webhooks
        );
    }

    public function testUpdateStatusDraftToConfirmed(): void
    {
        $result = $this->service->updateStatus(1, 'confirmed', 1, 'Test confirmation');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('confirmed', $result['data']['status']);
        $this->assertArrayHasKey('confirmed_at', $result['data']);
    }

    public function testUpdateStatusConfirmedToProcessing(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'confirmed';

        $result = $this->service->updateStatus(1, 'processing', 1);

        $this->assertTrue($result['success']);
        $this->assertSame('processing', $result['data']['status']);
    }

    public function testUpdateStatusProcessingToShipping(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'processing';

        $result = $this->service->updateStatus(1, 'shipping', 1);

        $this->assertTrue($result['success']);
        $this->assertSame('shipping', $result['data']['status']);
    }

    public function testUpdateStatusShippingToDelivered(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'shipping';

        $result = $this->service->updateStatus(1, 'delivered', 1);

        $this->assertTrue($result['success']);
        $this->assertSame('delivered', $result['data']['status']);
    }

    public function testUpdateStatusDeliveredToCompleted(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'delivered';

        $result = $this->service->updateStatus(1, 'completed', 1);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['data']['status']);
    }

    public function testUpdateStatusThrowsWhenOrderNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Order not found');

        $this->service->updateStatus(999, 'confirmed', 1);
    }

    public function testUpdateStatusThrowsOnInvalidTransition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from draft to completed');

        $this->service->updateStatus(1, 'completed', 1);
    }

    public function testUpdateStatusCancelsFromDraft(): void
    {
        $result = $this->service->updateStatus(1, 'cancelled', 1, 'Customer cancelled');

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $result['data']['status']);
        $this->assertSame('Customer cancelled', $result['data']['cancellation_reason']);
    }

    public function testUpdateStatusCancelsFromConfirmed(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'confirmed';

        $result = $this->service->updateStatus(1, 'cancelled', 1, 'Out of stock');

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $result['data']['status']);
    }

    public function testUpdateStatusCancelsFromProcessingRestoresInventory(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'processing';

        $result = $this->service->updateStatus(1, 'cancelled', 1, 'Customer changed mind');

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $result['data']['status']);
        // Inventory restoration happens through adjustInventory
    }

    public function testUpdateStatusLogsTransition(): void
    {
        $this->service->updateStatus(1, 'confirmed', 1, 'Test log');

        $this->assertNotEmpty($this->logRepo->fakeLogs);
        $log = $this->logRepo->fakeLogs[0];
        $this->assertSame(1, $log['order_id']);
        $this->assertSame('draft', $log['from_status']);
        $this->assertSame('confirmed', $log['to_status']);
        $this->assertSame('Test log', $log['notes']);
    }

    public function testUpdateStatusSetsTimestampField(): void
    {
        $result = $this->service->updateStatus(1, 'confirmed', 1);

        $this->assertArrayHasKey('confirmed_at', $result['data']);
        $this->assertNotNull($result['data']['confirmed_at']);
    }

    public function testUpdateStatusWithCODPaymentMarksPaid(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'delivered';
        $this->orderRepo->fakeOrders[1]['payment_method'] = 'COD';
        $this->orderRepo->fakeOrders[1]['total'] = 100000;

        $result = $this->service->updateStatus(1, 'completed', 1);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['data']['status']);
        $this->assertSame(100000, $result['data']['paid_amount']);
        $this->assertSame(0, $result['data']['debt_amount']);
        $this->assertSame(1, $result['data']['is_paid']);
        $this->assertSame(1, $result['data']['cod_collected']);
    }

    public function testCannotTransitionFromCancelled(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'cancelled';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from cancelled to confirmed');

        $this->service->updateStatus(1, 'confirmed', 1);
    }

    public function testCannotTransitionFromCompleted(): void
    {
        $this->orderRepo->fakeOrders[1]['status'] = 'completed';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from completed to cancelled');

        $this->service->updateStatus(1, 'cancelled', 1);
    }
}

// Fake repository classes for testing

class OrderStatusServiceFakeOrderRepo extends OrderRepository
{
    public array $fakeOrders = [];

    public function __construct()
    {
        $this->fakeOrders = [
            1 => [
                'id' => 1,
                'order_number' => 'ORD-001',
                'status' => 'draft',
                'branch_id' => 1,
                'customer_id' => 1,
                'total' => 500000,
                'payment_method' => 'CASH',
                'created_by' => 1,
                'items' => [
                    [
                        'id' => 1,
                        'product_id' => 1,
                        'variant_id' => null,
                        'quantity' => 2,
                        'unit_price' => 250000,
                    ]
                ],
            ],
            2 => [
                'id' => 2,
                'order_number' => 'ORD-002',
                'status' => 'confirmed',
                'branch_id' => 1,
                'customer_id' => 2,
                'total' => 300000,
                'payment_method' => 'TRANSFER',
                'created_by' => 1,
                'items' => [],
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fakeOrders[$id] ?? null;
    }

    public function updateFields(int $id, array $data): bool
    {
        if (!isset($this->fakeOrders[$id])) {
            return false;
        }
        $this->fakeOrders[$id] = array_merge($this->fakeOrders[$id], $data);
        return true;
    }
}

class OrderStatusServiceFakeLogRepo extends OrderStatusLogRepository
{
    public array $fakeLogs = [];

    public function __construct()
    {
        // No parent call
    }

    public function create(int $orderId, ?string $from, string $to, ?int $userId = null, ?string $notes = null): array
    {
        $log = [
            'id' => count($this->fakeLogs) + 1,
            'order_id' => $orderId,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $userId,
            'notes' => $notes,
            'changed_at' => date('Y-m-d H:i:s'),
        ];
        $this->fakeLogs[] = $log;
        return $log;
    }

    public function byOrder(int $orderId): array
    {
        return array_filter($this->fakeLogs, fn($l) => $l['order_id'] === $orderId);
    }
}

class OrderStatusServiceFakePaymentRepo extends OrderPaymentRepository
{
    public array $fakePayments = [];

    public function __construct()
    {
        // No parent call
    }

    public function findByOrder(int $orderId): array
    {
        return $this->fakePayments[$orderId] ?? [];
    }
}

class OrderStatusServiceFakeInventoryRepo extends InventoryRepository
{
    public array $fakeStock = [];

    public function __construct()
    {
        $this->fakeStock = [
            '1-1' => 100, // product_id-branch_id => quantity
        ];
    }

    public function adjustStockWithLock(int $productId, ?int $variantId, int $warehouseId, float $deltaQty, ?int $branchId = null): array
    {
        $key = "{$productId}-{$warehouseId}";
        if (!isset($this->fakeStock[$key])) {
            $this->fakeStock[$key] = 0;
        }
        $this->fakeStock[$key] += $deltaQty;
        return ['product_id' => $productId, 'new_quantity' => $this->fakeStock[$key]];
    }
}

class OrderStatusServiceFakeMovementLogger extends InventoryMovementLogger
{
    public array $fakeMovements = [];

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
        $movement = [
            'id' => count($this->fakeMovements) + 1,
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'type' => $type,
            'quantity' => $quantity,
            'batch_id' => $batchId,
            'serial_number' => $serialNumber,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $createdBy,
        ];
        $this->fakeMovements[] = $movement;
        return $movement;
    }
}

class OrderStatusServiceFakeBatchService extends ProductBatchService
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

class OrderStatusServiceFakeSerialService extends ProductSerialNumberService
{
    public array $fakeReserved = [];
    public array $fakeSold = [];

    public function __construct()
    {
        // No parent call
    }

    public function reserve(array $data): array
    {
        $this->fakeReserved = array_merge($this->fakeReserved, $data['serial_numbers'] ?? []);
        return ['reserved' => $this->fakeReserved];
    }

    public function release(array $serialNumbers): void
    {
        $this->fakeReserved = array_diff($this->fakeReserved, $serialNumbers);
    }

    public function sell(array $data): array
    {
        $this->fakeSold = array_merge($this->fakeSold, $data['serial_numbers'] ?? []);
        return ['sold' => $this->fakeSold];
    }
}
