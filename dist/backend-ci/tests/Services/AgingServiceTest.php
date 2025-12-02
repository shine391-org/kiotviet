<?php

namespace Tests\Services;

use App\Services\Accounting\AgingService;
use App\Services\Accounting\CurrencyService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: AgingService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class AgingServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private AgingService $service;
    private CurrencyService $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->currency = new CurrencyService();
        $this->service = new AgingService(null, $this->currency);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_groups_into_buckets_and_converts_currency()
    {
        $this->currency->createRate(['currency' => 'USD', 'rate' => 24000, 'valid_from' => '2025-01-01']);

        $this->db->table('gl_entries')->insert([
            'posting_date' => '2025-01-05',
            'account_id' => 1,
            'party_type' => 'customer',
            'party_id' => 1,
            'debit' => 100,
            'credit' => 0,
            'currency' => 'VND',
        ]);
        $this->db->table('gl_entries')->insert([
            'posting_date' => '2024-11-01',
            'account_id' => 1,
            'party_type' => 'customer',
            'party_id' => 1,
            'debit' => 0,
            'credit' => 50,
            'currency' => 'USD',
        ]);

        $report = $this->service->report([
            'party_type' => 'customer',
            'as_of' => '2025-01-15',
            'base_currency' => 'VND',
        ])['data'];

        $this->assertNotEmpty($report);
        $row = $report[0];
        $this->assertEquals('VND', $row['currency']);
        $this->assertEquals(100.0, $row['bucket_0_30']);
        $this->assertTrue($row['bucket_61_90'] < 0 || $row['bucket_90_plus'] < 0);
    }
}
