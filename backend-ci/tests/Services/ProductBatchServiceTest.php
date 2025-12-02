<?php

namespace Tests\Services;

use App\Repositories\Inventory\InventoryMovementRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Products\ProductBatchRepository;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Products\ProductBatchService;
use App\Validators\ProductBatchValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductBatchSerialSchemaTrait;

/**
 * @agent-test: ProductBatchService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class ProductBatchServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductBatchSerialSchemaTrait;

    private ProductBatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductBatchSerialSchema();
        
        $repo = new ProductBatchRepository(null, $this->db);
        $inventoryRepo = new InventoryRepository(null, $this->db);
        
        // Fix: Inject connection into Model to share transaction
        $movementModel = new \App\Models\InventoryMovementModel($this->db);
        $movementRepo = new InventoryMovementRepository($movementModel, $this->db);
        
        $logger = new InventoryMovementLogger($movementRepo);
        $this->service = new ProductBatchService($repo, new ProductBatchValidator(), $inventoryRepo, $logger);
        $this->seedProduct();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_batch_and_sets_initial_quantity()
    {
        $result = $this->service->create([
            'product_id' => 1,
            'branch_id' => 1,
            'warehouse_id' => 1,
            'batch_number' => 'B-001',
            'initial_quantity' => 5,
            'cost_per_unit' => 10.5,
            'expiry_date' => date('Y-m-d', strtotime('+30 days')),
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(5.0, (float) $result['data']['current_quantity']);

        $stock = $this->db->table('inventory_stock')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(5.0, (float) $stock['quantity_on_hand']);
    }

    /** @test */
    public function it_rejects_duplicate_batch_per_product()
    {
        $this->service->create([
            'product_id' => 1,
            'branch_id' => 1,
            'warehouse_id' => 1,
            'batch_number' => 'B-002',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->create([
            'product_id' => 1,
            'branch_id' => 1,
            'warehouse_id' => 1,
            'batch_number' => 'B-002',
        ]);
    }

    /** @test */
    public function it_adjusts_quantity_and_logs_movement()
    {
        $batch = $this->service->create([
            'product_id' => 1,
            'branch_id' => 1,
            'warehouse_id' => 1,
            'batch_number' => 'B-003',
            'initial_quantity' => 5,
        ])['data'];

        $res = $this->service->adjustQuantity($batch['id'], [
            'quantity_delta' => -2,
            'branch_id' => 1,
            'warehouse_id' => 1,
            'reason' => 'Adjust out',
            'serial_number' => 'SN-11',
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals(3.0, (float) $res['data']['current_quantity']);

        $stock = $this->db->table('inventory_stock')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(3.0, (float) $stock['quantity_on_hand']);

        $movement = $this->db->table('inventory_movements')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertEquals(-2.0, (float) $movement['quantity']);
        $this->assertEquals($batch['id'], (int) $movement['batch_id']);
        $this->assertEquals('SN-11', $movement['serial_number']);
    }

    /** @test */
    public function it_filters_expiring_batches()
    {
        $soon = date('Y-m-d', strtotime('+5 days'));
        $later = date('Y-m-d', strtotime('+40 days'));
        $this->service->create([
            'product_id' => 1,
            'branch_id' => 1,
            'warehouse_id' => 1,
            'batch_number' => 'B-EXP-1',
            'expiry_date' => $soon,
        ]);
        $this->service->create([
            'product_id' => 1,
            'branch_id' => 1,
            'warehouse_id' => 1,
            'batch_number' => 'B-EXP-2',
            'expiry_date' => $later,
        ]);

        $result = $this->service->expiring(['expiring_in_days' => 10, 'product_id' => 1]);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('B-EXP-1', $result['data'][0]['batch_number']);
    }

    private function seedProduct(): void
    {
        $now = date('Y-m-d H:i:s');
        
        // Seed Branch
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'Headquarters',
            'code' => 'HQ',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed Warehouse
        $this->db->table('warehouses')->insert([
            'id' => 1,
            'branch_id' => 1,
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed Product
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P-001',
            'name' => 'Product 1',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
