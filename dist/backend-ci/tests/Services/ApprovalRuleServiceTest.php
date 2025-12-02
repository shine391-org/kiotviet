<?php

namespace Tests\Services;

use App\Services\Approvals\ApprovalRuleService;
use App\Repositories\Approvals\ApprovalRuleRepository;
use App\Validators\ApprovalRuleValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/** @agent-test: ApprovalRuleService @agent-pattern: Service test with DevDatabaseTrait */
class ApprovalRuleServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ApprovalRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $repo = new ApprovalRuleRepository(null, $this->db);
        $this->service = new ApprovalRuleService($repo, new ApprovalRuleValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_rule_and_evaluates_amount()
    {
        $create = $this->service->create([
            'name' => 'Amount > 1000',
            'condition_type' => 'amount',
            'threshold_amount' => 1000,
            'approver_ids' => [10, 11],
        ]);
        $this->assertTrue($create['success']);

        $queue = $this->service->evaluateForOrder([
            'total' => 1500,
            'customer_id' => 1,
        ]);
        $this->assertEquals([10, 11], $queue);
    }

    /** @test */
    public function it_ignores_inactive_rule()
    {
        $this->service->create([
            'name' => 'Inactive',
            'condition_type' => 'amount',
            'threshold_amount' => 0,
            'approver_ids' => [5],
            'is_active' => 0,
        ]);

        $queue = $this->service->evaluateForOrder(['total' => 5000]);
        $this->assertEmpty($queue);
    }
}
