<?php

namespace Tests\Services;

use App\Repositories\Products\ProductBatchRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Services\Products\ProductBatchService;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Inventory\StockLedgerService;
use App\Validators\ProductBatchValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\BatchSchemaTrait;
use RuntimeException;
use InvalidArgumentException;

/**
 * @agent-test: ProductBatchService (DB)
 * @agent-pattern: Service test with DevDatabaseTrait + BatchSchemaTrait
 */
class ProductBatchServiceDbTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use BatchSchemaTrait;

    private ProductBatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetBatchSchema();

        $repo = new ProductBatchRepository(null, $this->db);
        $validator = new ProductBatchValidator();
        $inventoryRepo = new InventoryRepository($this->db);
        $movementLogger = new class extends InventoryMovementLogger {
            public function __construct() {}
            public function log(int $branchId, int $productId, ?int $variantId, string $type, float $quantity, ?int $batchId = null, ?string $serialNumber = null, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null, ?int $createdBy = null): array { return []; }
        };
        $ledger = new class extends StockLedgerService {
            public function record(array $input): array { return []; }
        };

        $this->service = new ProductBatchService($repo, $validator, $inventoryRepo, $movementLogger, $ledger);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testListReturnsEmptyWhenNoBatches(): void
    {
        $result = $this->service->list([]);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['data']);
    }

    public function testListReturnsBatches(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $this->createBatch($product['id'], $branch['id']);

        $result = $this->service->list([]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
    }

    public function testListFiltersByProductId(): void
    {
        $product1 = $this->createProductForBatch();
        $product2 = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $this->createBatch($product1['id'], $branch['id']);
        $this->createBatch($product2['id'], $branch['id']);

        $result = $this->service->list(['product_id' => $product1['id']]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
    }

    public function testShowReturnsBatch(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $batch = $this->createBatch($product['id'], $branch['id']);

        $result = $this->service->show($batch['id']);

        $this->assertTrue($result['success']);
        $this->assertSame($batch['batch_number'], $result['data']['batch_number']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->show(99999);
    }

    public function testCreateBatch(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();

        $result = $this->service->create([
            'product_id' => $product['id'],
            'branch_id' => $branch['id'],
            'batch_number' => 'NEW-BATCH-001',
            'initial_quantity' => 100,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('NEW-BATCH-001', $result['data']['batch_number']);
        $this->assertSame(100.0, (float) $result['data']['current_quantity']);
    }

    public function testCreateThrowsWhenBatchNumberExists(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $this->createBatch($product['id'], $branch['id'], ['batch_number' => 'DUPLICATE']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'product_id' => $product['id'],
            'branch_id' => $branch['id'],
            'batch_number' => 'DUPLICATE',
        ]);
    }

    public function testUpdateBatch(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $batch = $this->createBatch($product['id'], $branch['id']);

        $result = $this->service->update($batch['id'], [
            'status' => 'inactive',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('inactive', $result['data']['status']);
    }

    public function testUpdateThrowsWhenDuplicateBatchNumber(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $batch1 = $this->createBatch($product['id'], $branch['id'], ['batch_number' => 'BATCH-1']);
        $batch2 = $this->createBatch($product['id'], $branch['id'], ['batch_number' => 'BATCH-2']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->update($batch2['id'], ['batch_number' => 'BATCH-1']);
    }

    public function testAdjustQuantityPositive(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $batch = $this->createBatch($product['id'], $branch['id'], ['current_quantity' => 100]);

        $result = $this->service->adjustQuantity($batch['id'], [
            'quantity_delta' => 50,
            'reason' => 'Stock in',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(150.0, (float) $result['data']['current_quantity']);
    }

    public function testAdjustQuantityNegative(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $batch = $this->createBatch($product['id'], $branch['id'], ['current_quantity' => 100]);

        $result = $this->service->adjustQuantity($batch['id'], [
            'quantity_delta' => -30,
            'reason' => 'Stock out',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(70.0, (float) $result['data']['current_quantity']);
    }

    public function testAdjustQuantityThrowsOnZeroDelta(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $batch = $this->createBatch($product['id'], $branch['id']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->adjustQuantity($batch['id'], ['quantity_delta' => 0]);
    }

    public function testExpiringFiltersByDays(): void
    {
        $product = $this->createProductForBatch();
        $branch = $this->createBranchForBatch();
        $this->createBatch($product['id'], $branch['id'], [
            'expiry_date' => date('Y-m-d', strtotime('+10 days')),
        ]);

        $result = $this->service->expiring(['days' => 30]);

        $this->assertTrue($result['success']);
    }
}
