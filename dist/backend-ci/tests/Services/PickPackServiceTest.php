<?php

namespace Tests\Services;

use App\Services\Inventory\PickPackService;
use App\Services\Inventory\StockEntryService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: PickPackService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class PickPackServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private PickPackService $service;
    private StockEntryService $stockEntries;
    private int $stockEntryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBase();
        $this->stockEntries = new StockEntryService();
        $this->service = new PickPackService();
        $entry = $this->stockEntries->create([
            'type' => 'transfer',
            'branch_id' => 1,
            'source_warehouse_id' => 1,
            'target_warehouse_id' => 2,
            'items' => [
                ['product_id' => 1, 'qty' => 1],
            ],
        ])['data'];
        $this->stockEntryId = (int) $entry['id'];
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_pick_list_with_items()
    {
        $pick = $this->service->createPickList([
            'stock_entry_id' => $this->stockEntryId,
            'source_warehouse_id' => 1,
            'items' => [
                ['product_id' => 1, 'qty' => 1],
            ],
        ])['data'];

        $this->assertEquals('open', $pick['status']);
        $this->assertEquals(1, count($pick['items']));
    }

    /** @test */
    public function it_creates_packing_slip_from_pick_list()
    {
        $pick = $this->service->createPickList([
            'stock_entry_id' => $this->stockEntryId,
            'source_warehouse_id' => 1,
            'items' => [
                ['product_id' => 1, 'qty' => 1],
            ],
        ])['data'];

        $packing = $this->service->createPackingSlip([
            'pick_list_id' => $pick['id'],
            'stock_entry_id' => $this->stockEntryId,
            'source_warehouse_id' => 1,
            'target_warehouse_id' => 2,
            'items' => [
                ['product_id' => 1, 'qty' => 1],
            ],
        ])['data'];

        $this->assertEquals('packed', $packing['status']);
        $this->assertEquals(1, count($packing['items']));
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'HCM', 'created_at' => $now]);
        $this->db->table('branches')->insert(['id' => 2, 'name' => 'HN', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 1, 'name' => 'Main WH', 'branch_id' => 1, 'status' => 'active', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 2, 'name' => 'Secondary WH', 'branch_id' => 2, 'status' => 'active', 'created_at' => $now]);
    }
}
