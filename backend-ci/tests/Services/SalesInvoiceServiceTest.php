<?php

namespace Tests\Services;

use App\Services\Accounting\COAService;
use App\Services\Accounting\SalesInvoiceService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: SalesInvoiceService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class SalesInvoiceServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private SalesInvoiceService $service;
    private int $debitAccountId;
    private int $creditAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetAccounts();
        $this->seedAccounts();
        $this->seedCustomer();
        $this->seedCustomer();
        
        $invoices = new \App\Models\SalesInvoiceModel($this->db);
        $items = new \App\Models\SalesInvoiceItemModel($this->db);
        $taxes = new \App\Models\SalesInvoiceTaxModel($this->db);
        $schedules = new \App\Models\PaymentScheduleModel($this->db);

        $repo = new \App\Repositories\Accounting\SalesInvoiceRepository(
            $invoices, $items, $taxes, $schedules, $this->db
        );
        $this->service = new SalesInvoiceService($repo);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_totals_and_creates_invoice()
    {
        $payload = $this->samplePayload();
        $create = $this->service->create($payload);

        $this->assertTrue($create['success']);
        $invoice = $create['data'];
        $this->assertEquals(1000.0, $invoice['total']);
        $this->assertEquals(100.0, $invoice['taxes_total']);
        $this->assertEquals(1100.0, $invoice['grand_total']);
        $this->assertCount(1, $invoice['schedules']);
    }

    /** @test */
    public function it_submits_and_posts_gl_entries()
    {
        $payload = $this->samplePayload();
        $invoice = $this->service->create($payload)['data'];

        $submitted = $this->service->submit($invoice['id']);
        $this->assertEquals('submitted', $submitted['data']['status']);

        $entries = $this->db->table('gl_entries')->where('reference_type', 'sales_invoice')->get()->getResultArray();
        $this->assertCount(2, $entries);

        $cancelled = $this->service->cancel($invoice['id']);
        $this->assertEquals('cancelled', $cancelled['data']['status']);

        $entriesAfter = $this->db->table('gl_entries')->where('reference_id', $invoice['id'])->get()->getResultArray();
        $this->assertCount(4, $entriesAfter); // original + reversal
    }

    /** @test */
    public function it_rejects_unbalanced_schedule()
    {
        $this->expectException(\InvalidArgumentException::class);
        $payload = $this->samplePayload();
        $payload['schedules'] = [
            ['due_date' => '2025-01-10', 'amount' => 500],
        ];
        $this->service->create($payload);
    }

    private function samplePayload(): array
    {
        return [
            'customer_id' => 1,
            'posting_date' => '2025-01-01',
            'due_date' => '2025-01-15',
            'items' => [
                ['product_id' => 1, 'description' => 'Item A', 'quantity' => 2, 'rate' => 500],
            ],
            'taxes' => [['tax_name' => 'VAT', 'rate_percent' => 10]],
            'rounding_adjustment' => 0,
            'debit_account_id' => $this->debitAccountId,
            'credit_account_id' => $this->creditAccountId,
        ];
    }

    private function seedAccounts(): void
    {
        $coa = new COAService();
        $asset = $coa->create(['code' => '1000', 'name' => 'Assets', 'account_type' => 'asset', 'is_group' => true])['data'];
        $income = $coa->create(['code' => '4000', 'name' => 'Income', 'account_type' => 'income', 'is_group' => true])['data'];
        $this->debitAccountId = $coa->create([
            'code' => '1100',
            'name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'parent_id' => $asset['id'],
        ])['data']['id'];
        $this->creditAccountId = $coa->create([
            'code' => '4100',
            'name' => 'Sales',
            'account_type' => 'income',
            'parent_id' => $income['id'],
        ])['data']['id'];
    }

    private function resetAccounts(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach (['gl_entries', 'chart_of_accounts'] as $table) {
            if ($this->db->tableExists($table)) {
                $this->db->table($table)->truncate();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function seedCustomer(): void
    {
        $this->db->table('customers')->insert([
            'id' => 1,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '1234567890',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
