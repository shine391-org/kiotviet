<?php

namespace Tests\Services;

use App\Services\Orders\OrderQueryService;
use App\Validators\OrderListValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: OrderQueryService unit tests
 * @agent-pattern: Service + validator checks
 */
class OrderQueryServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    protected OrderQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = service('orderQueryService');
        $this->seedOrders();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function validate_filters_rejects_invalid_limit()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new OrderListValidator())->validate(['limit' => 500]);
    }

    /** @test */
    public function list_returns_totals_and_pagination()
    {
        $result = $this->service->list(['page' => 1, 'limit' => 10]);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertEquals(2, $result['pagination']['total']);
        $this->assertEquals(300000.0, (float) $result['totals']['total_amount']);
    }

    private function seedOrders(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'HN', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'Tester', 'phone' => '0909', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('orders')->insertBatch([
            [
                'order_number' => 'ORD-100',
                'customer_id' => 1,
                'branch_id' => 1,
                'order_date' => date('Y-m-d'),
                'status' => 'draft',
                'order_type' => 'shipping',
                'payment_method' => 'CASH',
                'subtotal' => 100000,
                'discount_total' => 0,
                'shipping_fee' => 0,
                'total' => 100000,
                'paid_amount' => 0,
                'debt_amount' => 100000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'order_number' => 'ORD-101',
                'customer_id' => 1,
                'branch_id' => 1,
                'order_date' => date('Y-m-d'),
                'status' => 'completed',
                'order_type' => 'shipping',
                'payment_method' => 'CASH',
                'subtotal' => 200000,
                'discount_total' => 0,
                'shipping_fee' => 0,
                'total' => 200000,
                'paid_amount' => 200000,
                'debt_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
