<?php

namespace Tests\Services;

use App\Services\Accounting\AccountingService;
use App\Services\Accounting\BankReconciliationService;
use App\Services\Accounting\COAService;
use App\Services\Accounting\PaymentEntryService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: BankReconciliationService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class BankReconciliationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private BankReconciliationService $service;
    private PaymentEntryService $payments;
    private int $bankAccountId;
    private int $receivableAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetAccountingTables();
        $this->seedAccounts();
        $accounting = new AccountingService();
        $this->payments = new PaymentEntryService(null, null, $accounting);
        $this->service = new BankReconciliationService(null, null, null, null);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    private function resetAccountingTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('gl_entries')->truncate();
        $this->db->table('chart_of_accounts')->truncate();
        $this->db->table('bank_reconciliations')->truncate();
        $this->db->table('bank_statements')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /** @test */
    public function it_auto_matches_bank_statement_to_payment_entry()
    {
        $payment = $this->payments->create([
            'party_type' => 'customer',
            'party_id' => 1,
            'payment_method' => 'BANK',
            'amount' => 300,
            'debit_account_id' => $this->bankAccountId,
            'credit_account_id' => $this->receivableAccountId,
            'reference_no' => 'REF-123',
            'reference_date' => '2025-06-01',
        ])['data'];
        $this->payments->submit($payment['id']);

        $import = $this->service->import([
            'lines' => [[
                'account_number' => '001',
                'amount' => 300,
                'currency' => 'VND',
                'reference_no' => 'REF-123',
                'reference_date' => '2025-06-01',
                'description' => 'Incoming',
            ]],
        ]);

        $this->assertTrue($import['success']);
        $this->assertCount(1, $import['auto_matched']);

        $reco = $this->db->table('bank_reconciliations')->get()->getRowArray();
        $this->assertEquals('reconciled', $reco['status']);
    }

    private function seedAccounts(): void
    {
        $coa = new COAService();
        $asset = $coa->create(['code' => '9000', 'name' => 'Bank', 'account_type' => 'asset', 'is_group' => true])['data'];
        $liab = $coa->create(['code' => '9100', 'name' => 'AR', 'account_type' => 'liability', 'is_group' => true])['data'];
        $this->bankAccountId = $coa->create(['code' => '9001', 'name' => 'Main Bank', 'account_type' => 'asset', 'parent_id' => $asset['id']])['data']['id'];
        $this->receivableAccountId = $coa->create(['code' => '9101', 'name' => 'AR Control', 'account_type' => 'liability', 'parent_id' => $liab['id']])['data']['id'];
    }
}
