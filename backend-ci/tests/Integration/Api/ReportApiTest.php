<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Reports API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class ReportApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->setUpAuthToken();
        $this->seedData();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_gl_report_via_api()
    {
        $res = $this->withHeaders($this->jsonHeaders())->get('/api/reports/gl');
        $res->assertStatus(200);
    }

    /** @test */
    public function it_returns_stock_balance_via_api()
    {
        $res = $this->withHeaders($this->jsonHeaders())->get('/api/reports/stock-balance');
        $res->assertStatus(200);
    }

    private function seedData(): void
    {
        $db = $this->db;
        $now = date('Y-m-d H:i:s');
        $db->table('chart_of_accounts')->insertBatch([
            ['id' => 1, 'code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'created_at' => $now],
            ['id' => 2, 'code' => '2000', 'name' => 'Revenue', 'account_type' => 'income', 'created_at' => $now],
        ]);
        $db->table('gl_entries')->insert([
            'posting_date' => '2025-01-05',
            'account_id' => 1,
            'debit' => 100,
            'credit' => 0,
            'currency' => 'VND',
        ]);
        $db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 5,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
