<?php

namespace Tests\Integration\Orders;

use App\Services\Orders\OrderService;
use App\Services\Orders\OrderStatusService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\StatusSchemaTrait;

/**
 * @agent-test: Order lifecycle integration (POS + Shipping)
 * @agent-pattern: End-to-end workflow on test DB
 */
class OrderLifecycleIntegrationTest extends CIUnitTestCase
{
    use StatusSchemaTrait;

    protected $db;
    protected OrderService $orders;
    protected OrderStatusService $statuses;

    protected function setUp(): void
    {
        parent::setUp();

        $config = config('Database');
        if (extension_loaded('sqlite3')) {
            $config->tests = [
                'DBDriver'    => 'SQLite3',
                'database'    => ':memory:',
                'DBPrefix'    => 'db_',
                'foreignKeys' => true,
                'DBDebug'     => true,
            ];
        }
        $config->defaultGroup = 'tests';

        $this->db = Database::connect('tests', false);
        if (strtolower($this->db->DBDriver) === 'sqlite3') {
            $this->markTestSkipped('Order lifecycle integration requires MySQL schema.');
        }
        $this->resetStatusSchema();
        $this->resetProducts();

        $this->orders = service('orderService');
        $this->statuses = service('orderStatusService');
    }

    private function resetProducts(): void
    {
        $this->db->query('DROP TABLE IF EXISTS products');
        $this->db->query('DROP TABLE IF EXISTS db_products');
        $this->db->query('CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT,
            name TEXT,
            selling_price REAL DEFAULT 0,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');
        $this->db->query('CREATE TABLE db_products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT,
            name TEXT,
            selling_price REAL DEFAULT 0,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');
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
            'payment_method' => 'CASH',
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
        $stock = $this->db->table('inventory_stock')->where('product_id', $productId)->get()->getRowArray();
        $this->assertEquals(45.0, (float) $stock['quantity_on_hand']);

        $log = $this->db->table('order_status_logs')->where('order_id', $order['id'])->get()->getRowArray();
        $this->assertNotNull($log);
        $movement = $this->db->table('inventory_movements')->where('reference_id', $order['id'])->get()->getRowArray();
        $this->assertNotNull($movement);
    }

    /** @test */
    public function full_shipping_order_lifecycle(): void
    {
        $productId = $this->seedProduct(200000);
        $this->db->table('inventory_stock')->insert([
            'branch_id' => 1,
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
        $stock = $this->db->table('inventory_stock')->where('product_id', $productId)->get()->getRowArray();
        $this->assertEquals(90.0, (float) $stock['quantity_on_hand']);

        $order = $this->statuses->updateStatus($order['id'], 'shipping')['data'];
        $order = $this->statuses->updateStatus($order['id'], 'delivered')['data'];
        $order = $this->statuses->updateStatus($order['id'], 'completed')['data'];

        $this->assertEquals('completed', $order['status']);
        $this->assertNotNull($order['completed_at']);
    }

    private function seedProduct(float $price): int
    {
        $this->db->query('DELETE FROM products');
        $this->db->query('DELETE FROM db_products');
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
        $this->db->table('db_products')->insert($row);
        return $id;
    }
}
