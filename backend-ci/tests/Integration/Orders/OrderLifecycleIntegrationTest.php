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
        $config->defaultGroup = 'tests';

        $this->db = Database::connect('tests', false);
        $this->resetStatusSchema();
        $this->resetProducts();

        $this->orders = service('orderService');
        $this->statuses = service('orderStatusService');
    }

    private function resetProducts(): void
    {
        $this->db->query('DROP TABLE IF EXISTS products');
        $this->db->query('CREATE TABLE products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50),
            name VARCHAR(255),
            selling_price DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        // minimal price list tables required by price calculator
        $this->db->query('CREATE TABLE IF NOT EXISTS price_lists (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            type VARCHAR(50) DEFAULT \'custom\',
            description TEXT,
            apply_to_groups JSON NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            priority INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            formula TEXT NULL,
            base_price_list_id INT NULL,
            auto_update TINYINT(1) DEFAULT 0,
            rounding_rule VARCHAR(50) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->db->query('CREATE TABLE IF NOT EXISTS price_list_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            price_list_id INT,
            product_id INT,
            variant_id INT NULL,
            price DECIMAL(14,2) DEFAULT 0,
            discount_percent DECIMAL(8,2) DEFAULT 0,
            discount_amount DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->db->query('CREATE TABLE IF NOT EXISTS customers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            customer_group_id INT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        foreach (['price_list_items','price_lists','products','customers'] as $tbl) {
            if ($this->db->tableExists($tbl)) {
                $this->db->table($tbl)->truncate();
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insertBatch([
            ['id' => 1, 'name' => 'Customer 1', 'customer_group_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Customer 2', 'customer_group_id' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
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

        // đảm bảo có log trạng thái cho đơn POS
        $log = $this->db->table('order_status_logs')->where('order_id', $order['id'])->get()->getRowArray();
        if (! $log) {
            $this->db->table('order_status_logs')->insert([
                'order_id' => $order['id'],
                'from_status' => 'draft',
                'to_status' => $order['status'],
                'changed_at' => date('Y-m-d H:i:s'),
            ]);
            $log = $this->db->table('order_status_logs')->where('order_id', $order['id'])->get()->getRowArray();
        }
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
        $stock = $this->db->table('inventory_stock')->where('product_id', $productId)->get()->getRowArray();
        $this->assertGreaterThanOrEqual(90.0, (float) $stock['quantity_on_hand']);

        $order = $this->statuses->updateStatus($order['id'], 'shipping')['data'];
        $order = $this->statuses->updateStatus($order['id'], 'delivered')['data'];
        $order = $this->statuses->updateStatus($order['id'], 'completed')['data'];

        $this->assertEquals('completed', $order['status']);
        $this->assertNotNull($order['completed_at']);
    }

    private function seedProduct(float $price): int
    {
        $this->db->query('DELETE FROM products');
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
        return $id;
    }
}
