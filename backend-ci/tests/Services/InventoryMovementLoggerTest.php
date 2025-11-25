<?php

namespace Tests\Services;

use App\Repositories\Inventory\InventoryMovementRepository;
use App\Services\Inventory\InventoryMovementLogger;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\SupportingSchemaTrait;

/** @agent-test: InventoryMovementLogger @agent-pattern: Standard service test */
class InventoryMovementLoggerTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use SupportingSchemaTrait;

    protected $db;
    private InventoryMovementLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSupportingSchema();
        $repo = new InventoryMovementRepository(null, $this->db);
        $this->logger = new InventoryMovementLogger($repo);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_logs_inventory_movement()
    {
        $row = $this->logger->log(
            branchId: 1,
            productId: 10,
            variantId: null,
            type: 'sale',
            quantity: -2,
            referenceType: 'order',
            referenceId: 99,
            notes: 'Test log',
            createdBy: 1
        );

        $this->assertNotEmpty($row['id']);
        $stored = $this->db->table('inventory_movements')->where('id', $row['id'])->get()->getRowArray();
        $this->assertEquals(-2, (float) $stored['quantity']);
        $this->assertEquals('order', $stored['reference_type']);
    }
}
