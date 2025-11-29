<?php

namespace Tests\Services;

use App\Services\Inventory\StockReconciliationService;
use App\Repositories\Inventory\StockReconciliationRepository;
use App\Repositories\Inventory\StockBinRepository;
use App\Services\Inventory\StockLedgerService;
use App\Validators\StockReconciliationValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/** @agent-test: StockReconciliationService @agent-pattern: Service test with DevDatabaseTrait */
class StockReconciliationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private StockReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBase();
        $repo = new StockReconciliationRepository(null, null, $this->db);
        $binRepo = new StockBinRepository(null, $this->db);
        $ledgerService = new StockLedgerService(null, $binRepo, null);
        $this->service = new StockReconciliationService($repo, new StockReconciliationValidator(), $ledgerService, $binRepo);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_approves_variance()
    {
        $res = $this->service->create([
            'branch_id' => 1,
            'items' => [
                ['product_id' => 1, 'counted_qty' => 8],
            ],
        ]);
        $this->assertTrue($res['success']);
        $id = $res['data']['id'];

        $approved = $this->service->approve($id, ['approved_by' => 9]);
        $this->assertEquals('approved', $approved['data']['status']);

        $bin = $this->db->table('stock_bins')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(8.0, (float) $bin['on_hand_qty']);
    }

    /** @test */
    public function it_rejects_unapproved_states_only()
    {
        $res = $this->service->create([
            'branch_id' => 1,
            'items' => [
                ['product_id' => 1, 'counted_qty' => 5],
            ],
        ]);
        $id = $res['data']['id'];
        $rej = $this->service->reject($id, []);
        $this->assertEquals('rejected', $rej['data']['status']);
    }

    private function seedBase(): void
    {
        $this->db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 5,
            'reserved_qty' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
