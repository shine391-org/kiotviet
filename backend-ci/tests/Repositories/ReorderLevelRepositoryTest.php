<?php

namespace Tests\Repositories;

use App\Repositories\Inventory\ReorderLevelRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/** @agent-test: ReorderLevelRepository @agent-pattern: Repository test with DevDatabaseTrait */
class ReorderLevelRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ReorderLevelRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->repo = new ReorderLevelRepository(null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_updates_reorder_level()
    {
        $level = $this->repo->create([
            'product_id' => 1,
            'branch_id' => 1,
            'min_level' => 5,
            'max_level' => 12,
            'safety_stock' => 2,
        ]);

        $this->assertNotEmpty($level['id']);
        $this->assertEquals(1, (int) $level['branch_id']);
        $this->assertTrue((bool) $level['is_active']);

        $updated = $this->repo->update($level['id'], ['max_level' => 20, 'is_active' => false]);
        $this->assertEquals(20.0, (float) $updated['max_level']);
        $this->assertFalse((bool) $updated['is_active']);
    }

    /** @test */
    public function it_returns_shortages_with_available_quantities()
    {
        $this->repo->create([
            'product_id' => 2,
            'branch_id' => 1,
            'min_level' => 5,
            'max_level' => 12,
            'safety_stock' => 2,
        ]);
        $this->repo->create([
            'product_id' => 3,
            'branch_id' => 1,
            'min_level' => 5,
            'max_level' => 12,
            'safety_stock' => 2,
        ]);

        $now = date('Y-m-d H:i:s');
        $this->db->table('stock_bins')->insert([
            'product_id' => 2,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 4,
            'reserved_qty' => 1,
            'updated_at' => $now,
        ]);
        $this->db->table('stock_bins')->insert([
            'product_id' => 3,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 15,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);

        $shortages = $this->repo->findShortages([]);
        $this->assertCount(1, $shortages);
        $this->assertEquals(3.0, (float) $shortages[0]['available_qty']);
        $this->assertEquals(4.0, (float) $shortages[0]['on_hand_qty']);
        $this->assertEquals(1.0, (float) $shortages[0]['reserved_qty']);
    }
}
