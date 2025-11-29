<?php

namespace Tests\Services;

use App\Services\Accounting\AccountingService;
use App\Services\Accounting\COAService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: AccountingService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class AccountingServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private AccountingService $service;
    private COAService $coa;
    private int $debitAccountId;
    private int $creditAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new AccountingService();
        $this->coa = new COAService();
        $this->seedAccounts();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_posts_balanced_journal()
    {
        $result = $this->service->postJournal([
            'posting_date' => '2025-01-01',
            'entries' => [
                ['account_id' => $this->debitAccountId, 'debit' => 100, 'credit' => 0, 'remarks' => 'Cash in'],
                ['account_id' => $this->creditAccountId, 'debit' => 0, 'credit' => 100, 'remarks' => 'Sales'],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);

        $sumDebit = $this->db->table('gl_entries')->selectSum('debit')->get()->getRow()->debit;
        $sumCredit = $this->db->table('gl_entries')->selectSum('credit')->get()->getRow()->credit;
        $this->assertEquals($sumDebit, $sumCredit);
    }

    /** @test */
    public function it_rejects_unbalanced_entries()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->postJournal([
            'posting_date' => '2025-01-02',
            'entries' => [
                ['account_id' => $this->debitAccountId, 'debit' => 50],
                ['account_id' => $this->creditAccountId, 'credit' => 40],
            ],
        ]);
    }

    /** @test */
    public function it_requires_existing_account()
    {
        $this->expectException(\RuntimeException::class);
        $this->service->postJournal([
            'posting_date' => '2025-01-03',
            'entries' => [
                ['account_id' => 9999, 'debit' => 10],
                ['account_id' => $this->creditAccountId, 'credit' => 10],
            ],
        ]);
    }

    private function seedAccounts(): void
    {
        $assetGroup = $this->coa->create([
            'code' => '1000',
            'name' => 'Assets',
            'account_type' => 'asset',
            'is_group' => true,
        ])['data'];

        $incomeGroup = $this->coa->create([
            'code' => '4000',
            'name' => 'Income',
            'account_type' => 'income',
            'is_group' => true,
        ])['data'];

        $cash = $this->coa->create([
            'code' => '1100',
            'name' => 'Cash',
            'account_type' => 'asset',
            'parent_id' => $assetGroup['id'],
        ])['data'];

        $sales = $this->coa->create([
            'code' => '4100',
            'name' => 'Sales',
            'account_type' => 'income',
            'parent_id' => $incomeGroup['id'],
        ])['data'];

        $this->debitAccountId = $cash['id'];
        $this->creditAccountId = $sales['id'];
    }
}
