<?php

namespace Tests\Services;

use App\Repositories\OrderStatusLogs\OrderStatusLogRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\SupportingSchemaTrait;

/** @agent-test: OrderStatusLogRepository @agent-pattern: Standard repository test */
class OrderStatusLogRepositoryTest extends CIUnitTestCase
{
    use SupportingSchemaTrait;

    protected $db;
    private OrderStatusLogRepository $repo;

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
        $this->repo = new OrderStatusLogRepository(null, $this->db);
    }

    /** @test */
    public function it_creates_status_log()
    {
        // seed order
        $this->db->table('orders')->insert([
            'status' => 'draft',
            'branch_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $orderId = (int) $this->db->insertID();

        $row = $this->repo->create($orderId, 'draft', 'confirmed', 1, 'Auto confirm');

        $this->assertNotEmpty($row['id']);
        $this->assertEquals('confirmed', $row['to_status']);

        $stored = $this->db->table('order_status_logs')->where('id', $row['id'])->get()->getRowArray();
        $this->assertEquals($orderId, (int) $stored['order_id']);
    }
}
