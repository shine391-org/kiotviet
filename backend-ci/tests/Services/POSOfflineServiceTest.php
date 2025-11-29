<?php

namespace Tests\Services;

use App\Services\POS\POSOfflineService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\POSSchemaTrait;

/**
 * @agent-test: POSOfflineService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait + schema traits
 */
class POSOfflineServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use POSSchemaTrait;

    private POSOfflineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetPOSSchema();
        $this->seedBase();
        $this->service = new POSOfflineService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_syncs_and_marks_synced()
    {
        $result = $this->service->sync([
            'items' => [
                [
                    'temp_id' => 't1',
                    'device_id' => 'd1',
                    'payload' => $this->orderPayload(80),
                ],
            ],
        ]);
        $this->assertTrue($result['success']);
        $item = $result['data'][0];
        $this->assertEquals('synced', $item['status']);
        $this->assertNotEmpty($item['order_id']);
        $this->assertEquals(1, $this->db->table('orders')->countAllResults());
    }

    /** @test */
    public function it_is_idempotent_and_returns_existing_order()
    {
        $payload = ['temp_id' => 't2', 'device_id' => 'd1', 'payload' => $this->orderPayload(80)];
        $first = $this->service->sync(['items' => [$payload]]);
        $second = $this->service->sync(['items' => [$payload]]);

        $row = $this->db->table('pos_offline_queue')->where('temp_id', 't2')->get()->getRowArray();
        $this->assertEquals('synced', $row['status'], $row['error_message'] ?? '');
        $this->assertGreaterThan(0, (int) $row['order_id']);
        $this->assertEquals(1, $this->db->table('orders')->countAllResults());
    }

    /** @test */
    public function it_marks_failed_when_stock_insufficient()
    {
        // zero stock to trigger failure
        $this->db->table('inventory_stock')->where('product_id', $this->productId())->update(['quantity_on_hand' => 0]);

        $res = $this->service->sync(['items' => [[
            'temp_id' => 't3',
            'device_id' => 'd1',
            'payload' => $this->orderPayload(80, quantity: 10),
        ]]]);

        $this->assertEquals('failed', $res['data'][0]['status']);
        $row = $this->db->table('pos_offline_queue')->where('temp_id', 't3')->get()->getRowArray();
        $this->assertEquals('failed', $row['status']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'cashier', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('payment_methods')->insertBatch([
            ['code' => 'CASH', 'name' => 'Cash', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
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
            'price' => 80,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('inventory_stock')->insert([
            'branch_id' => 1,
            'product_id' => 1,
            'variant_id' => null,
            'quantity_on_hand' => 100,
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

    private function productId(): int
    {
        return (int) $this->db->table('products')->select('id')->get()->getRow('id');
    }

    private function orderPayload(float $price, int $quantity = 1): array
    {
        return [
            'customer_id' => null,
            'branch_id' => 1,
            'order_type' => 'pos',
            'payment_method' => 'CASH',
            'user_id' => 1,
            'pos_profile_id' => 1,
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => $price * $quantity],
            ],
            'items' => [
                ['product_id' => $this->productId(), 'quantity' => $quantity],
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
}
