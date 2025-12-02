<?php

namespace Tests\Services;

use App\Services\Taxes\WithholdingAdvancedService;
use App\Repositories\Taxes\TaxCertificateRepository;
use App\Validators\RegionalTaxValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: WithholdingAdvancedService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class WithholdingAdvancedServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private WithholdingAdvancedService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->service = new WithholdingAdvancedService(
            new TaxCertificateRepository(null, $this->db),
            new RegionalTaxValidator()
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_certificate_with_withheld_amount()
    {
        $cert = $this->service->createCertificate([
            'country' => 'VN',
            'party_type' => 'supplier',
            'party_id' => 1,
            'base_amount' => 1000,
            'withheld_rate' => 10,
            'issue_date' => '2025-12-01',
        ])['data'];

        $this->assertEquals(100.0, (float) $cert['withheld_amount']);
        $this->assertStringStartsWith('CERT-VN', $cert['certificate_number']);
    }
}
