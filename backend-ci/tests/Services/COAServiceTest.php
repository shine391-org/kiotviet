<?php

namespace Tests\Services;

use App\Services\Accounting\COAService;
use App\Repositories\Accounting\COARepository;
use App\Validators\COAValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class COAServiceTest extends CIUnitTestCase
{
    private COAService $service;
    private COAFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new COAFakeRepo();
        $validator = new class extends COAValidator {
            public function validateCreate(array $data): array
            {
                return array_merge([
                    'code' => $data['code'] ?? 'ACC001',
                    'name' => $data['name'] ?? 'Test Account',
                    'parent_id' => $data['parent_id'] ?? null,
                    'is_group' => $data['is_group'] ?? false,
                ], $data);
            }
        };
        $this->service = new COAService($this->repo, $validator);
    }

    public function testCreateSuccess(): void
    {
        $data = ['code' => 'ACC999', 'name' => 'New Account'];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCreateThrowsOnDuplicateCode(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Account code already exists');
        
        $this->service->create(['code' => 'ACC001', 'name' => 'Duplicate']);
    }

    public function testCreateWithParentSuccess(): void
    {
        $data = ['code' => 'ACC999', 'name' => 'Child Account', 'parent_id' => 1];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
    }

    public function testCreateThrowsWhenParentNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parent account not found');
        
        $this->service->create(['code' => 'ACC999', 'name' => 'Child', 'parent_id' => 999]);
    }

    public function testCreateThrowsWhenParentNotGroup(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parent account must be a group');
        
        $this->service->create(['code' => 'ACC999', 'name' => 'Child', 'parent_id' => 2]);
    }

    public function testGetReturnsAccount(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('ACC001', $result['data']['code']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Account not found');
        
        $this->service->get(999);
    }

    public function testListChildrenReturnsData(): void
    {
        $result = $this->service->listChildren(null);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }

    public function testListChildrenWithParent(): void
    {
        $result = $this->service->listChildren(1);
        
        $this->assertTrue($result['success']);
    }
}

class COAFakeRepo extends COARepository
{
    private array $accounts = [
        1 => ['id' => 1, 'code' => 'ACC001', 'name' => 'Assets', 'parent_id' => null, 'is_group' => true],
        2 => ['id' => 2, 'code' => 'ACC002', 'name' => 'Cash', 'parent_id' => 1, 'is_group' => false],
    ];

    public function __construct() {}

    public function findById(int $id): ?array
    {
        return $this->accounts[$id] ?? null;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        foreach ($this->accounts as $id => $acc) {
            if ($acc['code'] === $code && $id !== $excludeId) {
                return true;
            }
        }
        return false;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->accounts)) + 1;
        $this->accounts[$id] = array_merge($data, ['id' => $id]);
        return $this->accounts[$id];
    }

    public function listChildren(?int $parentId = null): array
    {
        return array_values(array_filter($this->accounts, fn($a) => $a['parent_id'] === $parentId));
    }
}
