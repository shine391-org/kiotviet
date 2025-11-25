<?php

namespace Tests\Services;

use App\Repositories\OrderStatusLogs\OrderStatusLogRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\SupportingSchemaTrait;

/** @agent-test: OrderStatusLogRepository @agent-pattern: Standard repository test */
class OrderStatusLogRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use SupportingSchemaTrait;

    protected $db;
    private OrderStatusLogRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSupportingSchema();
        $this->repo = new OrderStatusLogRepository(null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
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
