<?php

namespace Tests\Services;

use App\Services\Shipping\ShipmentService;
use App\Repositories\Shipping\ShipmentRepository;
use App\Transformers\ShipmentTransformer;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class ShipmentServiceTest extends CIUnitTestCase
{
    private ShipmentService $service;
    private ShipmentFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ShipmentFakeRepo();
        $transformer = new ShipmentTransformer();
        $this->service = new ShipmentService($this->repo, $transformer);
    }

    public function testListReturnsDataWithPagination(): void
    {
        $result = $this->service->list([]);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    public function testListPaginationDefaults(): void
    {
        $result = $this->service->list([]);
        
        $this->assertEquals(1, $result['pagination']['page']);
        $this->assertEquals(15, $result['pagination']['limit']);
    }

    public function testListWithCustomPagination(): void
    {
        $result = $this->service->list(['page' => 2, 'limit' => 10]);
        
        $this->assertEquals(2, $result['pagination']['page']);
        $this->assertEquals(10, $result['pagination']['limit']);
    }

    public function testListLimitCappedAt100(): void
    {
        $result = $this->service->list(['limit' => 200]);
        
        $this->assertEquals(100, $result['pagination']['limit']);
    }

    public function testListLimitMinimum1(): void
    {
        $result = $this->service->list(['limit' => 0]);
        
        $this->assertEquals(1, $result['pagination']['limit']);
    }

    public function testListPageMinimum1(): void
    {
        $result = $this->service->list(['page' => 0]);
        
        $this->assertEquals(1, $result['pagination']['page']);
    }

    public function testListCalculatesTotalPages(): void
    {
        $result = $this->service->list(['limit' => 1]);
        
        $this->assertEquals(2, $result['pagination']['total_pages']);
    }

    public function testListTotalPagesAtLeast1(): void
    {
        $this->repo->setEmpty();
        $result = $this->service->list([]);
        
        $this->assertEquals(1, $result['pagination']['total_pages']);
    }

    public function testGetReturnsShipment(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy vận đơn');
        
        $this->service->get(999);
    }

    public function testListWithFilters(): void
    {
        $result = $this->service->list(['status' => 'pending']);
        
        $this->assertTrue($result['success']);
    }

    public function testListReturnsSummary(): void
    {
        $result = $this->service->list([]);
        
        $this->assertArrayHasKey('total_shipments', $result['summary']);
        $this->assertArrayHasKey('total_value', $result['summary']);
    }
}

class ShipmentFakeRepo extends ShipmentRepository
{
    private array $shipments = [
        1 => ['id' => 1, 'invoice_id' => 101, 'status' => 'pending', 'tracking_code' => 'TRACK001'],
        2 => ['id' => 2, 'invoice_id' => 102, 'status' => 'shipped', 'tracking_code' => 'TRACK002'],
    ];
    private bool $isEmpty = false;

    public function __construct() {}

    public function setEmpty(): void
    {
        $this->isEmpty = true;
    }

    public function findAll(array $filters = []): array
    {
        return $this->isEmpty ? [] : array_values($this->shipments);
    }

    public function count(array $filters = []): int
    {
        return $this->isEmpty ? 0 : count($this->shipments);
    }

    public function summary(array $filters = []): array
    {
        return [
            'total_shipments' => $this->isEmpty ? 0 : 2,
            'total_value' => $this->isEmpty ? 0 : 1000000,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->shipments[$id] ?? null;
    }
}
