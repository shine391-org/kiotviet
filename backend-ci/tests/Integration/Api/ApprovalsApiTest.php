<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: Approvals API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class ApprovalsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBaseData();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_submits_and_approves_via_api()
    {
        // Create rule
        $ruleResp = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'name' => 'Over 1k',
                'condition_type' => 'amount',
                'threshold_amount' => 1000,
                'approver_ids' => [10, 11],
            ]))
            ->post('/api/approval-rules');
        $ruleBody = $this->decodeResponse($ruleResp);
        $this->assertTrue($ruleBody['success'] ?? false, json_encode($ruleBody));

        // Submit approval for order
        $submit = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['order_id' => 1, 'requested_by' => 2]))
            ->post('/api/approvals/submit');
        $submitBody = $this->decodeResponse($submit);
        $this->assertTrue($submitBody['success'] ?? false, json_encode($submitBody));
        $this->assertNotNull($submitBody['data'] ?? null, 'Submit response missing data');
        $this->assertNotNull($submitBody['data']['id'] ?? null, 'Submit response missing id');
        $approvalId = $submitBody['data']['id'];

        // Approve level 1
        $approve1 = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['actor_id' => 10]))
            ->post('/api/approvals/' . $approvalId . '/approve');
        $approve1Body = $this->decodeResponse($approve1);
        $this->assertTrue($approve1Body['success'] ?? false, json_encode($approve1Body));

        // Approve level 2
        $approve2 = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['actor_id' => 11]))
            ->post('/api/approvals/' . $approvalId . '/approve');
        $approve2Body = $this->decodeResponse($approve2);
        $this->assertEquals('approved', $approve2Body['data']['status']);
    }

    /** @test */
    public function it_rejects_via_api()
    {
        $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'name' => 'Over 500',
                'condition_type' => 'amount',
                'threshold_amount' => 500,
                'approver_ids' => [20],
            ]))
            ->post('/api/approval-rules');

        $submit = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['order_id' => 1, 'requested_by' => 2]))
            ->post('/api/approvals/submit');
        $submitBody = $this->decodeResponse($submit);
        $this->assertNotNull($submitBody['data'] ?? null, 'Submit response missing data');
        $this->assertNotNull($submitBody['data']['id'] ?? null, 'Submit response missing id');
        $approvalId = $submitBody['data']['id'];

        $reject = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['actor_id' => 20, 'notes' => 'Nope']))
            ->post('/api/approvals/' . $approvalId . '/reject');
        $rejectBody = $this->decodeResponse($reject);
        $this->assertEquals('rejected', $rejectBody['data']['status']);
    }

    private function seedBaseData(): void
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

    private function decodeResponse($response): array
    {
        $raw = $response->getBody();
        if (is_string($raw) && strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
