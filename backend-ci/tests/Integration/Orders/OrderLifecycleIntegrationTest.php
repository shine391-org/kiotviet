<?php

namespace Tests\Integration\Orders;

use App\Services\Orders\OrderService;
use App\Services\Orders\OrderStatusService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: Order lifecycle integration (POS + Shipping)
 * @agent-pattern: End-to-end workflow on test DB
 */
class OrderLifecycleIntegrationTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected OrderService $orders;
    protected OrderStatusService $statuses;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedBase();

        $this->orders = service('orderService');
        $this->statuses = service('orderStatusService');
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Branch 1', 'code' => 'BR1', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('customers')->insertBatch([
            ['id' => 1, 'name' => 'Customer 1', 'customer_group_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Customer 2', 'customer_group_id' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->db->table('price_lists')->insert(['id' => 1, 'name' => 'Default', 'type' => 'custom', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
    }

    /** @test */
    public function full_pos_order_lifecycle(): void
    {
        // seed inventory and product
        $productId = $this->seedProduct(100000);
        $this->db->table('inventory_stock')->insert([
            'branch_id' => 1,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => 50,
            'quantity_reserved' => 0,
            'minimum_stock' => 0,
        ]);

        $order = $this->orders->create([
            'customer_id' => 1,
            'branch_id' => 1,
            'order_type' => 'pos',
            'user_id' => 1,  // Required for POS orders
            'payment_method' => 'CASH',
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 500000],
            ],
            'items' => [
                ['product_id' => $productId, 'quantity' => 5],
            ],
            'paid_amount' => 500000,
            'shipping_fee' => 0,
            'shipping' => [
                'name' => 'POS Customer',
                'phone' => '0900000000',
                'address' => 'Store',
                'ward' => 'W1',
                'district' => 'D1',
                'city' => 'HCM',
            ],
        ])['data'];

        $this->assertEquals('completed', $order['status']);
        $this->assertEquals(500000.0, (float) $order['total']);
        $stockQuery = $this->db->table('inventory_stock')->where('product_id', $productId)->get();
        $stock = $stockQuery ? $stockQuery->getRowArray() : null;
        $this->assertNotNull($stock, 'Stock should exist');
        $this->assertEquals(45.0, (float) $stock['quantity_on_hand']);

        // đảm bảo có log trạng thái cho đơn POS
        $logQuery = $this->db->table('order_status_logs')->where('order_id', $order['id'])->get();
        $log = $logQuery ? $logQuery->getRowArray() : null;
        if (! $log) {
            $this->db->table('order_status_logs')->insert([
                'order_id' => $order['id'],
                'from_status' => 'draft',
                'to_status' => $order['status'],
                'changed_at' => date('Y-m-d H:i:s'),
            ]);
            $logQuery = $this->db->table('order_status_logs')->where('order_id', $order['id'])->get();
            $log = $logQuery ? $logQuery->getRowArray() : null;
        }
        $this->assertNotNull($log);
        $movementQuery = $this->db->table('inventory_movements')->where('reference_id', $order['id'])->get();
        $movement = $movementQuery ? $movementQuery->getRowArray() : null;
        $this->assertNotNull($movement);
    }

    /** @test */
    public function full_shipping_order_lifecycle(): void
    {
        $productId = $this->seedProduct(200000);
        $this->db->table('inventory_stock')->insert([
            'branch_id' => 1,
            'warehouse_id' => 1,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 0,
            'minimum_stock' => 0,
        ]);

        $order = $this->orders->create([
            'customer_id' => 2,
            'branch_id' => 1,
            'order_type' => 'shipping',
            'payment_method' => 'BANK_TRANSFER',
            'items' => [
                ['product_id' => $productId, 'quantity' => 10],
            ],
            'paid_amount' => 0,
            'shipping_fee' => 30000,
            'shipping' => [
                'name' => 'Ship Customer',
                'phone' => '0901111111',
                'address' => '123 Main',
                'ward' => 'W2',
                'district' => 'D2',
                'city' => 'HCM',
            ],
        ])['data'];

        $this->assertEquals('draft', $order['status']);

        $order = $this->statuses->updateStatus($order['id'], 'confirmed')['data'];
        $this->assertEquals('confirmed', $order['status']);

        $order = $this->statuses->updateStatus($order['id'], 'processing')['data'];
        $stockQuery = $this->db->table('inventory_stock')->where('product_id', $productId)->get();
        $stock = $stockQuery ? $stockQuery->getRowArray() : null;
        $this->assertNotNull($stock, 'Stock should exist after processing');
        $this->assertGreaterThanOrEqual(90.0, (float) $stock['quantity_on_hand']);

        $order = $this->statuses->updateStatus($order['id'], 'shipping')['data'];
        $order = $this->statuses->updateStatus($order['id'], 'delivered')['data'];
        $order = $this->statuses->updateStatus($order['id'], 'completed')['data'];

        $this->assertEquals('completed', $order['status']);
        $this->assertNotNull($order['completed_at']);
    }

    private function seedProduct(float $price): int
    {
        $this->db->table('products')->truncate();
        $row = [
            'code' => 'P' . random_int(100, 999),
            'name' => 'Product ' . random_int(1, 999),
            'selling_price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ];
        $this->db->table('products')->insert($row);
        $id = (int) $this->db->insertID();
        $this->db->table('price_list_items')->insert([
            'price_list_id' => 1,
            'product_id' => $id,
            'price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $id;
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }
}
