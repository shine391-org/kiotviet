<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\POSSchemaTrait;

/**
 * @agent-test: POS offline API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class POSOfflineApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use POSSchemaTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
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
    public function it_syncs_batch_and_respects_idempotency()
    {
        $payload = [
            'items' => [
                ['temp_id' => 'A1', 'device_id' => 'DEV1', 'payload' => $this->orderPayload(50)],
                ['temp_id' => 'A1', 'device_id' => 'DEV1', 'payload' => $this->orderPayload(50)], // duplicate
                ['temp_id' => 'B1', 'device_id' => 'DEV1', 'payload' => $this->orderPayload(50, 20)], // will fail stock
            ],
        ];

        $response = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post('/api/pos/offline/sync');
        $response->assertStatus(200);
        $body = $this->getJsonFromResponse($response);

        $this->assertTrue($body['success']);
        $this->assertCount(3, $body['data']);
        $synced = array_values(array_filter($body['data'], fn ($row) => $row['status'] === 'synced'));
        $this->assertCount(2, $synced);
        $this->assertEquals($synced[0]['order_id'], $synced[1]['order_id']);

        $failed = array_values(array_filter($body['data'], fn ($row) => $row['status'] === 'failed'));
        $this->assertCount(1, $failed);
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
            'price' => 50,
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
        $this->db->table('pos_profiles')->insert([
            'id' => 1,
            'name' => 'POS1',
            'user_id' => 1,
            'branch_id' => 1,
            'price_list_id' => 1,
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

    private function orderPayload(float $price, int $quantity = 1): array
    {
        return [
            'branch_id' => 1,
            'order_type' => 'pos',
            'user_id' => 1,
            'pos_profile_id' => 1,
            'payment_method' => 'CASH',
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => $price * $quantity],
            ],
            'items' => [
                ['product_id' => 1, 'quantity' => $quantity],
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
