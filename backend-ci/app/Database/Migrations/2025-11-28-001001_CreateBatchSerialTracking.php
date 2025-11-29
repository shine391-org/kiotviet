<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Batch/serial tracking schema.
 *
 * @agent-migration: Batch + serial tracking
 * @agent-pattern: Schema + constraints
 */
class CreateBatchSerialTracking extends Migration
{
    public function up()
    {
        $this->createProductBatches();
        $this->createProductSerialNumbers();
        $this->alterInventoryMovements();
        $this->alterOrderItems();
    }

    public function down()
    {
        $this->dropOrderItemColumns();
        $this->dropInventoryMovementColumns();
        $this->forge->dropTable('product_serial_numbers', true);
        $this->forge->dropTable('product_batches', true);
    }

    private function createProductBatches(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'warehouse_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'batch_number' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'manufacture_date' => ['type' => 'DATE', 'null' => true],
            'expiry_date' => ['type' => 'DATE', 'null' => true],
            'initial_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'current_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'cost_per_unit' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'supplier_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'reference_document' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id', 'variant_id']);
        $this->forge->addKey('expiry_date');
        $this->forge->addUniqueKey(['product_id', 'batch_number'], 'uq_product_batch_number');
        $this->forge->createTable('product_batches', true);
    }

    private function createProductSerialNumbers(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'serial_number' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'available'],
            'warranty_expiry_date' => ['type' => 'DATE', 'null' => true],
            'reserved_for_order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'reserved_at' => ['type' => 'DATETIME', 'null' => true],
            'sold_to_order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'sold_date' => ['type' => 'DATETIME', 'null' => true],
            'returned_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id', 'variant_id']);
        $this->forge->addKey(['status', 'product_id'], false, false, 'idx_serial_status_product');
        $this->forge->addKey('reserved_for_order_id');
        $this->forge->addUniqueKey('serial_number', 'uq_serial_number');
        $this->forge->createTable('product_serial_numbers', true);
    }

    private function alterInventoryMovements(): void
    {
        if (! $this->db->fieldExists('batch_id', 'inventory_movements')) {
            $this->forge->addColumn('inventory_movements', [
                'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'variant_id'],
            ]);
        }
        if (! $this->db->fieldExists('serial_number', 'inventory_movements')) {
            $this->forge->addColumn('inventory_movements', [
                'serial_number' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'batch_id'],
            ]);
        }
    }

    private function alterOrderItems(): void
    {
        if (! $this->db->fieldExists('batch_id', 'order_items')) {
            $this->forge->addColumn('order_items', [
                'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'variant_id'],
            ]);
        }
        if (! $this->db->fieldExists('serial_numbers', 'order_items')) {
            $this->forge->addColumn('order_items', [
                'serial_numbers' => ['type' => 'TEXT', 'null' => true, 'after' => 'batch_id'],
            ]);
        }
    }

    private function dropInventoryMovementColumns(): void
    {
        if ($this->db->fieldExists('serial_number', 'inventory_movements')) {
            $this->forge->dropColumn('inventory_movements', 'serial_number');
        }
        if ($this->db->fieldExists('batch_id', 'inventory_movements')) {
            $this->forge->dropColumn('inventory_movements', 'batch_id');
        }
    }

    private function dropOrderItemColumns(): void
    {
        if ($this->db->fieldExists('serial_numbers', 'order_items')) {
            $this->forge->dropColumn('order_items', 'serial_numbers');
        }
        if ($this->db->fieldExists('batch_id', 'order_items')) {
            $this->forge->dropColumn('order_items', 'batch_id');
        }
    }
}
