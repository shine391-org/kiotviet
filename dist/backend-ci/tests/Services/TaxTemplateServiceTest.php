<?php

namespace Tests\Services;

use App\Services\Taxes\TaxTemplateService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: TaxTemplateService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class TaxTemplateServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private TaxTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new TaxTemplateService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_tax_with_items()
    {
        $tpl = $this->service->create([
            'name' => 'VAT10',
            'is_inclusive' => false,
            'items' => [
                ['tax_name' => 'VAT', 'rate_percent' => 10],
            ],
        ])['data'];

        $applied = $this->service->apply($tpl['id'], 1000);
        $this->assertEquals(100.0, $applied['tax_total']);
        $this->assertEquals(1100.0, $applied['grand_total']);
    }

    /** @test */
    public function it_supports_inclusive()
    {
        $tpl = $this->service->create([
            'name' => 'INC',
            'is_inclusive' => true,
            'rate_percent' => 5,
            'items' => [],
        ])['data'];

        $applied = $this->service->apply($tpl['id'], 100);
        $this->assertEquals(0.0, $applied['tax_total']); // inclusive means base already includes
        $this->assertEquals(100.0, $applied['grand_total']);
    }
}
