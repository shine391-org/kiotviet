<?php

namespace Tests\Services;

use App\Services\Accounting\WithholdingService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: WithholdingService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class WithholdingServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private WithholdingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new WithholdingService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_withholding_when_above_threshold()
    {
        $rule = $this->service->create([
            'name' => 'WHT',
            'rate_percent' => 5,
            'apply_threshold' => 100,
        ])['data'];

        $result = $this->service->apply($rule['id'], 200);
        $this->assertEquals(10.0, $result['amount']);

        $zero = $this->service->apply($rule['id'], 50);
        $this->assertEquals(0.0, $zero['amount']);
    }
}
