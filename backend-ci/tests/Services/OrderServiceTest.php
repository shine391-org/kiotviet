<?php

namespace Tests\Services;

use App\Services\Orders\OrderService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\PriceListSchemaTrait;

/** @agent-test: OrderService tests @agent-pattern: Service orchestrator test */
class OrderServiceTest extends CIUnitTestCase
{
    use PriceListSchemaTrait;

    private OrderService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetPriceListSchema();
        $this->service = new OrderService();
    }

    /** @test */
    public function it_calculates_preview_with_price_list()
    {
        $pid = $this->seedProduct(100);
        $listId = $this->seedPriceList(['name' => 'VIP', 'priority' => 3]);
        $this->seedItem($listId, $pid, null, 80);

        $preview = $this->service->preview([
            'customer_id' => 1,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => $pid, 'quantity' => 2]],
        ]);

        $this->assertTrue($preview['success']);
        $data = $preview['data'];
        $this->assertEquals(200.0, $data['subtotal']);
        $this->assertEquals(160.0, $data['total']);
        $this->assertEquals($listId, $data['applied_price_list_id']);
    }

    /** @test */
    public function it_persists_order_with_pricing()
    {
        $pid = $this->seedProduct(150);
        $listId = $this->seedPriceList(['name' => 'Sale', 'priority' => 2]);
        $this->seedItem($listId, $pid, null, 120);

        $create = $this->service->create([
            'customer_id' => 2,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => $pid, 'quantity' => 1]],
        ]);

        $this->assertTrue($create['success']);
        $this->assertNotEmpty($create['data']['id'] ?? null);
        $this->assertEquals(120.0, (float) $create['data']['total']);
        $items = $create['data']['items'] ?? [];
        $this->assertCount(1, $items);
        $this->assertEquals($listId, (int) $items[0]['price_list_id']);
    }

    private function seedProduct(float $price): int
    {
        $this->db->table('db_products')->insert([
            'code' => 'P' . random_int(100, 999),
            'name' => 'Prod',
            'selling_price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
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

    private function seedItem(int $listId, int $productId, ?int $variantId, float $price): void
    {
        $this->db->table('db_price_list_items')->insert([
            'price_list_id' => $listId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'price' => $price,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
