<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create purchase_returns and purchase_return_items tables
 * for tracking returns to suppliers (Trả hàng nhập)
 */
class CreatePurchaseReturnsTables extends Migration
{
    public function up()
    {
        // Purchase Returns table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'return_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'purchase_order_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'partner_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'branch_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'return_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'total_quantity' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'total_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'discount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'ncc_can_tra' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
                'comment' => 'Amount supplier needs to pay back',
            ],
            'ncc_da_tra' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
                'comment' => 'Amount supplier already paid back',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'draft',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'returned_by' => [
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

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('return_number');
        $this->forge->addKey('purchase_order_id');
        $this->forge->addKey('partner_id');
        $this->forge->addKey('branch_id');
        $this->forge->addKey('status');
        $this->forge->addKey('return_date');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('returned_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('purchase_returns', true);

        // Purchase Return Items table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'purchase_return_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => false,
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'variant_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'product_code' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'product_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'quantity' => [
                'type' => 'DECIMAL',
                'constraint' => '12,3',
                'default' => 0,
            ],
            'import_price' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
                'comment' => 'Original purchase price',
            ],
            'return_price' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
                'comment' => 'Return price (may differ from import)',
            ],
            'discount_per_item' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'notes' => [
                'type' => 'TEXT',
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('purchase_return_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('purchase_return_id', 'purchase_returns', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('purchase_return_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('purchase_return_items', true);
        $this->forge->dropTable('purchase_returns', true);
    }
}
