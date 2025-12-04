<?php

namespace Tests\Services;

use App\Repositories\Inventory\InventoryRepository;
use App\Services\Common\NotificationService;
use App\Services\Inventory\InventoryService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Assertions\DatabaseAssertions;
use Tests\Support\Assertions\BusinessLogicAssertions;
use Tests\Support\Assertions\EdgeCaseAssertions;
use Tests\Support\Assertions\ErrorMessageAssertions;
use Tests\Support\Factories\ProductFactory;
use Tests\Support\Factories\WarehouseFactory;
use RuntimeException;

class TestInventoryRepository extends InventoryRepository
{
    public function __construct($db)
    {
        parent::__construct($db);
    }

    public function reserveStock(int $productId, ?int $variantId, int $warehouseId, float $qty): array
    {
        $row = $this->stockRow($productId, $variantId, $warehouseId);
        if (! $row) { throw new RuntimeException('Stock not found'); }

        $newReserved = ($row['quantity_reserved'] ?? 0) + $qty;
        $available = ($row['quantity_on_hand'] ?? 0) - $newReserved;
        if ($available < 0) { throw new RuntimeException('Insufficient available stock'); }

        $this->db->table('inventory_stock')->where('id', $row['id'])->update([
            'quantity_reserved' => $newReserved,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $row['quantity_reserved'] = $newReserved;
        return $row;
    }

    public function releaseStock(int $productId, ?int $variantId, int $warehouseId, float $qty): array
    {
        $row = $this->stockRow($productId, $variantId, $warehouseId);
        if (! $row) { throw new RuntimeException('Stock not found'); }

        $newReserved = max(0, ($row['quantity_reserved'] ?? 0) - $qty);
        $this->db->table('inventory_stock')->where('id', $row['id'])->update([
            'quantity_reserved' => $newReserved,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $row['quantity_reserved'] = $newReserved;
        return $row;
    }
}

class InventoryServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use DatabaseAssertions;
    use BusinessLogicAssertions;
    use EdgeCaseAssertions;
    use ErrorMessageAssertions;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSchema();
        $repo = new TestInventoryRepository($this->db);
        $this->service = new InventoryService($repo, null, new NotificationService());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function test_in_movement_increases_stock(): void
    {
        // Create test data using factories
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-A']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);

        $movementData = [
            'movement_type' => 'IN',
            'product_id' => $productId,
            'variant_id' => null,
            'to_warehouse_id' => $warehouseId,
            'quantity' => 5,
            'unit_cost' => 10,
            'created_by' => 1,
        ];

        $result = $this->service->createMovement($movementData);

        // Strong assertions for service response
        $this->assertServiceSuccess($result);
        $this->assertServiceDataStructure($result, ['id', 'movement_type', 'quantity']);
        
        // Business logic validation for IN movement
        $this->assertInventoryMovement($result['data'], 'IN', 5.0);
        
        // Verify database state with strong assertions
        $this->assertDatabaseHas('inventory_stock', [
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => 5.0,
            'quantity_reserved' => 0.0
        ]);
        
        // Verify stock level calculation
        $this->assertStockLevelCalculation(0.0, 5.0, 'IN', 5.0);
        
        // Verify audit trail
        $this->assertAuditTrail('inventory_movements', $result['data']['id'], 'create');
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $result['data']['id'],
            'movement_type' => 'IN',
            'product_id' => $productId,
            'to_warehouse_id' => $warehouseId,
            'quantity' => 5.0,
            'unit_cost' => 10.0
        ]);
    }

    /** @test */
    public function test_out_movement_prevents_negative(): void
    {
        // Create test data using factories
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-A']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        // Seed initial stock
        $this->seedStock($productId, null, $warehouseId, 2);

        $movementData = [
            'movement_type' => 'OUT',
            'product_id' => $productId,
            'variant_id' => null,
            'from_warehouse_id' => $warehouseId,
            'quantity' => 5,
        ];

        // Strong assertion for business validation
        $this->assertBusinessValidation(function() use ($movementData) {
            $this->service->createMovement($movementData);
        }, RuntimeException::class, 'Insufficient available stock');
        
        // Verify stock level unchanged
        $this->assertDatabaseHas('inventory_stock', [
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => 2.0,
            'quantity_reserved' => 0.0
        ]);
    }

    /** @test */
    public function test_transfer_moves_between_warehouses(): void
    {
        // Create test data using factories
        $fromWarehouseId = WarehouseFactory::createActive(['code' => 'WH-A']);
        $toWarehouseId = WarehouseFactory::createActive(['code' => 'WH-B']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        // Seed initial stock
        $this->seedStock($productId, null, $fromWarehouseId, 10);

        $movementData = [
            'movement_type' => 'TRANSFER',
            'product_id' => $productId,
            'variant_id' => null,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'quantity' => 3,
        ];

        $result = $this->service->createMovement($movementData);

        // Strong assertions for service response
        $this->assertServiceSuccess($result);
        $this->assertInventoryMovement($result['data'], 'TRANSFER', 3.0);
        
        // Business logic validation for transfer
        $this->assertStockLevelCalculation(10.0, 3.0, 'OUT', 7.0);
        $this->assertStockLevelCalculation(0.0, 3.0, 'IN', 3.0);
        
        // Verify database state with strong assertions
        $this->assertDatabaseHas('inventory_stock', [
            'warehouse_id' => $fromWarehouseId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => 7.0,
            'quantity_reserved' => 0.0
        ]);
        
        $this->assertDatabaseHas('inventory_stock', [
            'warehouse_id' => $toWarehouseId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => 3.0,
            'quantity_reserved' => 0.0
        ]);
        
        // Verify audit trail
        $this->assertAuditTrail('inventory_movements', $result['data']['id'], 'create');
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $result['data']['id'],
            'movement_type' => 'TRANSFER',
            'product_id' => $productId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'quantity' => 3.0
        ]);
    }

