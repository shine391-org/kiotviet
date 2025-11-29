<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: Pricing API preview
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class PricingApiTest extends CIUnitTestCase
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
        $this->seedBase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_price_with_reason()
    {
        $resp = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'product_id' => 1,
                'customer_id' => 1,
                'quantity' => 1,
                'order_date' => '2025-11-29',
            ]))
            ->post('/api/pricing/preview');
        $body = $this->decode($resp);
        $this->assertTrue($body['success'] ?? false, json_encode($body));
        $this->assertEquals('customer_price_list', $body['reason']['source']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P1',
            'name' => 'Product 1',
            'selling_price' => 100,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_lists')->insert([
            'id' => 1,
            'name' => 'VIP',
            'is_active' => 1,
        ]);
        $this->db->table('price_list_items')->insert([
            'price_list_id' => 1,
            'product_id' => 1,
            'price' => 80,
        ]);
        $this->db->table('customer_price_lists')->insert([
            'customer_id' => 1,
            'price_list_id' => 1,
            'is_active' => 1,
        ]);
    }

    private function decode($response): array
    {
        $raw = $response->getBody();
        if (is_string($raw) && strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
