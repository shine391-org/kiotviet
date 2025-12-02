<?php

namespace Tests\Services;

use App\Services\Inventory\StockEntryReturnService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: StockEntryReturnService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class StockEntryReturnServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private StockEntryReturnService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBase();
        $this->service = new StockEntryReturnService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_processes_return_entry_and_updates_stock()
    {
        $res = $this->service->processReturn([
            'branch_id' => 1,
            'target_warehouse_id' => 1,
            'return_reason' => 'customer_return',
            'items' => [
                ['product_id' => 1, 'qty' => 3],
            ],
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals('submitted', $res['data']['status']);
        $this->assertEquals('customer_return', $res['data']['return_reason']);

        $bin = $this->db->table('stock_bins')->where('branch_id', 1)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(8.0, (float) $bin['on_hand_qty']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'HCM', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 1, 'name' => 'Return WH', 'branch_id' => 1, 'status' => 'active', 'created_at' => $now]);
        $this->db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 5,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
    }
}
