<?php

namespace Tests\Services;

use App\Services\Assets\AssetService;
use App\Services\Assets\MaintenanceService;
use App\Services\Inventory\StockEntryService;
use App\Repositories\Assets\AssetRepository;
use App\Repositories\Assets\MaintenanceRepository;
use App\Repositories\Inventory\StockEntryRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Inventory\StockLedgerRepository;
use App\Repositories\Inventory\StockBinRepository;
use App\Validators\AssetValidator;
use App\Validators\MaintenanceValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;
use App\Services\Inventory\StockLedgerService;

/**
 * @agent-test: MaintenanceService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class MaintenanceServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private MaintenanceService $service;
    private AssetService $assets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedStock();
        $assetRepo = new AssetRepository(null, $this->db);
        $this->assets = new AssetService($assetRepo, new AssetValidator());
        $stockEntryRepo = new StockEntryRepository(null, null, $this->db);
        $inventoryRepo = new InventoryRepository($this->db);
        $stockLedger = new StockLedgerService(
            new StockLedgerRepository(null, $this->db),
            new StockBinRepository(null, $this->db)
        );
        $stockEntryService = new StockEntryService($stockEntryRepo, new \App\Validators\StockEntryValidator(), $stockLedger, $inventoryRepo);
        $maintenanceRepo = new MaintenanceRepository(null, null, $this->db);
        $this->service = new MaintenanceService(
            $maintenanceRepo,
            $assetRepo,
            new MaintenanceValidator(),
            $stockEntryService
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_schedule_and_completes_work_order_with_parts()
    {
        $asset = $this->assets->create(['asset_name' => 'Conveyor'])['data'];
        $schedule = $this->service->createSchedule([
            'asset_id' => $asset['id'],
            'schedule_name' => 'Monthly PM',
        ])['data'];

        $wo = $this->service->createWorkOrder([
            'asset_id' => $asset['id'],
            'schedule_id' => $schedule['id'],
            'planned_date' => '2025-12-01',
        ])['data'];

        $completed = $this->service->completeWorkOrder($wo['id'], [
            'parts' => [
                ['product_id' => 1, 'qty' => 2, 'warehouse_id' => 1, 'branch_id' => 1],
            ],
        ])['data'];

        $this->assertEquals('completed', $completed['status']);
        $bin = $this->db->table('stock_bins')->where('product_id', 1)->where('branch_id', 1)->get()->getRowArray();
        $this->assertEquals(3.0, (float) $bin['on_hand_qty']);
    }

    private function seedStock(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 1, 'name' => 'WH', 'branch_id' => 1, 'status' => 'active', 'created_at' => $now]);
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
