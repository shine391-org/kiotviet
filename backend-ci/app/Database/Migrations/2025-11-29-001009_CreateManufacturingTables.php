<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Manufacturing tables for BOM and Work Orders.
 *
 * @agent-migration: Manufacturing core
 * @agent-pattern: Schema + constraints
 */
class CreateManufacturingTables extends Migration
{
    public function up()
    {
        $this->createBoms();
        $this->createBomItems();
        $this->createWorkOrders();
    }

    public function down()
    {
        $this->forge->dropTable('work_orders', true);
        $this->forge->dropTable('bom_items', true);
        $this->forge->dropTable('bill_of_materials', true);
    }

    private function createBoms(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'version' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 1],
            'uom' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'cost' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id', 'is_active']);
        $this->forge->addKey('version');
        $this->forge->createTable('bill_of_materials', true);
    }

    private function createBomItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'bom_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'component_product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'uom' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'scrap_percent' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('bom_id');
        $this->forge->addKey('component_product_id');
        $this->forge->createTable('bom_items', true);
    }

    private function createWorkOrders(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'bom_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'planned_start' => ['type' => 'DATETIME', 'null' => true],
            'planned_end' => ['type' => 'DATETIME', 'null' => true],
            'actual_start' => ['type' => 'DATETIME', 'null' => true],
            'actual_end' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addKey('bom_id');
        $this->forge->addKey('status');
        $this->forge->createTable('work_orders', true);
    }
}