    /** @test */
    public function test_low_stock_creates_alert(): void
    {
        // Create test data using factories
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-A']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        // Seed initial stock with minimum stock threshold
        $this->seedStock($productId, null, $warehouseId, 1, 2); // minimum_stock = 2

        $movementData = [
            'movement_type' => 'OUT',
            'product_id' => $productId,
            'variant_id' => null,
            'from_warehouse_id' => $warehouseId,
            'quantity' => 1,
        ];

        $result = $this->service->createMovement($movementData);

        // Strong assertions for service response
        $this->assertServiceSuccess($result);
        
        // Verify alert creation with strong assertions
        $this->assertDatabaseHas('inventory_alerts', [
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'variant_id' => null,
            'alert_type' => 'OUT_OF_STOCK',
            'status' => 'active'
        ]);
        
        // Verify alert details
        $alertQuery = $this->db->table('inventory_alerts')->get();
        $alert = $alertQuery ? $alertQuery->getRowArray() : null;
        $this->assertNotNull($alert, 'Alert should be created');
        $this->assertEquals('OUT_OF_STOCK', $alert['alert_type'],
            'Should create OUT_OF_STOCK alert');
        $this->assertEquals(0.0, (float) $alert['current_stock'],
            'Current stock should be 0');
        $this->assertEquals(2.0, (float) $alert['minimum_stock'],
            'Minimum stock should be 2');
        
        // Verify audit trail for alert
        $this->assertAuditTrail('inventory_alerts', $alert['id'], 'create');
    }

    /** @test */
    public function test_in_movement_creates_valuation(): void
    {
        // Create test data using factories
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-A']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);

        $movementData = [
            'movement_type' => 'IN',
            'product_id' => $productId,
            'variant_id' => null,
            'to_warehouse_id' => $warehouseId,
            'quantity' => 4,
            'unit_cost' => 5,
            'valuation_method' => 'FIFO',
        ];

        $result = $this->service->createMovement($movementData);

        // Strong assertions for service response
        $this->assertServiceSuccess($result);
        
        // Verify valuation creation with strong assertions
        $this->assertDatabaseHas('inventory_valuation', [
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'variant_id' => null,
            'valuation_method' => 'FIFO',
            'quantity' => 4.0,
            'unit_cost' => 5.0,
            'total_value' => 20.0 // 4 * 5
        ]);
        
        // Verify valuation details
        $valuationQuery = $this->db->table('inventory_valuation')->get();
        $valuation = $valuationQuery ? $valuationQuery->getRowArray() : null;
        $this->assertNotNull($valuation, 'Valuation row should exist');
        $this->assertEquals('FIFO', $valuation['valuation_method'],
            'Should use FIFO valuation method');
        $this->assertEquals(4.0, (float) $valuation['quantity'],
            'Should record correct quantity');
        $this->assertEquals(5.0, (float) $valuation['unit_cost'],
            'Should record correct unit cost');
        $this->assertEquals(20.0, (float) $valuation['total_value'],
            'Should calculate correct total value');
        
        // Verify audit trail for valuation
        $this->assertAuditTrail('inventory_valuation', $valuation['id'], 'create');
    }

