<?php

namespace Tests\Services;

use App\Services\Reports\ReportService;
use App\Validators\ReportValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: ReportService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class ReportServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ReportService $service;
    private int $glCount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedData($this->db);
        $this->glCount = $this->db->table('gl_entries')->countAllResults();
        $this->service = new ReportService($this->db, new ReportValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_gl_totals()
    {
        $this->assertGreaterThan(0, $this->glCount);
        $res = $this->service->gl(['from_date' => '2025-01-01', 'to_date' => '2025-12-31']);
        $this->assertEquals(600.0, $res['totals']['debit']);
        $this->assertEquals(300.0, $res['totals']['credit']);
    }

    /** @test */
    public function it_calculates_profit_and_loss()
    {
        $res = $this->service->profitLoss([]);
        $this->assertEquals(200.0, $res['data']['net_profit']);
    }

    /** @test */
    public function it_builds_balance_sheet()
    {
        $res = $this->service->balanceSheet([]);
        $this->assertEquals(500.0, $res['data']['asset']);
        $this->assertEquals(0.0, $res['data']['liability']);
    }

    /** @test */
    public function it_calculates_aging()
    {
        $res = $this->service->aging(['party_type' => 'customer', 'as_of_date' => date('Y-m-d')]);
        $total = array_sum($res['data']);
        $this->assertNotEquals(0.0, $total);
    }

    /** @test */
    public function it_lists_stock_balance()
    {
        $res = $this->service->stockBalance([]);
        $this->assertEquals(10.0, $res['total_on_hand']);
    }

    private function seedData($db): void
    {
        $now = date('Y-m-d H:i:s');
        $db->table('chart_of_accounts')->insertBatch([
            ['id' => 1, 'code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'created_at' => $now],
            ['id' => 2, 'code' => '2000', 'name' => 'Revenue', 'account_type' => 'income', 'created_at' => $now],
            ['id' => 3, 'code' => '5000', 'name' => 'Expense', 'account_type' => 'expense', 'created_at' => $now],
        ]);
        if ($db->error()['code'] ?? 0) {
            throw new \RuntimeException('Seed chart_of_accounts failed');
        }
        foreach ([
            ['posting_date' => '2025-01-05', 'account_id' => 1, 'debit' => 200, 'credit' => 0, 'currency' => 'VND'],
            ['posting_date' => '2025-01-06', 'account_id' => 1, 'debit' => 300, 'credit' => 0, 'currency' => 'VND'],
            ['posting_date' => '2025-01-07', 'account_id' => 2, 'debit' => 0, 'credit' => 300, 'party_type' => 'customer', 'party_id' => 1, 'currency' => 'VND'],
            ['posting_date' => '2025-01-08', 'account_id' => 3, 'debit' => 100, 'credit' => 0, 'currency' => 'VND'],
        ] as $row) {
            $db->table('gl_entries')->insert($row);
            $err = $db->error();
            if ($err['code'] ?? 0) {
                throw new \RuntimeException('Seed gl_entries failed: ' . ($err['message'] ?? ''));
            }
        }
        $db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 10,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
    }
}
