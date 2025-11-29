<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\EcommerceSchemaTrait;
use Tests\Support\AuthTestTrait;

/**
 * @agent-test: Ecommerce webhooks API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class EcommerceWebhooksApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use EcommerceSchemaTrait;

    private string $secret = 'ecom-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetEcommerceSchema();
        $this->seedBranch();
        putenv('ECOM_WEBHOOK_SECRET=' . $this->secret);
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_syncs_product_and_order_via_webhooks(): void
    {
        $productPayload = [
            'idempotency_key' => 'p-sync-1',
            'event' => 'product.updated',
            'data' => [
                'code' => 'ECOM-1',
                'name' => 'Ecom Product',
                'price' => 20,
            ],
        ];
        $productResp = $this->postJson('/api/webhooks/ecommerce/product', $productPayload);
        $body = $this->decode($productResp);
        $this->assertTrue($body['success'] ?? false);

        $orderPayload = [
            'idempotency_key' => 'o-sync-1',
            'event' => 'order.created',
            'data' => [
                'branch_id' => 1,
                'payment_method' => 'CASH',
                'items' => [
                    ['product_code' => 'ECOM-1', 'quantity' => 1, 'price' => 20],
                ],
            ],
        ];
        $orderResp = $this->postJson('/api/webhooks/ecommerce/order', $orderPayload);
        $orderBody = $this->decode($orderResp);
        $this->assertTrue($orderBody['success'] ?? false);

        $orders = $this->db->table('orders')->get()->getResultArray();
        $this->assertCount(1, $orders);
        $items = $this->db->table('order_items')->get()->getResultArray();
        $this->assertCount(1, $items);
    }

    private function postJson(string $uri, array $payload)
    {
        $body = json_encode($payload);
        $sig = hash_hmac('sha256', $body, $this->secret);
        return $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Ecom-Signature' => $sig,
            'Authorization' => 'Bearer ' . $this->authToken,
        ])->withBody($body)->post($uri);
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
}
