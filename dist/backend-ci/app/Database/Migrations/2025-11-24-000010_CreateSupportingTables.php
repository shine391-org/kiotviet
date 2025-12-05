<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Supporting tables: branches, order_status_logs, inventory_movements.
 *
 * @agent-migration: Supporting tables
 * @agent-pattern: Schema + FK ready
 */
class CreateSupportingTables extends Migration
{
    public function up()
    {
        $this->createBranches();
        $this->createOrderStatusLogs();
        $this->createInventoryMovements();
    }

    private function createBranches(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'address' => ['type' => 'TEXT', 'null' => true],
            'ward' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'district' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => 'active'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('is_active');
        $this->forge->createTable('branches', true);
    }

    private function createOrderStatusLogs(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'from_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'to_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'changed_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'changed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['order_id', 'changed_at'], false, false, 'idx_order_changed');
        $this->forge->createTable('order_status_logs', true);
    }

    private function createInventoryMovements(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'type' => ['type' => 'ENUM', 'constraint' => ['sale', 'return', 'adjustment', 'transfer_in', 'transfer_out'], 'null' => false],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'reference_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'reference_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['branch_id', 'product_id', 'created_at'], false, false, 'idx_branch_product');
        $this->forge->createTable('inventory_movements', true);
    }

    public function down()
    {
        $this->forge->dropTable('inventory_movements', true);
        $this->forge->dropTable('order_status_logs', true);
        $this->forge->dropTable('branches', true);
    }
}
