<?php

namespace Tests\Services;

use App\Services\Inventory\InventoryService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use RuntimeException;

class InventoryServiceTest extends CIUnitTestCase
{
    private InventoryService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->service = new InventoryService();
    }

    public function test_in_movement_increases_stock(): void
    {
        $wh = $this->seedWarehouse('WH-A');
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
        $row = $this->db->table('db_inventory_stock')->where('warehouse_id', $wh)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(5.0, (float) $row['quantity_on_hand']);
    }

    public function test_out_movement_prevents_negative(): void
    {
        $wh = $this->seedWarehouse('WH-A');
        $this->seedStock(1, null, $wh, 2);

        $this->expectException(RuntimeException::class);
        $this->service->createMovement([
            'movement_type' => 'OUT',
            'product_id' => 1,
            'from_warehouse_id' => $wh,
            'quantity' => 5,
        ]);
    }

    public function test_transfer_moves_between_warehouses(): void
    {
        $from = $this->seedWarehouse('WH-A');
        $to = $this->seedWarehouse('WH-B');
        $this->seedStock(1, null, $from, 10);

        $this->service->createMovement([
            'movement_type' => 'TRANSFER',
            'product_id' => 1,
            'from_warehouse_id' => $from,
            'to_warehouse_id' => $to,
            'quantity' => 3,
        ]);

        $fromRow = $this->db->table('db_inventory_stock')->where('warehouse_id', $from)->where('product_id', 1)->get()->getRowArray();
        $toRow = $this->db->table('db_inventory_stock')->where('warehouse_id', $to)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(7.0, (float) $fromRow['quantity_on_hand']);
        $this->assertEquals(3.0, (float) $toRow['quantity_on_hand']);
    }

    public function test_low_stock_creates_alert(): void
    {
        $wh = $this->seedWarehouse('WH-A');
        $this->seedStock(1, null, $wh, 1, 2); // minimum_stock = 2

        $this->service->createMovement([
            'movement_type' => 'OUT',
            'product_id' => 1,
            'from_warehouse_id' => $wh,
            'quantity' => 1,
        ]);

        $alert = $this->db->table('db_inventory_alerts')->get()->getRowArray();
        $this->assertNotNull($alert);
        $this->assertSame('OUT_OF_STOCK', $alert['alert_type']);
    }

    private function resetSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS db_inventory_alerts');
        $this->db->query('DROP TABLE IF EXISTS db_inventory_movements');
        $this->db->query('DROP TABLE IF EXISTS db_inventory_stock');
        $this->db->query('DROP TABLE IF EXISTS db_warehouses');

        $this->db->query('CREATE TABLE db_warehouses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT,
            name TEXT,
            address TEXT,
            phone TEXT,
            manager_id INTEGER,
            status TEXT,
            is_default INTEGER,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');

        $this->db->query('CREATE TABLE db_inventory_stock (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER,
            variant_id INTEGER,
            warehouse_id INTEGER,
            quantity_on_hand REAL,
            quantity_reserved REAL,
            minimum_stock REAL DEFAULT 0,
            last_movement_at TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');

        $this->db->query('CREATE TABLE db_inventory_movements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reference_code TEXT,
            movement_type TEXT,
            product_id INTEGER,
            variant_id INTEGER,
            from_warehouse_id INTEGER,
            to_warehouse_id INTEGER,
            quantity REAL,
            unit_cost REAL,
            reason TEXT,
            created_by INTEGER,
            created_at TEXT
        )');

        $this->db->query('CREATE TABLE db_inventory_alerts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            alert_type TEXT,
            product_id INTEGER,
            variant_id INTEGER,
            warehouse_id INTEGER,
            current_quantity REAL,
            threshold_quantity REAL,
            status TEXT,
            created_at TEXT
        )');
    }

    private function seedWarehouse(string $code): int
    {
        $this->db->table('db_warehouses')->insert([
            'code' => $code,
            'name' => $code,
            'status' => 'active',
            'is_default' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);
        return (int) $this->db->insertID();
    }

    private function seedStock(int $productId, ?int $variantId, int $warehouseId, float $qty, float $minStock = 0): void
    {
        $this->db->table('db_inventory_stock')->insert([
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
    }
}
