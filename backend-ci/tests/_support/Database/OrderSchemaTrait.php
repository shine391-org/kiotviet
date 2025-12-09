<?php

namespace Tests\Support\Database;

/**
 * Reset Order-related tables for tests.
 *
 * @agent-test-support: Order schema reset
 * @agent-pattern: Truncate with FK disable
 */
trait OrderSchemaTrait
{
    protected function resetOrderSchema(): void
    {
        $tables = [
            'order_payments',
            'order_items',
            'orders',
            'invoices',
            'invoice_items',
            'customers',
            'products',
            'product_variants_v2',
            'branches',
            'users',
        ];

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $existing = array_flip($this->db->listTables());
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                $this->db->table($table)->truncate();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function createBranch(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'name' => 'Test Branch ' . uniqid(),
            'code' => 'BR-' . uniqid(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('branches')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createUser(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'username' => 'user_' . uniqid(),
            'email' => 'test_' . uniqid() . '@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Test User',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('users')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createCustomer(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'name' => 'Test Customer ' . uniqid(),
            'code' => 'CUST-' . uniqid(),
            'phone' => '0' . rand(100000000, 999999999),
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('customers')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createProduct(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'product_type' => 'goods',
            'code' => 'PROD-' . uniqid(),
            'name' => 'Test Product ' . uniqid(),
            'slug' => 'prod-' . uniqid(),
            'status' => 'active',
            'selling_price' => 100000,
            'purchase_price' => 50000,
            'stock_quantity' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('products')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createOrder(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'order_number' => 'ORD-' . uniqid(),
            'code' => 'ORD-' . uniqid(),
            'order_type' => 'standard',
            'order_date' => date('Y-m-d'),
            'status' => 'draft',
            'subtotal' => 0,
            'discount' => 0,
            'shipping_fee' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('orders')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createOrderItem(int $orderId, int $productId, array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_code' => 'PROD-' . uniqid(),
            'product_name' => 'Test Product',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount' => 0,
            'total' => 100000,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('order_items')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }
}
