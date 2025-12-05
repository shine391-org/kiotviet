<?php

namespace Tests\Services;

use App\Services\Accounting\AccountingService;
use App\Services\Accounting\COAService;
use App\Services\Accounting\PaymentEntryService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Accounting PaymentEntryService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class AccountingPaymentEntryServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private PaymentEntryService $service;
    private int $debitAccountId;
    private int $creditAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetAccountingTables();
        $this->seedAccounts();
        $this->service = new PaymentEntryService(null, null, new AccountingService());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_submits_and_cancels_payment_entry_with_allocations()
    {
        $entry = $this->service->create([
            'party_type' => 'customer',
            'party_id' => 1,
            'payment_method' => 'BANK',
            'amount' => 500,
            'debit_account_id' => $this->debitAccountId,
            'credit_account_id' => $this->creditAccountId,
            'reference_no' => 'PMT-001',
            'reference_date' => '2025-05-01',
            'allocations' => [
                ['reference_type' => 'sales_invoice', 'reference_id' => 10, 'allocated_amount' => 500],
            ],
        ])['data'];

        $this->assertEquals('draft', $entry['status']);
        $this->assertCount(1, $entry['allocations']);

        $submitted = $this->service->submit($entry['id']);
        $this->assertEquals('submitted', $submitted['data']['status']);

        $gl = $this->db->table('gl_entries')->where('reference_type', 'payment_entry')->get()->getResultArray();
        $this->assertCount(2, $gl);

        $cancelled = $this->service->cancel($entry['id']);
        $this->assertEquals('cancelled', $cancelled['data']['status']);

        $glAfter = $this->db->table('gl_entries')->where('reference_id', $entry['id'])->get()->getResultArray();
        $this->assertCount(4, $glAfter);
    }

    private function resetAccountingTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('payment_entry_allocations')->truncate();
        $this->db->table('payment_entries')->truncate();
        $this->db->table('gl_entries')->truncate();
        $this->db->table('chart_of_accounts')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function seedAccounts(): void
    {
        $coa = new COAService();
        $asset = $coa->create(['code' => '7000', 'name' => 'Bank', 'account_type' => 'asset', 'is_group' => true])['data'];
        $liab = $coa->create(['code' => '8000', 'name' => 'Liabilities', 'account_type' => 'liability', 'is_group' => true])['data'];
        $this->debitAccountId = $coa->create(['code' => '7100', 'name' => 'Bank Main', 'account_type' => 'asset', 'parent_id' => $asset['id']])['data']['id'];
        $this->creditAccountId = $coa->create(['code' => '8100', 'name' => 'Receivable Clearing', 'account_type' => 'liability', 'parent_id' => $liab['id']])['data']['id'];
    }
}
