<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create stock_disposals and stock_disposal_items tables.
 */
class CreateStockDisposalsTables extends Migration
{
    public function up()
    {
        // Stock Disposals (header table)
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'branch_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'completed', 'cancelled'],
                'default' => 'draft',
            ],
            'disposed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'total_quantity' => [
                'type' => 'DECIMAL',
                'constraint' => '15,4',
                'default' => 0,
            ],
            'total_value' => [
                'type' => 'DECIMAL',
                'constraint' => '18,4',
                'default' => 0,
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
            'executor_id' => [
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('branch_id');
        $this->forge->addKey('status');
        $this->forge->addKey('disposed_at');
        $this->forge->addKey('created_by');
        $this->forge->addKey('executor_id');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('executor_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('stock_disposals', true);

        // Stock Disposal Items (line items)
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'disposal_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'variant_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'sku' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'quantity' => [
                'type' => 'DECIMAL',
                'constraint' => '15,4',
                'default' => 0,
            ],
            'cost_price' => [
                'type' => 'DECIMAL',
                'constraint' => '18,4',
                'default' => 0,
            ],
            'disposal_value' => [
                'type' => 'DECIMAL',
                'constraint' => '18,4',
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('disposal_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('variant_id');
        $this->forge->addForeignKey('disposal_id', 'stock_disposals', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants_v2', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('stock_disposal_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('stock_disposal_items', true);
        $this->forge->dropTable('stock_disposals', true);
    }
}
