<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\Database\PriceListSchemaTrait;

/** @agent-test: Price lists API @agent-pattern: Feature test (SQLite) */
class PriceListsApiTest extends CIUnitTestCase
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
    }

    public function test_create_price_list_success(): void
    {
        $payload = [
            'name' => 'Giá VIP',
            'type' => 'vip',
            'start_date' => '2025-01-01',
            'priority' => 3,
        ];

        $res = $this->withBody(json_encode($payload), 'application/json')
            ->post('api/price-lists');

        $res->assertStatus(201);
        $res->assertJSONFragment(['success' => true]);
        $res->assertJSONPath('data.name', 'Giá VIP');

        $row = $this->db->table('db_price_lists')->where('name', 'Giá VIP')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertEquals(3, (int) $row['priority']);
    }

    public function test_create_price_list_rejects_invalid_dates(): void
    {
        $payload = ['name' => 'Sai', 'start_date' => '2025-12-31', 'end_date' => '2025-01-01'];
        $res = $this->withBody(json_encode($payload), 'application/json')->post('api/price-lists');
        $res->assertStatus(400);
    }

    public function test_upsert_items_and_fetch(): void
    {
        $listId = $this->insertPriceList(['name' => 'Black Friday', 'priority' => 5]);

        $items = [
            ['product_id' => 1, 'price' => 80000, 'discount_percent' => 0],
            ['product_id' => 1, 'variant_id' => null, 'price' => 75000, 'discount_percent' => 5],
        ];

        $post = $this->withBody(json_encode(['items' => $items]), 'application/json')
            ->post("/api/price-lists/{$listId}/items");
        $this->assertInstanceOf(\CodeIgniter\Test\TestResponse::class, $post);
        $post->assertStatus(200);
        $post->assertJSONFragment(['success' => true]);

        $res = $this->get("/api/price-lists/{$listId}/items");
        $this->assertInstanceOf(\CodeIgniter\Test\TestResponse::class, $res);
        $res->assertStatus(200);
        $res->assertJSONPath('data.0.product_id', 1);
        $this->assertEquals(2, $this->db->table('db_price_list_items')->where('price_list_id', $listId)->countAllResults());
    }

    public function test_filter_by_status_active_and_expired(): void
    {
        $today = date('Y-m-d');
        $this->insertPriceList(['name' => 'Active', 'start_date' => $today, 'end_date' => null, 'is_active' => 1]);
        $this->insertPriceList(['name' => 'Expired', 'start_date' => '2024-01-01', 'end_date' => '2024-01-31', 'is_active' => 1]);

        $active = $this->get('/api/price-lists?status=active');
        $this->assertInstanceOf(\CodeIgniter\Test\TestResponse::class, $active);
        $active->assertStatus(200);
        $active->assertJSONFragment(['success' => true]);

        $expired = $this->get('/api/price-lists?status=expired');
        $this->assertInstanceOf(\CodeIgniter\Test\TestResponse::class, $expired);
        $expired->assertStatus(200);
        $expired->assertJSONFragment(['success' => true]);
    }

    private function insertPriceList(array $data): int
    {
        $payload = array_merge([
            'name' => 'List',
            'type' => 'custom',
            'priority' => 0,
            'is_active' => 1,
            'start_date' => null,
            'end_date' => null,
            'apply_to_groups' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        if (isset($payload['apply_to_groups']) && $payload['apply_to_groups'] !== null) {
            $payload['apply_to_groups'] = json_encode((array) $payload['apply_to_groups']);
        }

        $this->db->table('db_price_lists')->insert($payload);
        return (int) $this->db->insertID();
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
}
