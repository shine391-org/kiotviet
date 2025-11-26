<?php

namespace Tests\Services;

use App\Repositories\Inventory\InventoryRepository;
use App\Services\Common\NotificationService;
use App\Services\Inventory\InventoryService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
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
    private InventoryService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $repo = new TestInventoryRepository($this->db);
        $this->service = new InventoryService($repo, null, new NotificationService());
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
        $row = $this->db->table('inventory_stock')->where('warehouse_id', $wh)->where('product_id', 1)->get()->getRowArray();
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

        $fromRow = $this->db->table('inventory_stock')->where('warehouse_id', $from)->where('product_id', 1)->get()->getRowArray();
        $toRow = $this->db->table('inventory_stock')->where('warehouse_id', $to)->where('product_id', 1)->get()->getRowArray();
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

        $alert = $this->db->table('inventory_alerts')->get()->getRowArray();
        $this->assertNotNull($alert);
        $this->assertSame('OUT_OF_STOCK', $alert['alert_type']);
    }

    public function test_in_movement_creates_valuation(): void
    {
        $wh = $this->seedWarehouse('WH-A');
        $this->service->createMovement([
            'movement_type' => 'IN',
            'product_id' => 1,
            'to_warehouse_id' => $wh,
            'quantity' => 4,
            'unit_cost' => 5,
            'valuation_method' => 'FIFO',
        ]);

        $row = $this->db->table('inventory_valuation')->get()->getRowArray();
        $this->assertSame('FIFO', $row['valuation_method']);
        $this->assertEquals(4.0, (float) $row['quantity']);
        $this->assertEquals(5.0, (float) $row['unit_cost']);
    }

    public function test_reserve_and_release_stock(): void
    {
        $wh = $this->seedWarehouse('WH-A');
        $this->seedStock(1, null, $wh, 5);

        $reserved = $this->service->reserveStock([
            'product_id' => 1,
            'warehouse_id' => $wh,
            'quantity' => 2,
        ]);
        $this->assertEquals(2.0, (float) $reserved['data']['quantity_reserved']);

        $released = $this->service->releaseStock([
            'product_id' => 1,
            'warehouse_id' => $wh,
            'quantity' => 1,
        ]);
        $this->assertEquals(1.0, (float) $released['data']['quantity_reserved']);
    }

    public function test_ignore_alert(): void
    {
        $wh = $this->seedWarehouse('WH-A');
        $this->seedStock(1, null, $wh, 1, 2);
        $this->service->createMovement([
            'movement_type' => 'OUT',
            'product_id' => 1,
            'from_warehouse_id' => $wh,
            'quantity' => 1,
        ]);
        $alert = $this->db->table('inventory_alerts')->get()->getRowArray();

        $this->service->ignoreAlert($alert['id'], 99);
        $row = $this->db->table('inventory_alerts')->where('id', $alert['id'])->get()->getRowArray();
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
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $this->db->query('DROP TABLE IF EXISTS inventory_alerts');
        $this->db->query('DROP TABLE IF EXISTS inventory_valuation');
        $this->db->query('DROP TABLE IF EXISTS inventory_movements');
        $this->db->query('DROP TABLE IF EXISTS inventory_stock');
        $this->db->query('DROP TABLE IF EXISTS warehouses');

        $this->db->query("CREATE TABLE warehouses (
            id INTEGER PRIMARY KEY {$auto},
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
        )");

        $this->db->query("CREATE TABLE inventory_stock (
            id INTEGER PRIMARY KEY {$auto},
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
        )");

        $this->db->query("CREATE TABLE inventory_movements (
            id INTEGER PRIMARY KEY {$auto},
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
        )");

        $this->db->query("CREATE TABLE inventory_alerts (
            id INTEGER PRIMARY KEY {$auto},
            alert_type TEXT,
            product_id INTEGER,
            variant_id INTEGER,
            warehouse_id INTEGER,
            current_quantity REAL,
            threshold_quantity REAL,
            status TEXT,
            resolved_by INTEGER,
            resolved_at TEXT,
            created_at TEXT
        )");

        $this->db->query("CREATE TABLE inventory_valuation (
            id INTEGER PRIMARY KEY {$auto},
            warehouse_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            valuation_method TEXT,
            quantity REAL,
            unit_cost REAL,
            total_value REAL,
            movement_id INTEGER,
            created_at TEXT
        )");
    }

    private function seedWarehouse(string $code): int
    {
        $this->db->table('warehouses')->insert([
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
    }
}
