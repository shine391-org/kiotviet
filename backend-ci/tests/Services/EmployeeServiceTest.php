<?php

namespace Tests\Services;

use App\Services\HR\EmployeeService;
use App\Repositories\HR\EmployeeRepository;
use App\Validators\EmployeeValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class EmployeeServiceTest extends CIUnitTestCase
{
    private EmployeeService $service;
    private EmployeeFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EmployeeFakeRepo();
        $validator = new class extends EmployeeValidator {
            public function validateCreate(array $data): array { return $data; }
            public function validateUpdate(array $data): array { return $data; }
        };
        $this->service = new EmployeeService($this->repo, $validator);
    }

    public function testListReturnsEmployees(): void
    {
        $result = $this->service->list([]);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }

    public function testListWithFilters(): void
    {
        $result = $this->service->list(['status' => 'active']);
        
        $this->assertTrue($result['success']);
    }

    public function testShowReturnsEmployee(): void
    {
        $result = $this->service->show(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Employee not found');
        
        $this->service->show(999);
    }

    public function testCreateSuccess(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('employee_code', $result['data']);
    }

    public function testUpdateSuccess(): void
    {
        $result = $this->service->update(1, ['name' => 'Updated Name']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Updated Name', $result['data']['name']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Employee not found');
        
        $this->service->update(999, ['name' => 'Test']);
    }
}

class EmployeeFakeRepo extends EmployeeRepository
{
    private array $employeeData = [
        1 => ['id' => 1, 'employee_code' => 'EMP001', 'name' => 'John', 'status' => 'active'],
        2 => ['id' => 2, 'employee_code' => 'EMP002', 'name' => 'Jane', 'status' => 'active'],
    ];
    private int $nextNum = 3;

    public function __construct() {}

    public function list(array $filters = []): array
    {
        return array_values($this->employeeData);
    }

    public function find(int $id): ?array
    {
        return $this->employeeData[$id] ?? null;
    }

    public function nextCode(): string
    {
        return 'EMP' . str_pad($this->nextNum++, 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->employeeData)) + 1;
        $this->employeeData[$id] = array_merge($data, ['id' => $id]);
        return $this->employeeData[$id];
    }

    public function update(int $id, array $data): bool
    {
        if (isset($this->employeeData[$id])) {
            $this->employeeData[$id] = array_merge($this->employeeData[$id], $data);
            return true;
        }
        return false;
    }
}
