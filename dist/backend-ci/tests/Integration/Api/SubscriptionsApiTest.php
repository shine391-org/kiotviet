<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\SubscriptionSchemaTrait;

/**
 * @agent-test: Subscriptions API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class SubscriptionsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use SubscriptionSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSubscriptionSchema();
        $this->seedBranch();
        $this->seedProduct();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_runs_subscription_via_api()
    {
        $headers = $this->authHeaders(['Content-Type' => 'application/json']);
        $create = $this->withHeaders($headers)->withBody(json_encode([
            'plan_name' => 'Weekly',
            'interval_days' => 7,
            'next_run_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ]))->post('/api/subscriptions');
        $body = $this->decode($create);
        $this->assertTrue($body['success'] ?? false, json_encode($body));
        $id = $body['data']['id'];

        $run = $this->withHeaders($headers)->post('/api/subscriptions/run');
        $runBody = $this->decode($run);
        $this->assertEquals(1, $runBody['processed'] ?? 0);

        $orders = $this->db->table('orders')->get()->getResultArray();
        $this->assertCount(1, $orders);
    }

    private function decode($response): array
    {
        $raw = $response->getBody();
        if (is_string($raw) && strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function seedBranch(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'Main',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedProduct(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'SUB1',
            'name' => 'Subscription Product',
            'selling_price' => 10,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
