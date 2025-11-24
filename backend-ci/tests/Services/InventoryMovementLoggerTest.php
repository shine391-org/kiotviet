<?php

namespace Tests\Services;

use App\Repositories\Inventory\InventoryMovementRepository;
use App\Services\Inventory\InventoryMovementLogger;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\SupportingSchemaTrait;

/** @agent-test: InventoryMovementLogger @agent-pattern: Standard service test */
class InventoryMovementLoggerTest extends CIUnitTestCase
{
    use SupportingSchemaTrait;

    protected $db;
    private InventoryMovementLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        if (extension_loaded('sqlite3')) {
            $config->tests = [
                'DBDriver'    => 'SQLite3',
                'database'    => ':memory:',
                'DBPrefix'    => 'db_',
                'foreignKeys' => true,
                'DBDebug'     => true,
            ];
        } else {
            $config->tests = [
                'hostname' => '127.0.0.1',
                'port' => 3307,
                'username' => 'lanocrm_user',
                'password' => 'KP7n4RjcDbedSE2W8GgA',
                'database' => 'lanocrm_test',
                'DBDriver' => 'MySQLi',
                'DBPrefix' => '',
                'DBDebug' => true,
                'charset' => 'utf8mb4',
                'DBCollat' => 'utf8mb4_general_ci',
            ];
        }
        $config->defaultGroup = 'tests';

        $this->db = Database::connect('tests', false);
        $this->resetSupportingSchema();
        $repo = new InventoryMovementRepository(null, $this->db);
        $this->logger = new InventoryMovementLogger($repo);
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
