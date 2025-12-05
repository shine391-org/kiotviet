<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\POSSchemaTrait;

/**
 * @agent-test: POS checkout with tax + payment entries
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class POSTaxPaymentApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use POSSchemaTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');

        require_once APPPATH . 'Database/Migrations/2025-11-21-000000_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->resetPOSSchema();
        $this->seedBase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_payment_entries_and_applies_tax()
    {
        $payload = [
            'branch_id' => 1,
            'order_type' => 'pos',
            'user_id' => 1,
            'customer_id' => null,
            'pos_profile_id' => 1,
            'payment_method' => 'CASH',
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 110],
            ],
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
            'shipping' => [
                'name' => 'Walkin',
                'phone' => '0909',
                'address' => 'POS',
                'ward' => 'W',
                'district' => 'D',
                'city' => 'C',
            ],
        ];

        $response = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post('/api/orders');
        $response->assertStatus(201);
        $body = $this->getJsonFromResponse($response);

        $this->assertTrue($body['success']);
        $order = $body['data'];
        $this->assertEquals(10.0, (float) $order['tax_total']);
        $this->assertEquals(110.0, (float) $order['total']);

        $entries = $this->db->table('payment_entries')->where('order_id', $order['id'])->get()->getResultArray();
        $this->assertCount(1, $entries);
        $this->assertEquals(110.0, (float) $entries[0]['amount']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'cashier', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('payment_methods')->insert([
            'code' => 'CASH',
            'name' => 'Cash',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_lists')->insert([
            'id' => 1,
            'name' => 'POS',
            'type' => 'custom',
            'priority' => 1,
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P1',
            'name' => 'Item',
            'selling_price' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_list_items')->insert([
            'price_list_id' => 1,
            'product_id' => 1,
            'price' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('inventory_stock')->insert([
            'branch_id' => 1,
            'product_id' => 1,
            'variant_id' => null,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'minimum_stock' => 0,
        ]);
        $this->db->table('tax_templates')->insert([
            'id' => 1,
            'name' => 'VAT10',
            'rate_percent' => 10,
            'is_inclusive' => 0,
            'rounding_rule' => 'nearest',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('pos_profiles')->insert([
            'id' => 1,
            'name' => 'POS1',
            'user_id' => 1,
            'branch_id' => 1,
            'price_list_id' => 1,
            'tax_template_id' => 1,
            'require_shift' => 0,
            'allow_offline' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('pos_payment_methods')->insert([
            'profile_id' => 1,
            'payment_method' => 'CASH',
            'is_allowed' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
