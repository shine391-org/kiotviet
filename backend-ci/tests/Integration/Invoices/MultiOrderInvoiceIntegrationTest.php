<?php

namespace Tests\Integration\Invoices;

use App\Services\Invoices\InvoiceService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\InvoiceSchemaTrait;

/**
 * @agent-test: Multi-order invoice integration
 * @agent-pattern: MySQL-only test with DevDatabaseTrait
 */
class MultiOrderInvoiceIntegrationTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use InvoiceSchemaTrait;

    protected InvoiceService $invoices;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetInvoiceSchema();

        $this->seedLookup();
        $this->invoices = service('invoiceService');
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function multi_order_invoice_generation(): void
    {
        $orderIds = [];
        $expected = 0;
        for ($i = 1; $i <= 3; $i++) {
            $total = $i * 1000000;
            $this->db->table('orders')->insert([
                'customer_id' => 1,
                'branch_id' => 1,
                'order_date' => date('Y-m-d'),
                'status' => 'completed',
                'total' => $total,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $orderIds[] = (int) $this->db->insertID();
            $expected += $total;
        }

        $res = $this->invoices->generateFromOrders([
            'order_ids' => $orderIds,
            'branch_id' => 1,
            'vat_rate' => 0.1,
            'issue_date' => date('Y-m-d'),
        ]);

        $data = $res['data'];
        $this->assertEquals($expected, (float) $data['subtotal']);
        $this->assertEquals(round($expected * 0.1, 2), (float) $data['vat_amount']);
        $this->assertEquals($expected + round($expected * 0.1, 2), (float) $data['total']);
        $this->assertCount(3, $data['orders']);
    }

    private function seedLookup(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'ABC', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
    }
}
