<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\Database\PriceListSchemaTrait;
use Tests\Support\AuthTestTrait;

/** @agent-test: Orders pricing API @agent-pattern: Feature test (SQLite) */
class OrdersPricingApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use PriceListSchemaTrait;
    use AuthTestTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetPriceListSchema();
        $this->seedProduct(1, 100000);
        $listId = $this->seedPriceList(['name' => 'VIP', 'priority' => 5, 'apply_to_groups' => [2]]);
        $this->seedItem($listId, 1, null, 80000, 0, 0);
        $this->setUpAuthToken();
    }

    public function test_calculate_preview_applies_price_list(): void
    {
        $payload = [
            'customer_id' => 10,
            'customer_group_id' => 2,
            'order_date' => date('Y-m-d'),
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders/calculate-preview');

        $res->assertStatus(200);
        $res->assertJSONPath('data.total', 160000.0);
        $res->assertJSONPath('data.applied_price_list_name', 'VIP');
    }

    public function test_create_order_persists_and_records_price_list(): void
    {
        $payload = [
            'customer_id' => 10,
            'customer_group_id' => 2,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'CASH',
            'shipping' => [
                'name' => 'John Doe',
                'phone' => '0900000000',
                'address' => '123 Street',
                'ward' => 'Ward',
                'district' => 'District',
                'city' => 'City',
            ],
            'paid_amount' => 80000,
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders');

        $res->assertStatus(201);
        $res->assertJSONFragment(['success' => true]);
    }

    public function test_black_friday_overrides_vip(): void
    {
        $this->seedProduct(2, 100000);
        $vip = $this->seedPriceList(['name' => 'VIP', 'priority' => 5, 'apply_to_groups' => [2]]);
        $bf = $this->seedPriceList(['name' => 'BF', 'priority' => 10, 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')]);
        $this->seedItem($vip, 2, null, 80000, 0, 0);
        $this->seedItem($bf, 2, null, 60000, 0, 0);

        $payload = [
            'customer_group_id' => 2,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => 2, 'quantity' => 1]],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders/calculate-preview');

        $res->assertStatus(200);
        $res->assertJSONPath('data.items.0.final_price', 60000.0);
        $res->assertJSONPath('data.applied_price_list_name', 'BF');
    }

    public function test_multiple_customer_groups_priority_handling(): void
    {
        $this->seedProduct(3, 150000);
        $base = $this->seedPriceList(['name' => 'Retail', 'priority' => 1]);
        $groupA = $this->seedPriceList(['name' => 'GroupA', 'priority' => 3, 'apply_to_groups' => [3]]);
        $groupB = $this->seedPriceList(['name' => 'GroupB', 'priority' => 8, 'apply_to_groups' => [3, 4]]);

        $this->seedItem($base, 3, null, 150000, 0, 0);
        $this->seedItem($groupA, 3, null, 110000, 0, 0);
        $this->seedItem($groupB, 3, null, 90000, 0, 0);

        $payload = [
            'customer_group_id' => 3,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => 3, 'quantity' => 1]],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders/calculate-preview');

        $res->assertStatus(200);
        $res->assertJSONPath('data.items.0.final_price', 90000.0);
        $res->assertJSONPath('data.applied_price_list_name', 'GroupB');
    }

    public function test_rounding_rule_hundred_applied(): void
    {
        $this->seedProduct(4, 123456);
        $list = $this->seedPriceList([
            'name' => 'Round',
            'priority' => 9,
            'rounding_rule' => 'hundred',
            'formula' => 'base'
        ]);
        $this->seedItem($list, 4, null, 0, 0, 0);

        $payload = [
            'customer_group_id' => null,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => 4, 'quantity' => 1]],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders/calculate-preview');

        $res->assertStatus(200);
        $res->assertJSONPath('data.items.0.final_price', 123500.0);
        $res->assertJSONPath('data.applied_price_list_name', 'Round');
    }

    private function seedProduct(int $id, float $price): void
    {
        $this->db->table('db_products')->insert([
            'id' => $id,
            'code' => 'P' . $id,
            'name' => 'Prod ' . $id,
            'selling_price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedPriceList(array $data): int
    {
        $payload = array_merge([
            'name' => 'PL',
            'type' => 'custom',
            'priority' => 0,
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);
        $payload['apply_to_groups'] = isset($payload['apply_to_groups']) ? json_encode((array) $payload['apply_to_groups']) : null;
        $this->db->table('db_price_lists')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedItem(int $listId, int $productId, ?int $variantId, float $price, float $discountPercent, float $discountAmount): void
    {
        $this->db->table('db_price_list_items')->insert([
            'price_list_id' => $listId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'price' => $price,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
