<?php

namespace Tests\Services;

use App\Repositories\Taxes\TaxTemplateRepository;
use App\Services\POS\POSTaxService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: POSTaxService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class POSTaxServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private POSTaxService $service;
    private int $templateId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $repo = new TaxTemplateRepository(null, $this->db);
        $this->templateId = $repo->create([
            'name' => 'VAT10',
            'rate_percent' => 10,
            'rounding_rule' => 'nearest',
            'is_inclusive' => 0,
            'status' => 'active',
        ])['id'];
        $this->service = new POSTaxService($repo, null);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_tax_and_rounding()
    {
        $res = $this->service->apply($this->templateId, 100.0);
        $this->assertEquals(10.0, $res['tax_total']);
        $this->assertEquals(110.0, $res['grand_total']);
    }
}
