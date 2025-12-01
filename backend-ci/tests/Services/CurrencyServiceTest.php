<?php

namespace Tests\Services;

use App\Services\Accounting\CurrencyService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CurrencyService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class CurrencyServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetExchangeRates();
        $this->service = new CurrencyService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    private function resetExchangeRates(): void
    {
        if ($this->db->tableExists('exchange_rates')) {
            $this->db->table('exchange_rates')->truncate();
        }
    }

    /** @test */
    public function it_selects_latest_rate_by_date()
    {
        $this->service->createRate(['currency' => 'USD', 'rate' => 23000, 'valid_from' => '2025-01-01']);
        $this->service->createRate(['currency' => 'USD', 'rate' => 24000, 'valid_from' => '2025-02-01']);

        $converted = $this->service->convert(1, 'USD', 'VND', '2025-01-15');
        $this->assertEquals(23000.0, $converted);

        $convertedNew = $this->service->convert(1, 'USD', 'VND', '2025-02-10');
        $this->assertEquals(24000.0, $convertedNew);
    }

    /** @test */
    public function it_converts_between_non_base_currencies()
    {
        $this->service->createRate(['currency' => 'USD', 'rate' => 24000, 'valid_from' => '2025-02-01']);
        $this->service->createRate(['currency' => 'EUR', 'rate' => 26000, 'valid_from' => '2025-02-01']);

        $amount = $this->service->convert(10, 'EUR', 'USD', '2025-02-10');
        $this->assertEqualsWithDelta(10 * 26000 / 24000, $amount, 0.01);
    }

    /** @test */
    public function it_throws_when_missing_rate()
    {
        $this->expectException(\RuntimeException::class);
        $this->service->convert(1, 'USD', 'VND', '2025-01-01');
    }
}
