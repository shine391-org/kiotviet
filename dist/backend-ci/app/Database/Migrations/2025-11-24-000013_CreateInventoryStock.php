<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Inventory stock + alerts + valuation.
 *
 * @agent-migration: Inventory stock
 * @agent-pattern: Schema + constraints
 */
class CreateInventoryStock extends Migration
{
    public function up()
    {
        // inventory_stock
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'quantity_on_hand' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'quantity_reserved' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'minimum_stock' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => true],
            'last_movement_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['branch_id', 'product_id', 'variant_id'], 'uq_inventory_stock');
        $this->forge->addKey('product_id');
        $this->forge->createTable('inventory_stock', true);

        // inventory_alerts
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'alert_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'warehouse_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'current_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => true],
            'threshold_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'resolved_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id', 'warehouse_id']);
        $this->forge->createTable('inventory_alerts', true);

        // inventory_valuation (minimal)
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'warehouse_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'valuation_method' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'AVERAGE'],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'total_value' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'movement_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id', 'warehouse_id']);
        $this->forge->createTable('inventory_valuation', true);
    }

    public function down()
    {
        $this->forge->dropTable('inventory_valuation', true);
        $this->forge->dropTable('inventory_alerts', true);
        $this->forge->dropTable('inventory_stock', true);
    }
}
