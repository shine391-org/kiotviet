<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhancePurchaseOrders extends Migration
{
    public function up()
    {
        // Add columns to purchase_orders to match our enhanced schema
        $fields = [
            'partner_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'branch_id'
            ],
            'supplier_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'partner_id'
            ],
            'warehouse_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'supplier_id'
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'warehouse_id'
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'user_id'
            ],
            'order_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'created_by'
            ],
            'order_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'order_number'
            ],
            'expected_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'order_date'
            ],
            'received_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'expected_date'
            ],
            'subtotal' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0,
                'null' => false,
                'after' => 'received_date'
            ],
            'tax_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0,
                'null' => false,
                'after' => 'subtotal'
            ],
            'shipping_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0,
                'null' => false,
                'after' => 'tax_amount'
            ],
            'total_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0,
                'null' => false,
                'after' => 'shipping_fee'
            ],
            'paid_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0,
                'null' => false,
                'after' => 'total_amount'
            ],
            'payment_status' => [
                'type' => 'ENUM',
                'constraint' => ['unpaid', 'partial', 'paid'],
                'default' => 'unpaid',
                'null' => false,
                'after' => 'paid_amount'
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'payment_status'
            ],
        ];

        $this->forge->addColumn('purchase_orders', $fields);

        // Add foreign keys
        $this->db->query('ALTER TABLE purchase_orders 
            ADD CONSTRAINT purchase_orders_partner_id_foreign FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT purchase_orders_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT purchase_orders_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT purchase_orders_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT purchase_orders_created_by_foreign FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
        ');
    }

    public function down()
    {
        // Drop foreign keys first
        $this->forge->dropForeignKey('purchase_orders', 'purchase_orders_partner_id_foreign');
        $this->forge->dropForeignKey('purchase_orders', 'purchase_orders_supplier_id_foreign');
        $this->forge->dropForeignKey('purchase_orders', 'purchase_orders_warehouse_id_foreign');
        $this->forge->dropForeignKey('purchase_orders', 'purchase_orders_user_id_foreign');
        $this->forge->dropForeignKey('purchase_orders', 'purchase_orders_created_by_foreign');

        // Drop columns
        $this->forge->dropColumn('purchase_orders', [
            'partner_id', 'supplier_id', 'warehouse_id', 'user_id', 'created_by',
            'order_number', 'order_date', 'expected_date', 'received_date',
            'subtotal', 'tax_amount', 'shipping_fee', 'total_amount', 'paid_amount',
            'payment_status', 'notes'
        ]);
    }
}
