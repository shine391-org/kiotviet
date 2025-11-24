<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Minimal orders schema to support pricing. @agent-migration: Orders @agent-pattern: Schema + FK */
class CreateOrderTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'customer_group_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'order_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'draft'],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'discount_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'applied_price_list_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['customer_id', 'customer_group_id']);
        $this->forge->createTable('orders', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 1],
            'base_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'final_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'price_list_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'price_list_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('order_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('order_items', true);
        $this->forge->dropTable('orders', true);
    }
}
