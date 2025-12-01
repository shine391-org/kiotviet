<?php

namespace Tests\Services;

use App\Repositories\Inventory\InventoryRepository;
use App\Services\Common\NotificationService;
use App\Services\Inventory\InventoryService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
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

    public function test_in_movement_increases_stock(): void
    {
        $wh = $this->seedWarehouse('WH-A', 100);
        $result = $this->service->createMovement([
            'movement_type' => 'IN',
            'product_id' => 1,
            'variant_id' => null,
            'to_warehouse_id' => $wh,
            'quantity' => 5,
            'unit_cost' => 10,
            'created_by' => 1,
        ]);

        $this->assertTrue($result['success']);
        // Use raw SQL for proper NULL handling in test
        $query = $this->db->query("SELECT * FROM inventory_stock WHERE warehouse_id = ? AND product_id = ? AND variant_id IS NULL", [$wh, 1]);
        $row = $query ? $query->getRowArray() : null;
        $this->assertNotNull($row, 'Stock row should exist');
        $this->assertEquals(5.0, (float) $row['quantity_on_hand']);
    }

    public function test_out_movement_prevents_negative(): void
    {
        $wh = $this->seedWarehouse('WH-A', 101);
        $this->seedStock(1, null, $wh, 2);

        $this->expectException(RuntimeException::class);
        $this->service->createMovement([
            'movement_type' => 'OUT',
            'product_id' => 1,
            'variant_id' => null,
            'from_warehouse_id' => $wh,
            'quantity' => 5,
        ]);
    }

    public function test_transfer_moves_between_warehouses(): void
    {
        $from = $this->seedWarehouse('WH-A', 102);
        $to = $this->seedWarehouse('WH-B', 103);
        $this->seedStock(1, null, $from, 10);

        $this->service->createMovement([
            'movement_type' => 'TRANSFER',
            'product_id' => 1,
            'variant_id' => null,
            'from_warehouse_id' => $from,
            'to_warehouse_id' => $to,
            'quantity' => 3,
        ]);

        $fromQuery = $this->db->query("SELECT * FROM inventory_stock WHERE warehouse_id = ? AND product_id = ? AND variant_id IS NULL", [$from, 1]);
        $fromRow = $fromQuery ? $fromQuery->getRowArray() : null;
        $toQuery = $this->db->query("SELECT * FROM inventory_stock WHERE warehouse_id = ? AND product_id = ? AND variant_id IS NULL", [$to, 1]);
        $toRow = $toQuery ? $toQuery->getRowArray() : null;
        $this->assertNotNull($fromRow, 'From warehouse stock should exist');
        $this->assertNotNull($toRow, 'To warehouse stock should exist');
        $this->assertEquals(7.0, (float) $fromRow['quantity_on_hand']);
        $this->assertEquals(3.0, (float) $toRow['quantity_on_hand']);
    }

    public function test_low_stock_creates_alert(): void
    {
        $wh = $this->seedWarehouse('WH-A', 104);
        $this->seedStock(1, null, $wh, 1, 2); // minimum_stock = 2

        $this->service->createMovement([
            'movement_type' => 'OUT',
            'product_id' => 1,
            'variant_id' => null,
            'from_warehouse_id' => $wh,
            'quantity' => 1,
        ]);

        $alertQuery = $this->db->table('inventory_alerts')->get();
        $alert = $alertQuery ? $alertQuery->getRowArray() : null;
        $this->assertNotNull($alert, 'Alert should be created');
        $this->assertSame('OUT_OF_STOCK', $alert['alert_type']);
    }

    public function test_in_movement_creates_valuation(): void
    {
        $wh = $this->seedWarehouse('WH-A', 105);
        $this->service->createMovement([
            'movement_type' => 'IN',
            'product_id' => 1,
            'variant_id' => null,
            'to_warehouse_id' => $wh,
            'quantity' => 4,
            'unit_cost' => 5,
            'valuation_method' => 'FIFO',
        ]);

        $query = $this->db->table('inventory_valuation')->get();
        $row = $query ? $query->getRowArray() : null;
        $this->assertNotNull($row, 'Valuation row should exist');
        $this->assertSame('FIFO', $row['valuation_method']);
        $this->assertEquals(4.0, (float) $row['quantity']);
        $this->assertEquals(5.0, (float) $row['unit_cost']);
    }

    public function test_reserve_and_release_stock(): void
    {
        $wh = $this->seedWarehouse('WH-A', 106);
        $this->seedStock(1, null, $wh, 5);

        $reserved = $this->service->reserveStock([
            'product_id' => 1,
            'variant_id' => null,
            'warehouse_id' => $wh,
            'quantity' => 2,
        ]);
        $this->assertEquals(2.0, (float) $reserved['data']['quantity_reserved']);

        $released = $this->service->releaseStock([
            'product_id' => 1,
            'variant_id' => null,
            'warehouse_id' => $wh,
            'quantity' => 1,
        ]);
        $this->assertEquals(1.0, (float) $released['data']['quantity_reserved']);
    }

    public function test_ignore_alert(): void
    {
        $wh = $this->seedWarehouse('WH-A', 107);
        $this->seedStock(1, null, $wh, 1, 2);
        $this->service->createMovement([
            'movement_type' => 'OUT',
            'product_id' => 1,
            'variant_id' => null,
            'from_warehouse_id' => $wh,
            'quantity' => 1,
        ]);
        $alertQuery = $this->db->table('inventory_alerts')->get();
        $alert = $alertQuery ? $alertQuery->getRowArray() : null;
        $this->assertNotNull($alert, 'Alert should exist for ignore test');

        $this->service->ignoreAlert($alert['id'], 99);
        $rowQuery = $this->db->table('inventory_alerts')->where('id', $alert['id'])->get();
        $row = $rowQuery ? $rowQuery->getRowArray() : null;
        $this->assertNotNull($row, 'Alert row should still exist after ignore');
        $this->assertSame('ignored', $row['status']);
        $this->assertEquals(99, (int) $row['resolved_by']);
    }

    public function test_ignore_alert_throws_when_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->ignoreAlert(999, null);
    }

    public function test_resolve_alert_throws_when_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->resolveAlert(999, null);
    }

    private function resetSchema(): void
    {
        // Create tables if they don't exist
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        
        // Create warehouses table
        $this->db->query("
            CREATE TABLE IF NOT EXISTS warehouses (
                id INT PRIMARY KEY,
                code VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                status VARCHAR(20) DEFAULT 'active',
                is_default TINYINT DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME NULL
            )
        ");
        
        // Create inventory_stock table
        $this->db->query("
            CREATE TABLE IF NOT EXISTS inventory_stock (
                id INT AUTO_INCREMENT PRIMARY KEY,
                branch_id INT NULL,
                product_id INT NOT NULL,
                variant_id INT NULL,
                warehouse_id INT NOT NULL,
                quantity_on_hand DECIMAL(15,4) DEFAULT 0,
                quantity_reserved DECIMAL(15,4) DEFAULT 0,
                minimum_stock DECIMAL(15,4) DEFAULT 0,
                last_movement_at DATETIME NULL,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME NULL
            )
        ");
        
        // Create inventory_movements table
        $this->db->query("
            CREATE TABLE IF NOT EXISTS inventory_movements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                movement_type VARCHAR(20) NOT NULL,
                product_id INT NOT NULL,
                variant_id INT NULL,
                from_warehouse_id INT NULL,
                to_warehouse_id INT NULL,
                quantity DECIMAL(15,4) NOT NULL,
                unit_cost DECIMAL(15,4) NULL,
                reference_code VARCHAR(100) NULL,
                valuation_method VARCHAR(20) NULL,
                created_by INT NULL,
                created_at DATETIME
            )
        ");
        
        // Create inventory_alerts table
        $this->db->query("
            CREATE TABLE IF NOT EXISTS inventory_alerts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                alert_type VARCHAR(50) NOT NULL,
                product_id INT NOT NULL,
                variant_id INT NULL,
                warehouse_id INT NOT NULL,
                current_quantity DECIMAL(15,4),
                threshold_quantity DECIMAL(15,4),
                status VARCHAR(20) DEFAULT 'active',
                resolved_by INT NULL,
                resolved_at DATETIME NULL,
                created_at DATETIME
            )
        ");
        
        // Create inventory_valuation table
        $this->db->query("
            CREATE TABLE IF NOT EXISTS inventory_valuation (
                id INT AUTO_INCREMENT PRIMARY KEY,
                warehouse_id INT NOT NULL,
                product_id INT NOT NULL,
                variant_id INT NULL,
                valuation_method VARCHAR(20) NOT NULL,
                quantity DECIMAL(15,4) NOT NULL,
                unit_cost DECIMAL(15,4) NOT NULL,
                total_value DECIMAL(15,4) NOT NULL,
                movement_id INT NULL,
                created_at DATETIME
            )
        ");
        
        // Truncate data
        $tables = ['inventory_alerts', 'inventory_valuation', 'inventory_movements', 'inventory_stock', 'warehouses'];
        foreach ($tables as $table) {
            $this->db->query("TRUNCATE TABLE $table");
        }
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function seedWarehouse(string $code, int $id): int
    {
        $payload = [
            'id' => $id,
            'code' => $code,
            'name' => $code,
            'status' => 'active',
            'is_default' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ];
        $this->db->table('warehouses')->insert($payload);
        return $id;
    }

    private function seedStock(int $productId, ?int $variantId, int $warehouseId, float $qty, float $minStock = 0): void
    {
        $this->db->table('inventory_stock')->insert([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => $qty,
            'quantity_reserved' => 0,
            'minimum_stock' => $minStock,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);
        $insertId = $this->db->insertID();
        error_log("DEBUG SEED: Inserted stock row with ID=$insertId for product=$productId, warehouse=$warehouseId, variant=$variantId, qty=$qty");
        
        // Verify insert
        $query = $this->db->query("SELECT * FROM inventory_stock WHERE product_id = ? AND warehouse_id = ? AND variant_id IS NULL", [$productId, $warehouseId]);
        if ($query) {
            $check = $query->getRowArray();
            error_log("DEBUG SEED VERIFY: " . json_encode($check));
        } else {
            error_log("DEBUG SEED VERIFY: Query failed - " . $this->db->error()['message']);
        }
    }
}
