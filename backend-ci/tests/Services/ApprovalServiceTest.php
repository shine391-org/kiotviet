<?php

namespace Tests\Services;

use App\Services\Approvals\ApprovalService;
use App\Services\Approvals\ApprovalRuleService;
use App\Repositories\Approvals\ApprovalRepository;
use App\Repositories\Approvals\ApprovalActionRepository;
use App\Repositories\Approvals\ApprovalRuleRepository;
use App\Validators\ApprovalRequestValidator;
use App\Validators\ApprovalRuleValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/** @agent-test: ApprovalService @agent-pattern: Service test with DevDatabaseTrait */
class ApprovalServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedOrder();

        $db = $this->db;
        $ruleRepo = new ApprovalRuleRepository(null, $db);
        $ruleService = new ApprovalRuleService($ruleRepo, new ApprovalRuleValidator());
        $approvalRepo = new ApprovalRepository(null, $db);
        $actionRepo = new ApprovalActionRepository(null, $db);
        $this->service = new ApprovalService($approvalRepo, $actionRepo, $ruleService, new ApprovalRequestValidator());

        $ruleService->create([
            'name' => 'Over 1k',
            'condition_type' => 'amount',
            'threshold_amount' => 1000,
            'approver_ids' => [10, 11],
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_submits_and_approves_multi_level()
    {
        $res = $this->service->submitOrder(['order_id' => 1, 'requested_by' => 2], $this->orderData());
        $this->assertTrue($res['success']);
        $approval = $res['data'];
        $this->assertEquals('pending', $approval['status']);
        $this->assertEquals(10, $approval['current_approver_id']);

        $step1 = $this->service->approve($approval['id'], ['actor_id' => 10]);
        $this->assertEquals(11, $step1['data']['current_approver_id']);

        $step2 = $this->service->approve($approval['id'], ['actor_id' => 11]);
        $this->assertEquals('approved', $step2['data']['status']);
    }

    /** @test */
    public function it_prevents_double_approve_and_allows_reject()
    {
        $res = $this->service->submitOrder(['order_id' => 1, 'requested_by' => 2], $this->orderData());
        $approval = $res['data'];

        $this->service->approve($approval['id'], ['actor_id' => 10]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->approve($approval['id'], ['actor_id' => 10]);
    }

    /** @test */
    public function it_rejects()
    {
        $res = $this->service->submitOrder(['order_id' => 1, 'requested_by' => 2], $this->orderData());
        $approval = $res['data'];

        $rej = $this->service->reject($approval['id'], ['actor_id' => 10, 'notes' => 'Nope']);
        $this->assertEquals('rejected', $rej['data']['status']);
    }

    private function seedOrder(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('orders')->insert([
            'id' => 1,
            'order_number' => 'ORD-APP',
            'customer_id' => 1,
            'branch_id' => 1,
            'status' => 'draft',
            'order_type' => 'shipping',
            'payment_method' => 'CASH',
            'subtotal' => 1500,
            'total' => 1500,
            'paid_amount' => 0,
            'debt_amount' => 1500,
            'order_date' => date('Y-m-d'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function orderData(): array
    {
        return $this->db->table('orders')->where('id', 1)->get()->getRowArray() ?? [];
    }
}
