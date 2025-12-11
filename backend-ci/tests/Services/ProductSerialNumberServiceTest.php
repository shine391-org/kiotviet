<?php

namespace Tests\Services;

use App\Repositories\Products\ProductBatchRepository;
use App\Repositories\Products\ProductSerialNumberRepository;
use App\Services\Products\ProductSerialNumberService;
use App\Validators\ProductSerialValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-test: ProductSerialNumberService
 * @agent-pattern: Service orchestration with fakes
 */
class ProductSerialNumberServiceTest extends CIUnitTestCase
{
    private ProductSerialNumberService $service;
    private FakeProductSerialNumberRepo $repo;
    private FakeProductBatchRepo $batchRepo;
    private FakeProductSerialValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new FakeProductSerialNumberRepo();
        $this->batchRepo = new FakeProductBatchRepo();
        $this->validator = new FakeProductSerialValidator();
        $this->service = new ProductSerialNumberService($this->repo, $this->validator, $this->batchRepo);
    }

    public function testListReturnsSerialNumbers(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'available'],
            ['id' => 2, 'serial_number' => 'SN002', 'product_id' => 1, 'status' => 'sold'],
        ];

        $result = $this->service->list(['product_id' => 1]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
    }

    public function testCreateSuccessWithoutBatch(): void
    {
        $data = [
            'product_id' => 1,
            'serial_number' => 'SN-NEW-001',
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('SN-NEW-001', $result['data']['serial_number']);
    }

    public function testCreateWithValidBatch(): void
    {
        $this->batchRepo->batchStorage = [
            10 => ['id' => 10, 'product_id' => 1, 'variant_id' => 5, 'batch_number' => 'BATCH001'],
        ];

        $data = [
            'product_id' => 1,
            'serial_number' => 'SN-BATCH-001',
            'batch_id' => 10,
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['data']['variant_id']);
    }

    public function testCreateThrowsWhenBatchNotFound(): void
    {
        $data = [
            'product_id' => 1,
            'serial_number' => 'SN-001',
            'batch_id' => 999,
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Batch not found for serial number');

        $this->service->create($data);
    }

    public function testCreateThrowsWhenBatchBelongsToDifferentProduct(): void
    {
        $this->batchRepo->batchStorage = [
            10 => ['id' => 10, 'product_id' => 2, 'batch_number' => 'BATCH001'],
        ];

        $data = [
            'product_id' => 1,
            'serial_number' => 'SN-001',
            'batch_id' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch does not belong to the product');

        $this->service->create($data);
    }

    public function testCreateThrowsWhenSerialExists(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN-EXISTS', 'product_id' => 1],
        ];

        $data = [
            'product_id' => 1,
            'serial_number' => 'SN-EXISTS',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Serial number already exists');

        $this->service->create($data);
    }

    public function testReserveSuccess(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'available'],
            ['id' => 2, 'serial_number' => 'SN002', 'product_id' => 1, 'status' => 'available'],
        ];

        $result = $this->service->reserve([
            'order_id' => 100,
            'serial_numbers' => ['SN001', 'SN002'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
    }

    public function testSellSuccess(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'available'],
        ];

        $result = $this->service->sell([
            'order_id' => 100,
            'serial_numbers' => ['SN001'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
    }

    public function testMarkReturnedSuccess(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'sold'],
        ];

        $result = $this->service->markReturned([
            'order_id' => 100,
            'serial_numbers' => ['SN001'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
    }

    public function testReleaseWithEmptyArray(): void
    {
        $this->service->release([]);
        $this->assertTrue(true); // No exception thrown
    }

    public function testReleaseSuccess(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'reserved'],
        ];

        $this->service->release(['SN001']);
        $this->assertTrue(true); // No exception thrown
    }

    public function testEnsureAvailableForOrderThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Serial number not found');

        $this->service->ensureAvailableForOrder('NONEXISTENT');
    }

    public function testEnsureAvailableForOrderThrowsWhenSold(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'sold'],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Serial already sold');

        $this->service->ensureAvailableForOrder('SN001');
    }

    public function testEnsureAvailableForOrderThrowsWhenReservedByAnotherOrder(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'reserved', 'reserved_for_order_id' => 50],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Serial reserved by another order');

        $this->service->ensureAvailableForOrder('SN001', 100);
    }

    public function testEnsureAvailableForOrderSuccessWhenReservedBySameOrder(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'reserved', 'reserved_for_order_id' => 100],
        ];

        $result = $this->service->ensureAvailableForOrder('SN001', 100);

        $this->assertIsArray($result);
        $this->assertEquals('SN001', $result['serial_number']);
    }

    public function testEnsureAvailableForOrderSuccessWhenAvailable(): void
    {
        $this->repo->serialStorage = [
            ['id' => 1, 'serial_number' => 'SN001', 'product_id' => 1, 'status' => 'available'],
        ];

        $result = $this->service->ensureAvailableForOrder('SN001');

        $this->assertIsArray($result);
        $this->assertEquals('SN001', $result['serial_number']);
    }
}

