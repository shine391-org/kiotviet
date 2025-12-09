<?php

namespace Tests\Services;

use App\Services\Assets\AssetService;
use App\Repositories\Assets\AssetRepository;
use App\Validators\AssetValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class AssetServiceTest extends CIUnitTestCase
{
    private AssetService $service;
    private AssetFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AssetFakeRepo();
        $validator = new class extends AssetValidator {
            public function validateCreate(array $data): array { return $data; }
            public function validateStatus(array $data): array { return $data; }
        };
        $this->service = new AssetService($this->repo, $validator);
    }

    public function testListReturnsAssets(): void
    {
        $result = $this->service->list([]);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }

    public function testShowReturnsAsset(): void
    {
        $result = $this->service->show(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset not found');
        
        $this->service->show(999);
    }

    public function testCreateSuccess(): void
    {
        $data = ['name' => 'Computer', 'purchase_price' => 20000000];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCapitalizeChangesStatusToActive(): void
    {
        $result = $this->service->capitalize(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['data']['status']);
    }

    public function testCapitalizeAlreadyActiveReturnsAsset(): void
    {
        $result = $this->service->capitalize(2);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['data']['status']);
    }

    public function testCapitalizeThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->capitalize(999);
    }

    public function testDisposeChangesStatusToDisposed(): void
    {
        $result = $this->service->dispose(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('disposed', $result['data']['status']);
    }

    public function testDisposeThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->dispose(999);
    }

    public function testChangeStatusSuccess(): void
    {
        $result = $this->service->changeStatus(1, ['status' => 'maintenance']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('maintenance', $result['data']['status']);
    }

    public function testChangeStatusThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->changeStatus(999, ['status' => 'active']);
    }
}

class AssetFakeRepo extends AssetRepository
{
    private array $assetData = [
        1 => ['id' => 1, 'asset_number' => 'AST001', 'name' => 'Laptop', 'status' => 'draft'],
        2 => ['id' => 2, 'asset_number' => 'AST002', 'name' => 'Printer', 'status' => 'active'],
    ];
    private int $nextNum = 3;

    public function __construct() {}

    public function list(array $filters = []): array
    {
        return array_values($this->assetData);
    }

    public function find(int $id): ?array
    {
        return $this->assetData[$id] ?? null;
    }

    public function nextNumber(): string
    {
        return 'AST' . str_pad($this->nextNum++, 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->assetData)) + 1;
        $this->assetData[$id] = array_merge($data, ['id' => $id]);
        return $this->assetData[$id];
    }

    public function update(int $id, array $data): bool
    {
        if (isset($this->assetData[$id])) {
            $this->assetData[$id] = array_merge($this->assetData[$id], $data);
            return true;
        }
        return false;
    }
}
