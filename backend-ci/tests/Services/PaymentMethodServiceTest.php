<?php

namespace Tests\Services;

use App\Services\PaymentMethods\PaymentMethodService;
use App\Repositories\PaymentMethods\PaymentMethodRepository;
use App\Validators\PaymentMethodValidator;
use App\Transformers\PaymentMethodTransformer;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class PaymentMethodServiceTest extends CIUnitTestCase
{
    private PaymentMethodService $service;
    private PaymentMethodFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new PaymentMethodFakeRepo();
        $validator = new class extends PaymentMethodValidator {
            public function validateListFilters(array $filters): array
            {
                return array_merge(['page' => 1, 'limit' => 20], $filters);
            }
            public function validateCreate(array $data): array { return $data; }
            public function validateUpdate(array $data): array { return $data; }
        };
        $transformer = new PaymentMethodTransformer();
        $cache = new class implements \CodeIgniter\Cache\CacheInterface {
            private array $data = [];
            public function get(string $key) { return $this->data[$key] ?? null; }
            public function save(string $key, $value, int $ttl = 60) { $this->data[$key] = $value; return true; }
            public function delete(string $key) { unset($this->data[$key]); return true; }
            public function increment(string $key, int $offset = 1) { return 0; }
            public function decrement(string $key, int $offset = 1) { return 0; }
            public function clean() { $this->data = []; return true; }
            public function getCacheInfo() { return []; }
            public function getMetaData(string $key) { return null; }
            public function isSupported(): bool { return true; }
            public function initialize() {}
            public function deleteMatching(string $pattern) { return 0; }
            public function remember(string $key, int $ttl, \Closure $callback) { return $callback(); }
        };
        $this->service = new PaymentMethodService($this->repo, $validator, $transformer, $cache);
    }

    public function testListReturnsSuccessWithData(): void
    {
        $result = $this->service->list([]);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testListWithFiltersPagination(): void
    {
        $result = $this->service->list(['page' => 2, 'limit' => 10]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['pagination']['page']);
        $this->assertEquals(10, $result['pagination']['limit']);
    }

    public function testGetReturnsPaymentMethod(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment method not found');
        
        $this->service->get(999);
    }

    public function testCreateSuccess(): void
    {
        $data = ['code' => 'NEW001', 'name' => 'New Method', 'is_active' => true];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCreateThrowsOnDuplicateCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method code already exists');
        
        $this->service->create(['code' => 'CASH', 'name' => 'Duplicate']);
    }

    public function testUpdateSuccess(): void
    {
        $result = $this->service->update(1, ['name' => 'Updated Name']);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->update(999, ['name' => 'Test']);
    }

    public function testUpdateThrowsOnDuplicateCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method code already exists');
        
        $this->service->update(1, ['code' => 'TRANSFER']);
    }

    public function testDeleteSuccess(): void
    {
        $result = $this->service->delete(2);
        
        $this->assertTrue($result['success']);
    }

    public function testDeleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->delete(999);
    }

    public function testDeleteThrowsWhenUsedInOrders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete payment method that is used in orders');
        
        $this->service->delete(1);
    }

    public function testActivateMethod(): void
    {
        $result = $this->service->activate(2);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testDeactivateMethod(): void
    {
        $result = $this->service->deactivate(1);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testActivateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->activate(999);
    }

    public function testDeactivateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->deactivate(999);
    }
}

class PaymentMethodFakeRepo extends PaymentMethodRepository
{
    private array $methods = [
        1 => ['id' => 1, 'code' => 'CASH', 'name' => 'Cash', 'is_active' => true],
        2 => ['id' => 2, 'code' => 'TRANSFER', 'name' => 'Bank Transfer', 'is_active' => false],
    ];

    public function __construct() {}

    public function findAll(array $filters = []): array
    {
        return array_values($this->methods);
    }

    public function count(array $filters = []): int
    {
        return count($this->methods);
    }

    public function findById(int $id): ?array
    {
        return $this->methods[$id] ?? null;
    }

    public function activeOrdered(): array
    {
        return array_filter($this->methods, fn($m) => $m['is_active']);
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        foreach ($this->methods as $id => $method) {
            if ($method['code'] === $code && $id !== $excludeId) {
                return true;
            }
        }
        return false;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->methods)) + 1;
        $this->methods[$id] = array_merge($data, ['id' => $id]);
        return $this->methods[$id];
    }

    public function update(int $id, array $data): bool
    {
        if (isset($this->methods[$id])) {
            $this->methods[$id] = array_merge($this->methods[$id], $data);
            return true;
        }
        return false;
    }

    public function delete(int $id): bool
    {
        unset($this->methods[$id]);
        return true;
    }

    public function usedInOrders(string $code): bool
    {
        return $code === 'CASH';
    }
}
