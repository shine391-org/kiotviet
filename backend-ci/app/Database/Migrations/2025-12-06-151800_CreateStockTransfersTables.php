<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockTransfersTables extends Migration
{
    public function up()
    {
        // stock_transfers - header table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'in_transit', 'received', 'cancelled'], 'default' => 'draft'],
            'from_branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'to_branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'transfer_date' => ['type' => 'DATETIME', 'null' => true],
            'receive_date' => ['type' => 'DATETIME', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'receiving_notes' => ['type' => 'TEXT', 'null' => true],
            'total_items' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'quantity_sent' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'value_sent' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'quantity_received' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'value_received' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'received_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('from_branch_id');
        $this->forge->addKey('to_branch_id');
        $this->forge->addKey('status');
        $this->forge->addKey('transfer_date');
        $this->forge->createTable('stock_transfers', true);

        // stock_transfer_items - line items
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'transfer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'product_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'product_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'unit' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'quantity_sent' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'quantity_received' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'total_price' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'notes' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('transfer_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('transfer_id', 'stock_transfers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('stock_transfer_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('stock_transfer_items', true);
        $this->forge->dropTable('stock_transfers', true);
    }
}
