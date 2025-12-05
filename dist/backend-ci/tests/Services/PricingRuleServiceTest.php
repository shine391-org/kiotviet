<?php

namespace Tests\Services;

use App\Services\Pricing\PricingRuleService;
use App\Repositories\Pricing\PricingRuleRepository;
use App\Validators\PricingRuleValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/** @agent-test: PricingRuleService @agent-pattern: Service test with DevDatabaseTrait */
class PricingRuleServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private PricingRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->service = new PricingRuleService(new PricingRuleRepository(null, $this->db), new PricingRuleValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_finds_applicable_rule_by_priority_and_date()
    {
        $this->service->create([
            'name' => 'Rule A',
            'condition_type' => 'product',
            'product_id' => 1,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'price' => 90,
            'priority' => 50,
        ]);

        $this->service->create([
            'name' => 'Rule B lower priority',
            'condition_type' => 'product',
            'product_id' => 1,
            'price' => 80,
            'priority' => 200,
        ]);

        $rule = $this->service->findApplicableRule([
            'product_id' => 1,
            'variant_id' => null,
            'quantity' => 1,
            'date' => '2025-06-01',
        ]);

        $this->assertNotNull($rule);
        $this->assertEquals('Rule A', $rule['name']);
    }
}
