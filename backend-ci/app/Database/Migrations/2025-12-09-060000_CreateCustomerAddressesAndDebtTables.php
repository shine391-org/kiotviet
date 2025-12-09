<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create customer_addresses and customer_debt_transactions tables
 * for the Customer Detail Panel tabs.
 */
class CreateCustomerAddressesAndDebtTables extends Migration
{
    public function up(): void
    {
        // ============================================
        // customer_addresses table
        // For "Địa chỉ nhận hàng" tab
        // ============================================
        if (! $this->db->tableExists('customer_addresses')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'customer_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => false,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'comment' => 'Tên địa chỉ (Nhà, Văn phòng, etc.)',
                ],
                'recipient_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'comment' => 'Tên người nhận',
                ],
                'phone' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                ],
                'province' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'district' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'ward' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'is_default' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('customer_id', false, false, 'idx_cust_addr_customer');
            $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE', 'fk_cust_addr_customer');
            $this->forge->createTable('customer_addresses', true);
        }

        // ============================================
        // customer_debt_transactions table
        // For "Nợ cần thu từ khách" tab
        // ============================================
        if (! $this->db->tableExists('customer_debt_transactions')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'customer_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => false,
                ],
                'order_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Reference to orders table if applicable',
                ],
                'code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'comment' => 'Transaction code (auto-generated or reference)',
                ],
                'type' => [
                    'type' => 'ENUM',
                    'constraint' => ['SALE', 'PAYMENT', 'ADJUSTMENT', 'DISCOUNT', 'REFUND'],
                    'default' => 'SALE',
                    'comment' => 'Transaction type',
                ],
                'value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0.00,
                    'comment' => 'Transaction value (positive for debt increase, negative for decrease)',
                ],
                'balance' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0.00,
                    'comment' => 'Running balance after this transaction',
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type' => 'INT',
                    'null' => true,
                ],
                'branch_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('customer_id', false, false, 'idx_cust_debt_customer');
            $this->forge->addKey('order_id', false, false, 'idx_cust_debt_order');
            $this->forge->addKey('type', false, false, 'idx_cust_debt_type');
            $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE', 'fk_cust_debt_customer');
            $this->forge->createTable('customer_debt_transactions', true);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('customer_debt_transactions')) {
            $this->forge->dropTable('customer_debt_transactions', true);
        }
        if ($this->db->tableExists('customer_addresses')) {
            $this->forge->dropTable('customer_addresses', true);
        }
    }
}
