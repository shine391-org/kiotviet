<?php

namespace Tests\Services;

use App\Services\Taxes\RegionalTaxService;
use App\Repositories\Taxes\RegionalTaxRuleRepository;
use App\Repositories\Taxes\EInvoiceLogRepository;
use App\Validators\RegionalTaxValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: RegionalTaxService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class RegionalTaxServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private RegionalTaxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $repo = new RegionalTaxRuleRepository(null, $this->db);
        $this->service = new RegionalTaxService($repo, new EInvoiceLogRepository(null, $this->db), new RegionalTaxValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_rule_and_calculates_tax()
    {
        $this->service->setRule(['country' => 'VN', 'rule_json' => ['gst_rate' => 10, 'surcharge_rate' => 2]]);
        $res = $this->service->preview([
            'country' => 'VN',
            'lines' => [
                ['amount' => 100, 'tax_rate' => 0],
            ],
        ])['data'];

        $this->assertEquals(12.0, $res['total_tax']);
        $this->assertEquals(10.0, $res['lines'][0]['applied_rate']);
    }

    /** @test */
    public function it_logs_einvoice_stub()
    {
        $log = $this->service->logEInvoice(['invoice_id' => 1, 'payload' => ['any' => 'thing']])['data'];
        $this->assertEquals('queued', $log['status']);
    }
}