class FakeProductSerialNumberRepo extends ProductSerialNumberRepository
{
    public array $serialStorage = [];
    private FakeDbConnection $fakeDb;

    public function __construct()
    {
        $this->fakeDb = new FakeDbConnection();
    }

    public function list(array $filters = []): array
    {
        return $this->serialStorage;
    }

    public function findBySerial(string $serial): ?array
    {
        foreach ($this->serialStorage as $item) {
            if ($item['serial_number'] === $serial) {
                return $item;
            }
        }
        return null;
    }

    public function create(array $data): array
    {
        $id = count($this->serialStorage) + 1;
        $data['id'] = $id;
        $this->serialStorage[] = $data;
        return $data;
    }

    public function reserve(string $serial, int $orderId): array
    {
        foreach ($this->serialStorage as &$item) {
            if ($item['serial_number'] === $serial) {
                $item['status'] = 'reserved';
                $item['reserved_for_order_id'] = $orderId;
                return $item;
            }
        }
        return ['serial_number' => $serial, 'status' => 'reserved'];
    }

    public function markSold(string $serial, int $orderId): array
    {
        foreach ($this->serialStorage as &$item) {
            if ($item['serial_number'] === $serial) {
                $item['status'] = 'sold';
                return $item;
            }
        }
        return ['serial_number' => $serial, 'status' => 'sold'];
    }

    public function markReturned(string $serialNumber, ?int $orderId = null): array
    {
        foreach ($this->serialStorage as &$item) {
            if ($item['serial_number'] === $serialNumber) {
                $item['status'] = 'available';
                return $item;
            }
        }
        return ['serial_number' => $serialNumber, 'status' => 'available'];
    }

    public function release(string $serialNumber): array
    {
        foreach ($this->serialStorage as &$item) {
            if ($item['serial_number'] === $serialNumber) {
                $item['status'] = 'available';
                unset($item['reserved_for_order_id']);
                return $item;
            }
        }
        return ['serial_number' => $serialNumber, 'status' => 'available'];
    }

    public function db(): \CodeIgniter\Database\BaseConnection
    {
        return $this->fakeDb;
    }
}

class FakeDbConnection extends \CodeIgniter\Database\BaseConnection
{
    public function __construct()
    {
    }

    public function transBegin(bool $testMode = false): bool
    {
        return true;
    }

    public function transCommit(): bool
    {
        return true;
    }

    public function transRollback(): bool
    {
        return true;
    }

    protected function _transBegin(): bool
    {
        return true;
    }

    protected function _transCommit(): bool
    {
        return true;
    }

    protected function _transRollback(): bool
    {
        return true;
    }

    public function connect(bool $persistent = false): mixed
    {
        return true;
    }

    public function reconnect(): bool
    {
        return true;
    }

    public function getVersion(): string
    {
        return '8.0.0';
    }

    protected function execute(string $sql): mixed
    {
        return true;
    }

    public function affectedRows(): int
    {
        return 0;
    }

    public function insertID(): int
    {
        return 0;
    }

    public function _close(): void
    {
    }

    protected function _escapeString(string $str): string
    {
        return addslashes($str);
    }

    protected function _listTables(bool $constrainByPrefix = false, ?string $tableName = null): string
    {
        return "SHOW TABLES";
    }

    protected function _listColumns($table = ''): string
    {
        return "SHOW COLUMNS FROM {$table}";
    }

    public function _fieldData(string $table): array
    {
        return [];
    }

    public function _indexData(string $table): array
    {
        return [];
    }

    public function _foreignKeyData(string $table): array
    {
        return [];
    }

    public function error(): array
    {
        return ['code' => null, 'message' => ''];
    }

    public function setDatabase(string $databaseName): bool
    {
        return true;
    }
}

class FakeProductBatchRepo extends ProductBatchRepository
{
    public array $batchStorage = [];

    public function __construct()
    {
    }

    public function find(int $id): ?array
    {
        return $this->batchStorage[$id] ?? null;
    }
}

class FakeProductSerialValidator extends ProductSerialValidator
{
    public function __construct()
    {
    }

    public function validateFilters(array $filters): array
    {
        return $filters;
    }

    public function validateCreate(array $data): array
    {
        return $data;
    }

    public function validateReserve(array $data): array
    {
        return $data;
    }

    public function validateSell(array $data): array
    {
        return $data;
    }

    public function validateReturn(array $data): array
    {
        return $data;
    }
}
