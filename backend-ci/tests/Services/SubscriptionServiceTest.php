<?php

namespace Tests\Services;

use App\Repositories\Subscriptions\SubscriptionCycleRepository;
use App\Repositories\Subscriptions\SubscriptionRepository;
use App\Services\Orders\OrderService;
use App\Services\Subscriptions\SubscriptionService;
use App\Validators\SubscriptionValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\SubscriptionSchemaTrait;

/**
 * @agent-test: SubscriptionService
 * @agent-pattern: Service test with DevDatabaseTrait + idempotent cycles
 */
class SubscriptionServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use SubscriptionSchemaTrait;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSubscriptionSchema();
        $this->seedBranch();
        $this->seedProduct();

        $repo = new SubscriptionRepository(null, $this->db);
        $cycleRepo = new SubscriptionCycleRepository(null, $this->db);
        $orderService = new OrderService();
        $this->service = new SubscriptionService($repo, $cycleRepo, $orderService, new SubscriptionValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_create_and_pause_resume(): void
    {
        $sub = $this->service->create([
            'customer_id' => null,
            'template_id' => null,
            'plan_name' => 'Weekly Box',
            'interval_days' => 7,
        ]);
        $this->assertTrue($sub['success']);
        $id = $sub['data']['id'];

        $paused = $this->service->pause($id);
        $this->assertSame('paused', $paused['data']['status']);

        $resumed = $this->service->resume($id);
        $this->assertSame('active', $resumed['data']['status']);
    }

    public function test_run_due_creates_one_order_per_cycle(): void
    {
        $sub = $this->service->create([
            'plan_name' => 'Weekly',
            'interval_days' => 7,
            'next_run_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);

        $result = $this->service->runDue(date('Y-m-d H:i:s'));
        $this->assertSame(1, $result['processed']);

        // running again same timestamp should skip via idempotency
        $result2 = $this->service->runDue(date('Y-m-d H:i:s'));
        $this->assertSame(0, $result2['processed']);

        $orders = $this->db->table('orders')->get()->getResultArray();
        $this->assertCount(1, $orders);
    }

    private function seedBranch(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'Main',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedProduct(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'SUB1',
            'name' => 'Subscription Product',
            'selling_price' => 10,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
