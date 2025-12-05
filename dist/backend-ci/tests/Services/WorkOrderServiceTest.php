<?php

namespace Tests\Services;

use App\Repositories\Manufacturing\BOMRepository;
use App\Repositories\Manufacturing\WorkOrderRepository;
use App\Services\Manufacturing\BOMService;
use App\Services\Manufacturing\WorkOrderService;
use App\Services\Inventory\StockLedgerService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ManufacturingSchemaTrait;

/**
 * @agent-test: WorkOrderService
 * @agent-pattern: Service test with DevDatabaseTrait + StockLedgerService
 */
class WorkOrderServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ManufacturingSchemaTrait;

    private WorkOrderService $service;
    private BOMService $bomService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetManufacturingSchema();
        $this->seedProducts();
        $this->seedBranch();
        $this->seedStock();

        $bomRepo = new BOMRepository(null, null, $this->db);
        $woRepo = new WorkOrderRepository(null, null, $this->db);
        $this->bomService = new BOMService($bomRepo);
        $this->service = new WorkOrderService($woRepo, $bomRepo, new StockLedgerService());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_complete_consumes_components_and_produces_fg(): void
    {
        $bom = $this->bomService->create([
            'product_id' => 100,
            'items' => [
                ['component_product_id' => 1, 'quantity' => 2],
                ['component_product_id' => 2, 'quantity' => 1],
            ],
        ]);

        $wo = $this->service->create([
            'product_id' => 100,
            'bom_id' => $bom['data']['id'],
            'quantity' => 2,
            'branch_id' => 1,
        ]);
        $this->service->start($wo['data']['id']);
        $completed = $this->service->complete($wo['data']['id']);

        $this->assertSame('completed', $completed['data']['status']);

        $binC1 = $this->db->table('stock_bins')->where(['product_id' => 1, 'branch_id' => 1])->get()->getRowArray();
        $binC2 = $this->db->table('stock_bins')->where(['product_id' => 2, 'branch_id' => 1])->get()->getRowArray();
        $binFG = $this->db->table('stock_bins')->where(['product_id' => 100, 'branch_id' => 1])->get()->getRowArray();

        $this->assertEquals(6.0, (float) $binC1['on_hand_qty']); // 10 - (2*2)
        $this->assertEquals(3.0, (float) $binC2['on_hand_qty']); // 5 - (1*2)
        $this->assertEquals(2.0, (float) $binFG['on_hand_qty']); // produced
    }

    public function test_prevent_oversubscription(): void
    {
        $bom = $this->bomService->create([
            'product_id' => 100,
            'items' => [
                ['component_product_id' => 1, 'quantity' => 50],
            ],
        ]);

        $wo = $this->service->create([
            'product_id' => 100,
            'bom_id' => $bom['data']['id'],
            'quantity' => 1,
            'branch_id' => 1,
        ]);

        $this->service->start($wo['data']['id']);
        $this->expectException(RuntimeException::class);
        $this->service->complete($wo['data']['id']);
    }

    private function seedProducts(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insertBatch([
            ['id' => 100, 'code' => 'FG', 'name' => 'Finished', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 1, 'code' => 'C1', 'name' => 'Comp1', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'C2', 'name' => 'Comp2', 'selling_price' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedBranch(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'Main',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedStock(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('stock_bins')->insertBatch([
            ['product_id' => 1, 'variant_id' => null, 'branch_id' => 1, 'batch_id' => null, 'on_hand_qty' => 10, 'reserved_qty' => 0, 'updated_at' => $now],
            ['product_id' => 2, 'variant_id' => null, 'branch_id' => 1, 'batch_id' => null, 'on_hand_qty' => 5, 'reserved_qty' => 0, 'updated_at' => $now],
        ]);
    }
}
