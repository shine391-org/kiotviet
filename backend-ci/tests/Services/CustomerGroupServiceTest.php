<?php

namespace Tests\Services;

use App\Services\CustomerGroups\CustomerGroupService;
use App\Repositories\CustomerGroups\CustomerGroupRepository;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class CustomerGroupServiceTest extends CIUnitTestCase
{
    private CustomerGroupService $service;
    private CustomerGroupFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new CustomerGroupFakeRepo();
        $this->service = new CustomerGroupService($this->repo);
    }

    public function testListReturnsGroupsWithMeta(): void
    {
        $result = $this->service->list();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('meta', $result);
        $this->assertEquals(2, $result['meta']['total']);
    }

    public function testListWithPagination(): void
    {
        $result = $this->service->list(['page' => 2, 'limit' => 10]);
        
        $this->assertEquals(2, $result['meta']['page']);
        $this->assertEquals(10, $result['meta']['limit']);
    }

    public function testGetReturnsGroup(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('VIP', $result['data']['name']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Customer group not found');
        
        $this->service->get(999);
    }

    public function testCreateSuccess(): void
    {
        $result = $this->service->create(['name' => 'Gold']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Gold', $result['data']['name']);
    }

    public function testCreateThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        
        $this->service->create(['name' => '']);
    }

    public function testCreateThrowsWhenNameMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->service->create(['description' => 'Test']);
    }

    public function testUpdateSuccess(): void
    {
        $result = $this->service->update(1, ['name' => 'VIP Gold']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('VIP Gold', $result['data']['name']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->update(999, ['name' => 'Test']);
    }

    public function testUpdateThrowsWhenNoFieldsProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No fields to update');
        
        $this->service->update(1, []);
    }

    public function testUpdateWithDiscountPercent(): void
    {
        $result = $this->service->update(1, ['discount_percent' => 15]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(15, $result['data']['discount_percent']);
    }

    public function testUpdateWithIsActive(): void
    {
        $result = $this->service->update(1, ['is_active' => false]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data']['is_active']);
    }

    public function testDeleteSuccess(): void
    {
        $result = $this->service->delete(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Customer group deleted', $result['message']);
    }

    public function testDeleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->delete(999);
    }
}

class CustomerGroupFakeRepo extends CustomerGroupRepository
{
    private array $groups = [
        1 => ['id' => 1, 'name' => 'VIP', 'description' => 'VIP customers', 'discount_percent' => 10, 'is_active' => 1],
        2 => ['id' => 2, 'name' => 'Regular', 'description' => 'Regular customers', 'discount_percent' => 0, 'is_active' => 1],
    ];

    public function __construct() {}

    public function findAll(array $filters = []): array
    {
        return array_values($this->groups);
    }

    public function count(array $filters = []): int
    {
        return count($this->groups);
    }

    public function findById(int $id): ?array
    {
        return $this->groups[$id] ?? null;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->groups)) + 1;
        $this->groups[$id] = array_merge($data, ['id' => $id]);
        return $this->groups[$id];
    }

    public function update(int $id, array $data): bool
    {
        if (isset($this->groups[$id])) {
            $this->groups[$id] = array_merge($this->groups[$id], $data);
            return true;
        }
        return false;
    }

    public function delete(int $id): bool
    {
        unset($this->groups[$id]);
        return true;
    }
}
