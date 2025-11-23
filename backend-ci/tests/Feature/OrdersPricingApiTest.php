<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\Database\PriceListSchemaTrait;

/** @agent-test: Orders pricing API @agent-pattern: Feature test (SQLite) */
class OrdersPricingApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use PriceListSchemaTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetPriceListSchema();
        $this->seedProduct(1, 100000);
        $listId = $this->seedPriceList(['name' => 'VIP', 'priority' => 5, 'apply_to_groups' => [2]]);
        $this->seedItem($listId, 1, null, 80000, 0, 0);
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

        $res = $this->withBody(json_encode($payload), 'application/json')
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
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ];

        $res = $this->withBody(json_encode($payload), 'application/json')
            ->post('api/orders');

        $res->assertStatus(201);
        $res->assertJSONFragment(['success' => true]);

        $order = $this->db->table('db_orders')->get()->getRowArray();
        $this->assertNotNull($order);
        $this->assertEquals(80000.0, (float) $order['total']);

        $item = $this->db->table('db_order_items')->where('order_id', $order['id'])->get()->getRowArray();
        $this->assertEquals(80000.0, (float) $item['final_price']);
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
