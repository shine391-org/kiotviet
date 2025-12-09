<?php

namespace Tests\Services;

use App\Services\DeliveryPartners\DeliveryPartnerService;
use App\Repositories\DeliveryPartners\DeliveryPartnerRepository;
use App\Validators\DeliveryPartnerValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class DeliveryPartnerServiceTest extends CIUnitTestCase
{
    private DeliveryPartnerService $service;
    private DeliveryPartnerFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new DeliveryPartnerFakeRepo();
        $validator = new class extends DeliveryPartnerValidator {
            public function validateListFilters(array $filters): array
            {
                return array_merge(['page' => 1, 'limit' => 20], $filters);
            }
            public function validateCreate(array $data): array { return $data; }
            public function validateUpdate(array $data): array { return $data; }
        };
        $this->service = new DeliveryPartnerService($this->repo, $validator);
    }

    public function testListReturnsDataWithPagination(): void
    {
        $result = $this->service->list([]);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    public function testListPaginationCorrect(): void
    {
        $result = $this->service->list(['page' => 2, 'limit' => 10]);
        
        $this->assertEquals(2, $result['pagination']['page']);
        $this->assertEquals(10, $result['pagination']['limit']);
    }

    public function testGetReturnsPartner(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('GHN', $result['data']['code']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Delivery partner not found');
        
        $this->service->get(999);
    }

    public function testCreateSuccess(): void
    {
        $data = ['code' => 'NEW', 'name' => 'New Partner'];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Delivery partner created successfully', $result['message']);
    }

    public function testCreateThrowsOnDuplicateCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner code already exists');
        
        $this->service->create(['code' => 'GHN', 'name' => 'Duplicate']);
    }

    public function testUpdateSuccess(): void
    {
        $result = $this->service->update(1, ['name' => 'Updated Name']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Delivery partner updated successfully', $result['message']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->update(999, ['name' => 'Test']);
    }

    public function testUpdateWithCodeChangeSuccess(): void
    {
        $result = $this->service->update(1, ['code' => 'NEW_CODE', 'name' => 'Test']);
        
        $this->assertTrue($result['success']);
    }

    public function testUpdateThrowsOnDuplicateCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner code already exists');
        
        $this->service->update(1, ['code' => 'GHTK']);
    }

    public function testDeleteSuccess(): void
    {
        $result = $this->service->delete(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Delivery partner deleted successfully', $result['message']);
    }

    public function testDeleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->delete(999);
    }

    public function testListWithFilters(): void
    {
        $result = $this->service->list(['is_active' => true]);
        
        $this->assertTrue($result['success']);
    }
}

class DeliveryPartnerFakeRepo extends DeliveryPartnerRepository
{
    private array $partners = [
        1 => ['id' => 1, 'code' => 'GHN', 'name' => 'Giao Hang Nhanh', 'is_active' => 1],
        2 => ['id' => 2, 'code' => 'GHTK', 'name' => 'Giao Hang Tiet Kiem', 'is_active' => 1],
    ];

    public function __construct() {}

    public function findAll(array $filters = []): array
    {
        return array_values($this->partners);
    }

    public function count(array $filters = []): int
    {
        return count($this->partners);
    }

    public function summary(array $filters = []): array
    {
        return ['total_active' => 2, 'total_inactive' => 0];
    }

    public function findById(int $id): ?array
    {
        return $this->partners[$id] ?? null;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        foreach ($this->partners as $id => $partner) {
            if ($partner['code'] === $code && $id !== $excludeId) {
                return true;
            }
        }
        return false;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->partners)) + 1;
        $this->partners[$id] = array_merge($data, ['id' => $id]);
        return $this->partners[$id];
    }

    public function update(int $id, array $data): array
    {
        if (isset($this->partners[$id])) {
            $this->partners[$id] = array_merge($this->partners[$id], $data);
        }
        return $this->partners[$id] ?? [];
    }

    public function delete(int $id): void
    {
        unset($this->partners[$id]);
    }
}
