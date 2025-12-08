<?php

namespace Tests\Services;

use App\Services\Customers\CustomerService;
use App\Repositories\Customers\CustomerRepository;
use App\Transformers\CustomerTransformer;
use App\Validators\CustomerValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-test: CustomerService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class CustomerServiceTest extends CIUnitTestCase
{
    private CustomerService $service;
    private CustomerServiceFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new CustomerServiceFakeRepo();
        $validator = new CustomerValidator();
        $transformer = new CustomerTransformer();
        $this->service = new CustomerService($this->repo, $validator, $transformer);
    }

    public function testListReturnsCustomersWithPagination(): void
    {
        $result = $this->service->list(['page' => 1, 'limit' => 10]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
        $this->assertSame(1, $result['pagination']['page']);
        $this->assertSame(10, $result['pagination']['limit']);
        $this->assertSame(2, $result['pagination']['total']);
    }

    public function testListWithSearchFilter(): void
    {
        $result = $this->service->list(['search' => 'Nguyen']);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']);
    }

    public function testListWithStatusFilter(): void
    {
        $result = $this->service->list(['status' => 'ACTIVE']);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']);
    }

    public function testGetReturnsCustomer(): void
    {
        $result = $this->service->get(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
        $this->assertSame('Nguyen Van A', $result['data']['name']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Customer not found');
        $this->service->get(999);
    }

    public function testCreateReturnsNewCustomer(): void
    {
        $data = [
            'name' => 'New Customer',
            'customer_type' => 'INDIVIDUAL',
            'phone' => '0901234567',
            'email' => 'new@example.com',
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('New Customer', $result['data']['name']);
    }

    public function testCreateValidatesRequiredName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer name is required');
        $this->service->create(['name' => '']);
    }

    public function testCreateValidatesCustomerType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('customer_type must be one of');
        $this->service->create(['name' => 'Test', 'customer_type' => 'INVALID']);
    }

    public function testCreateValidatesEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email is not a valid email');
        $this->service->create(['name' => 'Test', 'email' => 'invalid-email']);
    }

    public function testCreateValidatesPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('phone must be 6-20 digits');
        $this->service->create(['name' => 'Test', 'phone' => '123']);
    }

    public function testCreateValidatesTaxCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tax_code must be 10-14 digits');
        $this->service->create(['name' => 'Test', 'tax_code' => '123']);
    }

    public function testCreateValidatesGender(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('gender must be one of');
        $this->service->create(['name' => 'Test', 'gender' => 'INVALID']);
    }

    public function testCreateRejectsDuplicateTaxCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax code already exists');
        $this->service->create([
            'name' => 'Test',
            'tax_code' => '1234567890', // This will trigger duplicate check
        ]);
    }

    public function testUpdateReturnsUpdatedCustomer(): void
    {
        $result = $this->service->update(1, ['name' => 'Updated Name']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Customer not found');
        $this->service->update(999, ['name' => 'Test']);
    }

    public function testUpdateValidatesEmptyPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No fields to update');
        $this->service->update(1, []);
    }

    public function testDeleteReturnsSuccess(): void
    {
        $result = $this->service->delete(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('thành công', $result['message']);
    }

    public function testDeleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Customer not found');
        $this->service->delete(999);
    }

    public function testExportReturnsCustomers(): void
    {
        $result = $this->service->export([]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testImportReturnsStats(): void
    {
        $rows = [
            ['name' => 'Import 1', 'customer_type' => 'INDIVIDUAL'],
            ['name' => 'Import 2', 'customer_type' => 'COMPANY'],
            ['name' => '', 'customer_type' => 'INDIVIDUAL'], // Invalid - no name
        ];

        $result = $this->service->import($rows);

        $this->assertArrayHasKey('imported', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertSame(2, $result['imported']);
        $this->assertSame(1, $result['errors']);
    }
}

class CustomerServiceFakeRepo extends CustomerRepository
{
    private array $customers = [];

    public function __construct()
    {
        $this->customers = [
            1 => [
                'id' => 1,
                'name' => 'Nguyen Van A',
                'customer_type' => 'INDIVIDUAL',
                'code' => 'KH001',
                'phone' => '0901234567',
                'email' => 'a@example.com',
                'status' => 'ACTIVE',
                'organization_id' => 1,
                'created_at' => '2024-01-01 00:00:00',
            ],
            2 => [
                'id' => 2,
                'name' => 'Company ABC',
                'customer_type' => 'COMPANY',
                'code' => 'KH002',
                'phone' => '0909876543',
                'email' => 'abc@company.com',
                'tax_code' => '1234567890',
                'status' => 'ACTIVE',
                'organization_id' => 1,
                'created_at' => '2024-01-02 00:00:00',
            ],
        ];
    }

    public function findAll(array $filters = []): array
    {
        return array_values($this->customers);
    }

    public function count(array $filters = []): int
    {
        return count($this->customers);
    }

    public function findById(int $id): ?array
    {
        return $this->customers[$id] ?? null;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->customers)) + 1;
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->customers[$id] = $data;
        return $data;
    }

    public function update(int $id, array $data): bool
    {
        if (! isset($this->customers[$id])) {
            return false;
        }
        $this->customers[$id] = array_merge($this->customers[$id], $data);
        return true;
    }

    public function softDelete(int $id): bool
    {
        if (! isset($this->customers[$id])) {
            return false;
        }
        $this->customers[$id]['deleted_at'] = date('Y-m-d H:i:s');
        return true;
    }

    public function taxCodeExists(string $taxCode, ?int $excludeId = null, ?int $organizationId = null): bool
    {
        foreach ($this->customers as $c) {
            if (($c['tax_code'] ?? '') === $taxCode && $c['id'] !== $excludeId) {
                return true;
            }
        }
        return false;
    }
}
