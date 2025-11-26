<?php

namespace Tests\Services;

use App\Repositories\Invoices\InvoiceRepository;
use App\Services\Invoices\InvoicePDFGenerator;
use App\Services\Invoices\InvoiceService;
use App\Services\Invoices\VATCalculator;
use App\Validators\InvoiceValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: InvoiceService unified MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait + InvoiceSchemaTrait (MySQL-only)
 */
class InvoiceServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        
        // Use InvoiceSchemaTrait for comprehensive schema
        $this->resetCompleteSchema();
        $this->seedLookup();

        $repo = new InvoiceRepository(null, null, $this->db);
        $validator = new InvoiceValidator();
        $vat = new VATCalculator();
        $pdf = new InvoicePDFGenerator();
        $this->service = new InvoiceService($repo, $validator, null, $vat, $pdf);
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_invoice_and_links_orders()
    {
        $order1 = $this->seedOrder(1, 1, 150000);
        $order2 = $this->seedOrder(1, 1, 50000);

        $res = $this->service->create([
            'customer_id' => 1,
            'branch_id' => 1,
            'issue_date' => '2025-11-24',
            'vat_rate' => 0.1,
            'order_ids' => [$order1, $order2],
            'created_by' => 1,
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals(200000.0, $res['data']['subtotal']);
        $this->assertEquals(20000.0, $res['data']['vat_amount']);
        $this->assertEquals(220000.0, $res['data']['total']);
        $this->assertEquals('HD-1-0001', $res['data']['invoice_number']);
        $this->assertCount(2, $res['data']['orders']);
    }

    /** @test */
    public function it_prevents_duplicate_order_across_invoices()
    {
        $orderId = $this->seedOrder(1, 1, 100000);

        $this->service->create([
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [$orderId],
            'vat_rate' => 0.1,
            'created_by' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [$orderId],
            'vat_rate' => 0.1,
            'created_by' => 1,
        ]);
    }

    /** @test */
    public function vat_calculation_rounds_correctly()
    {
        $orderId = $this->seedOrder(1, 1, 33333.33);

        $res = $this->service->create([
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [$orderId],
            'vat_rate' => 0.08,
            'created_by' => 1,
        ]);

        $this->assertEquals(2666.67, $res['data']['vat_amount']);
        $this->assertEquals(36000.0, $res['data']['total']);
    }

    private function seedOrder(int $customerId, int $branchId, float $total): int
    {
        $row = [
            'customer_id' => $customerId,
            'branch_id' => $branchId,
            'total' => $total,
            'status' => 'confirmed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('orders')->insert($row);
        return (int) $this->db->insertID();
    }

    private function seedLookup(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'ACME', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Branch 1', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
    }
}
