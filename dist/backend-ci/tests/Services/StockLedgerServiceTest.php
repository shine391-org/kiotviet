<?php

namespace Tests\Services;

use App\Services\Inventory\StockLedgerService;
use App\Repositories\Inventory\StockLedgerRepository;
use App\Repositories\Inventory\StockBinRepository;
use App\Validators\StockLedgerValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/** @agent-test: StockLedgerService @agent-pattern: Service test with DevDatabaseTrait */
class StockLedgerServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private StockLedgerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $ledgerRepo = new StockLedgerRepository(null, $this->db);
        $binRepo = new StockBinRepository(null, $this->db);
        $this->service = new StockLedgerService($ledgerRepo, $binRepo, new StockLedgerValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_records_ledger_and_updates_bin()
    {
        $res = $this->service->record([
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'test',
            'reference_id' => 10,
            'qty_delta' => 5,
        ]);
        $this->assertTrue($res['success']);

        $bin = $this->db->table('stock_bins')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(5.0, (float) $bin['on_hand_qty']);

        // idempotent
        $res2 = $this->service->record([
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'test',
            'reference_id' => 10,
            'qty_delta' => 5,
        ]);
        $this->assertEquals($res['data']['id'], $res2['data']['id']);
    }
}
