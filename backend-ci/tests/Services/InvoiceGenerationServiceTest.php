<?php

namespace Tests\Services;

use App\Services\Invoices\InvoiceService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\InvoiceSchemaTrait;

/** @agent-test: Invoice generation @agent-pattern: Service test */
class InvoiceGenerationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use InvoiceSchemaTrait;

    protected $db;
    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->resetInvoiceSchema();
        $this->seedLookup();

        $this->service = new InvoiceService();
    }

    /** @test */
    public function it_generates_invoice_from_completed_orders_same_customer_and_branch()
    {
        $order1 = $this->seedOrder(1, 1, 100000, 'completed');
        $order2 = $this->seedOrder(1, 1, 200000, 'completed');

        $res = $this->service->generateFromOrders([
            'order_ids' => [$order1, $order2],
            'branch_id' => 1,
            'vat_rate' => 0.1,
            'created_by' => 1,
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals(300000.0, $res['data']['subtotal']);
        $this->assertEquals(330000.0, $res['data']['total']);
    }

    /** @test */
    public function it_blocks_mixed_customers()
    {
        $o1 = $this->seedOrder(1, 1, 100000, 'completed');
        $o2 = $this->seedOrder(2, 1, 100000, 'completed');
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generateFromOrders([
            'order_ids' => [$o1, $o2],
            'branch_id' => 1,
            'vat_rate' => 0.1,
        ]);
    }

    private function seedLookup(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'ACME', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('customers')->insert(['id' => 2, 'name' => 'Another', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Branch', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
    }

    private function seedOrder(int $customerId, int $branchId, float $total, string $status): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('orders')->insert([
            'customer_id' => $customerId,
            'branch_id' => $branchId,
            'status' => $status,
            'total' => $total,
            'order_date' => date('Y-m-d'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->db->insertID();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }
}
