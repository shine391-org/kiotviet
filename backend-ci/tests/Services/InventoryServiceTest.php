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
        // Tables are created by golden migration, just truncate data
        $tables = ['inventory_alerts', 'inventory_valuation', 'inventory_movements', 'inventory_stock', 'warehouses'];
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $this->db->table($table)->truncate();
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
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