    /** @test */
    public function test_reserve_and_release_stock(): void
    {
        // Create test data using factories
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-A']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        // Seed initial stock
        $this->seedStock($productId, null, $warehouseId, 5);

        $reserveData = [
            'product_id' => $productId,
            'variant_id' => null,
            'warehouse_id' => $warehouseId,
            'quantity' => 2,
        ];

        $reserved = $this->service->reserveStock($reserveData);

        // Strong assertions for reservation
        $this->assertServiceSuccess($reserved);
        $this->assertServiceDataStructure($reserved, ['quantity_reserved', 'quantity_available']);
        $this->assertEquals(2.0, (float) $reserved['data']['quantity_reserved'],
            'Should reserve 2 units');
        $this->assertEquals(3.0, (float) $reserved['data']['quantity_available'],
            'Should have 3 units available');
        
        // Verify database state after reservation
        $this->assertDatabaseHas('inventory_stock', [
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => 5.0,
            'quantity_reserved' => 2.0
        ]);

        $releaseData = [
            'product_id' => $productId,
            'variant_id' => null,
            'warehouse_id' => $warehouseId,
            'quantity' => 1,
        ];

        $released = $this->service->releaseStock($releaseData);

        // Strong assertions for release
        $this->assertServiceSuccess($released);
        $this->assertServiceDataStructure($released, ['quantity_reserved', 'quantity_available']);
        $this->assertEquals(1.0, (float) $released['data']['quantity_reserved'],
            'Should have 1 unit reserved after release');
        $this->assertEquals(4.0, (float) $released['data']['quantity_available'],
            'Should have 4 units available after release');
        
        // Verify database state after release
        $this->assertDatabaseHas('inventory_stock', [
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => 5.0,
            'quantity_reserved' => 1.0
        ]);
        
        // Verify audit trail
        $this->assertAuditTrail('inventory_stock', $this->getStockId($warehouseId, $productId), 'update');
    }

    /** @test */
    public function test_ignore_alert(): void
    {
        // Create test data using factories
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-A']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        // Seed initial stock and create alert
        $this->seedStock($productId, null, $warehouseId, 1, 2);
        
        $movementData = [
            'movement_type' => 'OUT',
            'product_id' => $productId,
            'variant_id' => null,
            'from_warehouse_id' => $warehouseId,
            'quantity' => 1,
        ];

        $this->service->createMovement($movementData);

        // Get alert for testing
        $alertQuery = $this->db->table('inventory_alerts')->get();
        $alert = $alertQuery ? $alertQuery->getRowArray() : null;
        $this->assertNotNull($alert, 'Alert should exist for ignore test');

        $this->service->ignoreAlert($alert['id'], 99);

        // Verify alert status with strong assertions
        $this->assertDatabaseHas('inventory_alerts', [
            'id' => $alert['id'],
            'status' => 'ignored',
            'resolved_by' => 99,
            'resolved_at' => null // Should be set by service
        ]);
        
        // Verify alert still exists
        $rowQuery = $this->db->table('inventory_alerts')->where('id', $alert['id'])->get();
        $row = $rowQuery ? $rowQuery->getRowArray() : null;
        $this->assertNotNull($row, 'Alert row should still exist after ignore');
        $this->assertEquals('ignored', $row['status'],
            'Alert status should be ignored');
        $this->assertEquals(99, (int) $row['resolved_by'],
            'Should record who resolved the alert');
        
        // Verify audit trail
        $this->assertAuditTrail('inventory_alerts', $alert['id'], 'update', 99);
    }

