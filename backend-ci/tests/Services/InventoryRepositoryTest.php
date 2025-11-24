<?php

namespace Tests\Services;

use App\Repositories\Inventory\InventoryRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\InventoryStockSchemaTrait;

/** @agent-test: InventoryRepository @agent-pattern: Stock adjust/reserve */
class InventoryRepositoryTest extends CIUnitTestCase
{
    use InventoryStockSchemaTrait;

    protected $db;
    private InventoryRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite extension is required for fast inventory repository tests.');
        }
        $config = config('Database');
        $config->tests = [
            'DBDriver'    => 'SQLite3',
            'database'    => ':memory:',
            'DBPrefix'    => 'db_',
            'foreignKeys' => true,
            'DBDebug'     => true,
        ];
        $config->defaultGroup = 'tests';

        $this->db = Database::connect('tests', false);
        $this->resetInventoryStockSchema();
        $this->repo = new InventoryRepository($this->db);
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
