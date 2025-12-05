<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\POSSchemaTrait;

/**
 * @agent-test: POS loyalty + coupon integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class POSLoyaltyCouponApiTest extends CIUnitTestCase
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
        $this->db->table('orders')->truncate();
        $this->db->table('order_items')->truncate();
        $this->db->table('order_payments')->truncate();
        $this->db->table('products')->truncate();
        $this->db->table('price_lists')->truncate();
        $this->db->table('price_list_items')->truncate();
        $this->db->table('branches')->truncate();
        $this->db->table('users')->truncate();
        $this->db->table('customers')->truncate();
        $this->db->table('pos_profiles')->truncate();
        $this->db->table('pos_payment_methods')->truncate();
        $this->db->table('loyalty_programs')->truncate();
        $this->db->table('loyalty_wallets')->truncate();
        $this->db->table('coupons')->truncate();
        $this->db->table('coupon_usages')->truncate();
        $this->db->table('cash_transactions')->truncate();
        $this->seedBase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_coupon_and_redeems_points_in_pos_order()
    {
        $payload = [
            'branch_id' => 1,
            'order_type' => 'pos',
            'user_id' => 1,
            'customer_id' => 1,
            'pos_profile_id' => 1,
            'payment_method' => 'CASH',
            'coupon_code' => 'SALE10',
            'redeem_points' => 5,
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 67],
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
        $this->assertEquals('SALE10', $order['coupon_code']);
        $this->assertEquals(8.0, (float) $order['coupon_discount']);
        $this->assertEquals(5.0, (float) $order['loyalty_discount']);
        $this->assertEquals(67.0, (float) $order['total']);

        $usage = $this->db->table('coupon_usages')->where('coupon_id', 1)->get()->getRowArray();
        $this->assertNotNull($usage);
        $wallet = $this->db->table('loyalty_wallets')->where('customer_id', 1)->get()->getRowArray();
        $this->assertEquals(15.0, (float) $wallet['points_balance']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'cashier', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'Cust', 'created_at' => $now, 'updated_at' => $now]);
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
            'product_type' => 'standard',
            'name' => 'Item',
            'selling_price' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_list_items')->insert([
            'price_list_id' => 1,
            'product_id' => 1,
            'price' => 80,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('warehouses')->insert([
            'id' => 1,
            'code' => 'WH-1',
            'name' => 'Main Warehouse',
            'branch_id' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('inventory_stock')->insert([
            'branch_id' => 1,
            'warehouse_id' => 1,
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
        $this->db->table('loyalty_programs')->insert([
            'id' => 1,
            'name' => 'Default',
            'earn_rate' => 0.01,
            'redeem_rate' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('loyalty_wallets')->insert([
            'customer_id' => 1,
            'points_balance' => 20,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('coupons')->insert([
            'id' => 1,
            'code' => 'SALE10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'usage_limit' => 0,
            'status' => 'active',
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
