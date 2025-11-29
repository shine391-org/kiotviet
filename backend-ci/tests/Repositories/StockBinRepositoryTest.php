<?php

namespace Tests\Repositories;

use App\Repositories\Inventory\StockBinRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/** @agent-test: StockBinRepository @agent-pattern: Repository test with locking */
class StockBinRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private StockBinRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->repo = new StockBinRepository(null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_adjusts_bin_and_prevents_negative()
    {
        $bin = $this->repo->adjust(1, null, 1, null, 5);
        $this->assertEquals(5.0, (float) $bin['on_hand_qty']);

        $bin = $this->repo->adjust(1, null, 1, null, -2);
        $this->assertEquals(3.0, (float) $bin['on_hand_qty']);

        $this->expectException(\RuntimeException::class);
        $this->repo->adjust(1, null, 1, null, -10);
    }
}
