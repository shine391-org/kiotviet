<?php

namespace Tests\Services;

use App\Services\PurchaseOrders\PurchaseOrderService;
use App\Repositories\PurchaseOrders\PurchaseOrderRepository;
use App\Validators\PurchaseOrderValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class PurchaseOrderServiceTest extends CIUnitTestCase
{
    private PurchaseOrderService $service;
    private PurchaseOrderFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new PurchaseOrderFakeRepo();
        $validator = new class extends PurchaseOrderValidator {
            public function validateCreate(array $data): array
            {
                return array_merge([
                    'branch_id' => 1,
                    'supplier_id' => 1,
                    'payment_method' => 'cash',
                    'order_date' => date('Y-m-d'),
                    'notes' => null,
                    'items' => [],
                ], $data);
            }
        };
        $this->service = new PurchaseOrderService($this->repo, $validator);
    }



    public function testListReturnsOrders(): void
    {
        $result = $this->service->list([]);
        
        $this->assertIsArray($result);
    }

    public function testListWithFilters(): void
    {
        $result = $this->service->list(['status' => 'draft']);
        
        $this->assertIsArray($result);
    }

    public function testGetReturnsOrder(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Purchase order not found');
        
        $this->service->get(999);
    }

    public function testSubmitSuccess(): void
    {
        $result = $this->service->submit(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('submitted', $result['data']['status']);
    }

    public function testSubmitThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Purchase order not found');
        
        $this->service->submit(999);
    }

    public function testSubmitThrowsWhenNotDraft(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only draft orders can be submitted');
        
        $this->service->submit(2);
    }

    public function testCancelSuccess(): void
    {
        $result = $this->service->cancel(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('cancelled', $result['data']['status']);
    }

    public function testCancelThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Purchase order not found');
        
        $this->service->cancel(999);
    }

}

class PurchaseOrderFakeRepo extends PurchaseOrderRepository
{
    private array $orderData = [
        1 => ['id' => 1, 'order_number' => 'PO-001', 'status' => 'draft', 'total' => 100000],
        2 => ['id' => 2, 'order_number' => 'PO-002', 'status' => 'submitted', 'total' => 200000],
    ];
    private int $nextNum = 3;

    public function __construct() {}

    public function list(array $filters = []): array
    {
        return array_values($this->orderData);
    }

    public function findById(int $id): ?array
    {
        return $this->orderData[$id] ?? null;
    }

    public function nextNumber(): string
    {
        return 'PO-' . str_pad($this->nextNum++, 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items = []): array
    {
        $id = max(array_keys($this->orderData)) + 1;
        $this->orderData[$id] = array_merge($data, ['id' => $id]);
        return $this->orderData[$id];
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (isset($this->orderData[$id])) {
            $this->orderData[$id]['status'] = $status;
            return true;
        }
        return false;
    }
}
