<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestorePurchaseOrderItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'purchase_order_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'quantity' => ['type' => 'INT', 'constraint' => 11],
            'received_quantity' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('purchase_order_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('purchase_order_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('purchase_order_items', true);
    }
}
