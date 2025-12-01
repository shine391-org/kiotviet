<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\OrderTemplateSchemaTrait;

/**
 * @agent-test: Order templates API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class OrderTemplatesApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use OrderTemplateSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->db->table('order_templates')->truncate();
        $this->db->table('order_template_items')->truncate();
        $this->db->table('products')->truncate();
        $this->db->table('branches')->truncate();
        $this->db->table('price_lists')->truncate();
        $this->db->table('price_list_items')->truncate();
        $this->db->table('customers')->truncate();
        
        $now = date('Y-m-d H:i:s');
        $this->db->table('warehouses')->insert(['id' => 1, 'code' => 'WH-1', 'name' => 'Warehouse 1', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        
        $this->seedProduct();
        $this->seedBranch();
        $this->seedPriceList(30);
        $this->seedCustomer();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_applies_template_via_api()
    {
        $headers = $this->authHeaders(['Content-Type' => 'application/json']);

        $create = $this->withHeaders($headers)->withBody(json_encode([
            'name' => 'Weekly template',
            'customer_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]))->post('/api/order-templates');
        $body = $this->decode($create);
        $this->assertTrue($body['success'] ?? false, json_encode($body));
        $templateId = $body['data']['id'];

        $apply = $this->withHeaders($headers)->withBody(json_encode([
            'branch_id' => 1,
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'shipping_name' => 'Tester',
            'shipping_phone' => '0909',
            'shipping_address' => '123 Street',
        ]))->post('/api/order-templates/' . $templateId . '/apply');

        $applyBody = $this->decode($apply);
        $this->assertTrue($applyBody['success'] ?? false, json_encode($applyBody));
        $this->assertSame('draft', $applyBody['data']['status']);
        $this->assertCount(1, $applyBody['data']['items']);
    }

    private function seedProduct(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P1',
            'name' => 'Prod 1',
            'selling_price' => 30,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedBranch(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'Branch 1',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedPriceList(float $price): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('price_lists')->insert([
            'id' => 1,
            'name' => 'Base',
            'type' => 'custom',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_list_items')->insert([
            'price_list_id' => 1,
            'product_id' => 1,
            'variant_id' => null,
            'price' => $price,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedCustomer(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert([
            'id' => 1,
            'name' => 'Cust 1',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
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
}