    /** @test */
    public function test_ignore_alert_throws_when_missing(): void
    {
        $this->assertBusinessValidation(function() {
            $this->service->ignoreAlert(999, null);
        }, RuntimeException::class, 'Alert not found');
    }

    /** @test */
    public function test_resolve_alert_throws_when_missing(): void
    {
        $this->assertBusinessValidation(function() {
            $this->service->resolveAlert(999, null);
        }, RuntimeException::class, 'Alert not found');
    }

    /** @test */
    public function test_handles_edge_cases_for_stock_quantities(): void
    {
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-EDGE']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        $boundaryTests = [
            ['field' => 'quantity', 'value' => 0, 'should_pass' => false, 'expected_exception' => RuntimeException::class],
            ['field' => 'quantity', 'value' => -1, 'should_pass' => false, 'expected_exception' => RuntimeException::class],
            ['field' => 'quantity', 'value' => 0.01, 'should_pass' => true],
            ['field' => 'quantity', 'value' => 9999, 'should_pass' => true],
        ];

        $this->assertBoundaryValueHandling(function($data) use ($warehouseId, $productId) {
            $movementData = [
                'movement_type' => 'IN',
                'product_id' => $productId,
                'variant_id' => null,
                'to_warehouse_id' => $warehouseId,
                'quantity' => $data['quantity'],
                'unit_cost' => 10,
            ];
            
            return $this->service->createMovement($movementData);
        }, $boundaryTests);
    }

    /** @test */
    public function test_handles_null_and_empty_values(): void
    {
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-NULL']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        $this->assertNullValueHandling(function($data) use ($warehouseId, $productId) {
            $movementData = [
                'movement_type' => 'IN',
                'product_id' => $data['product_id'] ?? $productId,
                'variant_id' => $data['variant_id'] ?? null,
                'to_warehouse_id' => $data['to_warehouse_id'] ?? $warehouseId,
                'quantity' => $data['quantity'] ?? 5,
                'unit_cost' => $data['unit_cost'] ?? 10,
            ];
            
            return $this->service->createMovement($movementData);
        }, ['product_id', 'to_warehouse_id', 'quantity'], \InvalidArgumentException::class);
    }

    /** @test */
    public function test_provides_meaningful_error_messages(): void
    {
        $warehouseId = WarehouseFactory::createActive(['code' => 'WH-ERR']);
        $productId = ProductFactory::createWithPrice(100.0, 80.0);
        
        // Seed initial stock
        $this->seedStock($productId, null, $warehouseId, 2);

        $movementData = [
            'movement_type' => 'OUT',
            'product_id' => $productId,
            'variant_id' => null,
            'from_warehouse_id' => $warehouseId,
            'quantity' => 5, // More than available
        ];

        try {
            $this->service->createMovement($movementData);
            $this->fail('Should have thrown exception for insufficient stock');
        } catch (\Exception $e) {
            $this->assertUserFriendlyErrorMessage([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            
            $this->assertErrorFieldContext([
                'success' => false,
                'message' => $e->getMessage()
            ], ['quantity', 'stock']);
            
            $this->assertErrorFormatting([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function resetSchema(): void
    {
        $tables = ['inventory_alerts', 'inventory_valuation', 'inventory_movements', 'inventory_stock', 'warehouses'];
        $existing = array_flip($this->db->listTables());
        foreach ($tables as $table) {
            if (! isset($existing[$table])) {
                throw new RuntimeException("Missing required table {$table} in test DB; restore schema before running InventoryServiceTest.");
            }
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $this->db->table($table)->truncate();
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function seedStock(int $productId, ?int $variantId, int $warehouseId, float $qty, float $minStock = 0): void
    {
        $data = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => $qty,
            'quantity_reserved' => 0,
            'minimum_stock' => $minStock,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $this->db->table('inventory_stock')->insert($data);
    }

    /**
     * Helper method to get stock record ID
     */
    private function getStockId(int $warehouseId, int $productId): int
    {
        $stock = $this->db->table('inventory_stock')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->get()->getRowArray();
        
        return (int) $stock['id'];
    }
}
