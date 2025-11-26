<?php

namespace Tests\Services;

use App\Repositories\Inventory\InventoryRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\InventoryStockSchemaTrait;

/** @agent-test: InventoryRepository @agent-pattern: Stock adjust/reserve */
class InventoryRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use InventoryStockSchemaTrait;

    protected $db;
    private InventoryRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetInventoryStockSchema();
        $this->repo = new InventoryRepository($this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_upserts_and_adjusts_stock()
    {
        $row = $this->repo->adjustStock(1, null, 1, 5);
        $this->assertEquals(5.0, (float) $row['quantity_on_hand']);

        $row2 = $this->repo->adjustStock(1, null, 1, -2);
        $this->assertEquals(3.0, (float) $row2['quantity_on_hand']);
    }

    /** @test */
    public function it_reserves_and_releases()
    {
        $this->repo->adjustStock(1, null, 1, 5);
        $res = $this->repo->reserveStock(1, null, 1, 2);
        $this->assertEquals(2.0, (float) $res['quantity_reserved']);

        $rel = $this->repo->releaseStock(1, null, 1, 1);
        $this->assertEquals(1.0, (float) $rel['quantity_reserved']);
    }
}
