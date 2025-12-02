<?php

namespace Tests\Services;

use App\Repositories\Dashboard\DashboardRepository;
use App\Services\Dashboard\DashboardService;
use App\Validators\DashboardValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: DashboardService
 * @agent-pattern: Service test with DevDatabaseTrait + schema cleanup
 */
class DashboardServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedData();
        $this->service = new DashboardService(
            new DashboardRepository($this->db),
            new DashboardValidator()
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_kpi_metrics()
    {
        $result = $this->service->kpiToday([]);
        $this->assertTrue($result['success']);

        $kpi = $result['data'];
        $this->assertEquals(700.0, $kpi['revenue']);
        $this->assertEquals(300.0, $kpi['returns']);
        $this->assertEquals(400.0, $kpi['netRevenue']);
        $this->assertEquals(100.0, round($kpi['netChange'], 2));
    }

    /** @test */
    public function it_builds_revenue_chart()
    {
        $result = $this->service->revenueChart(['range' => 'today', 'period' => 'day']);
        $this->assertTrue($result['success']);

        $chart = $result['chart'];
        $this->assertIsArray($chart['labels']);
        $this->assertGreaterThan(0, $chart['total']);
        $this->assertEquals(700.0, $chart['total']);
    }

    /** @test */
    public function it_returns_top_products()
    {
        $result = $this->service->topProducts([]);
        $this->assertTrue($result['success']);

        $items = $result['items'];
        $this->assertCount(1, $items);
        $this->assertEquals(700.0, $items[0]['value']);
    }

    /** @test */
    public function it_return_recent_activities()
    {
        $result = $this->service->activities(['limit' => 2]);
        $this->assertTrue($result['success']);

        $items = $result['items'];
        $this->assertCount(2, $items);
        $this->assertEquals(150.0, $items[0]['amount']);
    }

    private function seedData(): void
    {
        $now = date('Y-m-d H:i:s');
        $productId = (int) $this->db->table('products')->insert([
            'product_type' => 'goods',
            'code' => 'TEST-001',
            'name' => 'Test Product',
            'slug' => 'test-product',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $customerId = (int) $this->db->table('customers')->insert([
            'organization_id' => 1,
            'name' => 'Demo Customer',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->table('users')->insert([
            'username' => 'tester',
            'email' => 'tester@localhost',
            'password' => password_hash('P@ssw0rd', PASSWORD_DEFAULT),
            'full_name' => 'Tester',
            'branch_id' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $today = date('Y-m-d');
        $lastMonth = date('Y-m-d', strtotime('first day of last month'));

        $order1 = $this->insertOrder($customerId, 'completed', $today, 500.0, 1, $now);
        $this->insertOrderItem($order1, $productId, 500.0, 1.0, 250.0, $now);

        $order2 = $this->insertOrder($customerId, 'cancelled', $today, 300.0, 1, $now);
        $this->insertOrderItem($order2, $productId, 300.0, 1.0, 150.0, $now);

        $order3 = $this->insertOrder($customerId, 'processing', $today, 200.0, 1, $now);
        $this->insertOrderItem($order3, $productId, 200.0, 1.0, 100.0, $now);

        $order4 = $this->insertOrder($customerId, 'completed', $lastMonth, 200.0, 1, $now);
        $this->insertOrderItem($order4, $productId, 200.0, 1.0, 100.0, $now);

        $this->db->table('cash_transactions')->insertBatch([
            [
                'account_name' => 'Quỹ demo',
                'amount' => 150.0,
                'bank_account' => null,
                'branch_id' => 1,
                'category' => 'sales',
                'created_at' => $now,
                'updated_at' => $now,
                'transaction_date' => $today,
                'type' => 'RECEIPT',
                'status' => 'approved',
                'created_by' => 1,
                'created_by_name' => 'Tester',
                'payment_method' => 'cash',
            ],
            [
                'account_name' => 'Quỹ demo',
                'amount' => 120.0,
                'bank_account' => null,
                'branch_id' => 1,
                'category' => 'refund',
                'created_at' => $now,
                'updated_at' => $now,
                'transaction_date' => $today,
                'type' => 'PAYMENT',
                'status' => 'approved',
                'created_by' => 1,
                'created_by_name' => 'Tester',
                'payment_method' => 'cash',
            ],
        ]);
    }

    private function insertOrder(int $customerId, string $status, string $date, float $total, int $branchId, string $now): int
    {
        $this->db->table('orders')->insert([
            'customer_id' => $customerId,
            'branch_id' => $branchId,
            'status' => $status,
            'order_date' => $date,
            'total' => $total,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->db->insertID();
    }

    private function insertOrderItem(int $orderId, int $productId, float $price, float $qty, float $base, string $now): void
    {
        $this->db->table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity' => $qty,
            'base_price' => $base,
            'final_price' => $price,
            'price_list_id' => null,
            'price_list_name' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
