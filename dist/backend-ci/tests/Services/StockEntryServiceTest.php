<?php

namespace Tests\Services;

use App\Services\Inventory\StockEntryService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: StockEntryService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class StockEntryServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private StockEntryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBase();
        $this->service = new StockEntryService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_issues_stock_and_updates_bins()
    {
        $entry = $this->service->create([
            'type' => 'issue',
            'branch_id' => 1,
            'source_warehouse_id' => 1,
            'items' => [
                ['product_id' => 1, 'qty' => 3],
            ],
        ])['data'];

        $submitted = $this->service->submit($entry['id'])['data'];
        $this->assertEquals('submitted', $submitted['status']);

        $bin = $this->db->table('stock_bins')->where('branch_id', 1)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(7.0, (float) $bin['on_hand_qty']);
    }

    /** @test */
    public function it_transfers_between_warehouses()
    {
        $entry = $this->service->create([
            'type' => 'transfer',
            'branch_id' => 1,
            'source_warehouse_id' => 1,
            'target_warehouse_id' => 2,
            'items' => [
                ['product_id' => 1, 'qty' => 4],
            ],
        ])['data'];

        $this->service->submit($entry['id']);

        $sourceBin = $this->db->table('stock_bins')->where('branch_id', 1)->where('product_id', 1)->get()->getRowArray();
        $targetBin = $this->db->table('stock_bins')->where('branch_id', 2)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(6.0, (float) $sourceBin['on_hand_qty']);
        $this->assertEquals(9.0, (float) $targetBin['on_hand_qty']);
    }

    /** @test */
    public function it_processes_return_and_persists_reason()
    {
        $entry = $this->service->create([
            'type' => 'return',
            'branch_id' => 2,
            'target_warehouse_id' => 2,
            'return_reason' => 'damaged',
            'items' => [
                ['product_id' => 1, 'qty' => 2],
            ],
        ])['data'];

        $submitted = $this->service->submit($entry['id'])['data'];
        $this->assertEquals('damaged', $submitted['return_reason']);

        $targetBin = $this->db->table('stock_bins')->where('branch_id', 2)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(7.0, (float) $targetBin['on_hand_qty']);
    }

    /** @test */
    public function it_cancels_and_reverses_movements()
    {
        $entry = $this->service->create([
            'type' => 'issue',
            'branch_id' => 1,
            'source_warehouse_id' => 1,
            'items' => [
                ['product_id' => 1, 'qty' => 2],
            ],
        ])['data'];

        $this->service->submit($entry['id']);
        $cancelled = $this->service->cancel($entry['id'])['data'];
        $this->assertEquals('cancelled', $cancelled['status']);

        $bin = $this->db->table('stock_bins')->where('branch_id', 1)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(10.0, (float) $bin['on_hand_qty']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'HCM', 'created_at' => $now]);
        $this->db->table('branches')->insert(['id' => 2, 'name' => 'HN', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 1, 'name' => 'Main WH', 'branch_id' => 1, 'status' => 'active', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 2, 'name' => 'Secondary WH', 'branch_id' => 2, 'status' => 'active', 'created_at' => $now]);
        $this->db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 10,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
        $this->db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 2,
            'batch_id' => null,
            'on_hand_qty' => 5,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
    }
}
