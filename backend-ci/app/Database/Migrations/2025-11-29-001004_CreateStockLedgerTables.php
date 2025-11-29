<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stock ledger/bin/reconciliation schema.
 *
 * @agent-migration: Stock ledger
 * @agent-pattern: Schema + constraints
 */
class CreateStockLedgerTables extends Migration
{
    public function up()
    {
        $this->createStockLedgers();
        $this->createStockBins();
        $this->createStockReconciliations();
        $this->createStockReconciliationItems();
    }

    public function down()
    {
        $this->forge->dropTable('stock_reconciliation_items', true);
        $this->forge->dropTable('stock_reconciliations', true);
        $this->forge->dropTable('stock_bins', true);
        $this->forge->dropTable('stock_ledgers', true);
    }

    private function createStockLedgers(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'warehouse_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'serial_number' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'movement_date' => ['type' => 'DATETIME', 'null' => false],
            'reference_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'reference_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'reference_seq' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'qty_delta' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'total_cost' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id', 'branch_id']);
        $this->forge->addKey(['reference_type', 'reference_id']);
        $this->forge->addUniqueKey(['reference_type', 'reference_id', 'reference_seq'], 'uq_ledger_ref_seq');
        $this->forge->createTable('stock_ledgers', true);
    }

    private function createStockBins(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'on_hand_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'reserved_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['product_id', 'variant_id', 'branch_id', 'batch_id'], 'uq_stock_bin');
        $this->forge->createTable('stock_bins', true);
    }

    private function createStockReconciliations(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'recon_number' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'approved_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('recon_number');
        $this->forge->createTable('stock_reconciliations', true);
    }

    private function createStockReconciliationItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'reconciliation_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'counted_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'current_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'variance_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('reconciliation_id');
        $this->forge->addKey(['product_id', 'batch_id']);
        $this->forge->createTable('stock_reconciliation_items', true);
    }
}
