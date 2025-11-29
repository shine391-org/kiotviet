<?php

namespace Tests\Services;

use App\Services\Accounting\COAService;
use App\Services\Accounting\PurchaseInvoiceService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: PurchaseInvoiceService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class PurchaseInvoiceServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private PurchaseInvoiceService $service;
    private int $debitAccountId;
    private int $creditAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->seedAccounts();
        $this->service = new PurchaseInvoiceService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_purchase_invoice_and_posts_gl_on_submit()
    {
        $payload = $this->samplePayload();
        $invoice = $this->service->create($payload)['data'];

        $this->assertEquals(1000.0, $invoice['total']);
        $this->assertEquals(100.0, $invoice['taxes_total']);
        $this->assertEquals(1100.0, $invoice['grand_total']);

        $submitted = $this->service->submit($invoice['id']);
        $this->assertEquals('submitted', $submitted['data']['status']);

        $entries = $this->db->table('gl_entries')->where('reference_type', 'purchase_invoice')->get()->getResultArray();
        $this->assertCount(2, $entries);

        $cancelled = $this->service->cancel($invoice['id']);
        $this->assertEquals('cancelled', $cancelled['data']['status']);
        $entriesAfter = $this->db->table('gl_entries')->where('reference_id', $invoice['id'])->get()->getResultArray();
        $this->assertCount(4, $entriesAfter);
    }

    /** @test */
    public function it_validates_supplier()
    {
        $this->expectException(\InvalidArgumentException::class);
        $payload = $this->samplePayload();
        $payload['supplier_id'] = 0;
        $this->service->create($payload);
    }

    private function samplePayload(): array
    {
        return [
            'supplier_id' => 5,
            'posting_date' => '2025-03-01',
            'due_date' => '2025-03-10',
            'items' => [
                ['product_id' => 1, 'description' => 'Raw Material', 'quantity' => 2, 'rate' => 500],
            ],
            'taxes' => [['tax_name' => 'VAT', 'rate_percent' => 10]],
            'debit_account_id' => $this->debitAccountId,
            'credit_account_id' => $this->creditAccountId,
        ];
    }

    private function seedAccounts(): void
    {
        $coa = new COAService();
        $asset = $coa->create(['code' => '2000', 'name' => 'Inventory', 'account_type' => 'asset', 'is_group' => true])['data'];
        $liab = $coa->create(['code' => '3000', 'name' => 'Liabilities', 'account_type' => 'liability', 'is_group' => true])['data'];
        $this->debitAccountId = $coa->create(['code' => '2100', 'name' => 'Stock in', 'account_type' => 'asset', 'parent_id' => $asset['id']])['data']['id'];
        $this->creditAccountId = $coa->create(['code' => '3100', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'parent_id' => $liab['id']])['data']['id'];
    }
}
