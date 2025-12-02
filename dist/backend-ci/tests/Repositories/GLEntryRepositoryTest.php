<?php

namespace Tests\Repositories;

use App\Repositories\Accounting\GLEntryRepository;
use App\Services\Accounting\COAService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: GLEntryRepository MySQL testing
 * @agent-pattern: Repository test with DevDatabaseTrait
 */
class GLEntryRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private GLEntryRepository $repo;
    private int $cashId;
    private int $salesId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->repo = new GLEntryRepository();
        $this->seedAccounts();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_filters_by_account_and_date_range()
    {
        $this->repo->createBatch([
            [
                'posting_date' => '2025-01-01',
                'account_id' => $this->cashId,
                'debit' => 100,
                'credit' => 0,
            ],
            [
                'posting_date' => '2025-02-01',
                'account_id' => $this->salesId,
                'debit' => 0,
                'credit' => 100,
            ],
        ]);

        $rows = $this->repo->listByFilters([
            'account_id' => $this->salesId,
            'from_date' => '2025-02-01',
            'to_date' => '2025-02-28',
        ]);

        $this->assertCount(1, $rows);
        $this->assertEquals($this->salesId, $rows[0]['account_id']);
    }

    private function seedAccounts(): void
    {
        $coa = new COAService();
        $asset = $coa->create(['code' => '1000', 'name' => 'Assets', 'account_type' => 'asset', 'is_group' => true])['data'];
        $income = $coa->create(['code' => '4000', 'name' => 'Income', 'account_type' => 'income', 'is_group' => true])['data'];
        $this->cashId = $coa->create(['code' => '1100', 'name' => 'Cash', 'account_type' => 'asset', 'parent_id' => $asset['id']])['data']['id'];
        $this->salesId = $coa->create(['code' => '4100', 'name' => 'Sales', 'account_type' => 'income', 'parent_id' => $income['id']])['data']['id'];
    }
}
