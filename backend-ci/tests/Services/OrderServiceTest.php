<?php

namespace Tests\Services;

use App\Services\Orders\OrderService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\PriceListSchemaTrait;

/**
 * @agent-test: OrderService unified MySQL testing
 * @agent-pattern: Service orchestrator test with DevDatabaseTrait
 */
class OrderServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use PriceListSchemaTrait;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        
        // Use PriceListSchemaTrait for comprehensive schema including order_sequences
        $this->resetPriceListSchema();
        
        // Seed order sequences for OrderNumberGenerator
        $this->seedOrderSequences();
        
        $this->service = new OrderService();
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_preview_with_price_list()
    {
        $pid = $this->seedProduct(100);
        $listId = $this->seedPriceList(['name' => 'VIP', 'priority' => 3]);
        $this->seedItem($listId, $pid, null, 80);

        $preview = $this->service->preview([
            'customer_id' => null,
            'branch_id' => 1,
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
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'shipping' => [
                'name' => 'Test Customer',
                'phone' => '123456789',
                'address' => 'Test Address',
                'ward' => 'Test Ward',
                'district' => 'Test District',
                'city' => 'Test City'
            ],
            'items' => [['product_id' => $pid, 'quantity' => 1]],
            'notes' => 'Test order'
        ]);

        $this->assertTrue($create['success']);
        $this->assertArrayHasKey('data', $create);
        $this->assertEquals(120.0, (float) $create['data']['total']);
        $items = $create['data']['items'] ?? [];
        $this->assertCount(1, $items);
        $this->assertEquals($listId, (int) $items[0]['price_list_id']);
    }

    /** @test */
    public function it_creates_order_with_multiple_products_and_mixed_pricing()
    {
        $p1 = $this->seedProduct(100);
        $p2 = $this->seedProduct(50);
        $listId = $this->seedPriceList(['name' => 'OnlyP1', 'priority' => 3]);
        $this->seedItem($listId, $p1, null, 80);

        $create = $this->service->create([
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'shipping' => [
                'name' => 'Test Customer',
                'phone' => '123456789',
                'address' => 'Test Address',
                'ward' => 'Test Ward',
                'district' => 'Test District',
                'city' => 'Test City'
            ],
            'items' => [
                ['product_id' => $p1, 'quantity' => 2], // priced by list -> 80 *2
                ['product_id' => $p2, 'quantity' => 1], // base 50
            ],
            'notes' => 'Test order'
        ]);

        $this->assertTrue($create['success']);
        $this->assertEquals(210.0, (float) $create['data']['total']); // 160 + 50
    }

    /** @test */
    public function it_rejects_zero_quantity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $p1 = $this->seedProduct(100);
        $this->service->create([
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'CASH',
            'order_type' => 'shipping',
            'shipping' => [
                'name' => 'Test Customer',
                'phone' => '123456789',
                'address' => 'Test Address',
                'ward' => 'Test Ward',
                'district' => 'Test District',
                'city' => 'Test City'
            ],
            'items' => [
                ['product_id' => $p1, 'quantity' => 0],
            ],
            'notes' => 'Test order'
        ]);
    }

    private function seedProduct(float $price): int
    {
        $this->db->table('products')->insert([
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
        $this->db->table('price_lists')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedItem(int $listId, int $productId, ?int $variantId, float $price): void
    {
        $this->db->table('price_list_items')->insert([
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

    private function seedOrderSequences(): void
    {
        $this->db->table('order_sequences')->insert([
            'branch_id' => 1,
            'sequence_number' => 1,
            'prefix' => 'ORD',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
