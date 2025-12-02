<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add missing foreign keys for invoice_orders and return_items.
 *
 * @agent-migration: FK hardening
 * @agent-pattern: Cleanup + add constraints
 */
class AddMissingForeignKeys extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            // SQLite migrations skip FK alterations; main env uses MySQL.
            return;
        }

        $this->addInvoiceOrderFks();
        $this->addReturnItemFks();
    }

    public function down()
    {
        if ($this->isSqlite()) {
            return;
        }

        $this->dropFkIfExists('invoice_orders', 'fk_invoice_orders_invoice');
        $this->dropFkIfExists('invoice_orders', 'fk_invoice_orders_order');
        $this->dropFkIfExists('return_items', 'fk_return_items_return');
        $this->dropFkIfExists('return_items', 'fk_return_items_order_item');
    }

    private function addInvoiceOrderFks(): void
    {
        if (! $this->db->tableExists('invoice_orders') || ! $this->db->tableExists('invoices') || ! $this->db->tableExists('orders')) {
            return;
        }

        $this->cleanupOrphanedRows('invoice_orders', 'invoice_id', 'invoices');
        $this->cleanupOrphanedRows('invoice_orders', 'order_id', 'orders');

        if (! $this->foreignKeyExists('invoice_orders', 'fk_invoice_orders_invoice')) {
            $this->forge->addColumn('invoice_orders', [
                'CONSTRAINT fk_invoice_orders_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE ON UPDATE CASCADE',
            ]);
        }
        if (! $this->foreignKeyExists('invoice_orders', 'fk_invoice_orders_order')) {
            $this->forge->addColumn('invoice_orders', [
                'CONSTRAINT fk_invoice_orders_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE',
            ]);
        }
    }

    private function addReturnItemFks(): void
    {
        if (! $this->db->tableExists('return_items') || ! $this->db->tableExists('returns') || ! $this->db->tableExists('order_items')) {
            return;
        }

        $this->cleanupOrphanedRows('return_items', 'return_id', 'returns');
        $this->cleanupOrphanedRows('return_items', 'order_item_id', 'order_items');

        if (! $this->foreignKeyExists('return_items', 'fk_return_items_return')) {
            $this->forge->addColumn('return_items', [
                'CONSTRAINT fk_return_items_return FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE ON UPDATE CASCADE',
            ]);
        }
        if (! $this->foreignKeyExists('return_items', 'fk_return_items_order_item')) {
            $this->forge->addColumn('return_items', [
                'CONSTRAINT fk_return_items_order_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE ON UPDATE CASCADE',
            ]);
        }
    }

    private function cleanupOrphanedRows(string $table, string $column, string $refTable): void
    {
        $refIds = array_map('intval', array_column($this->db->table($refTable)->select('id')->get()->getResultArray(), 'id'));
        $existing = array_fill_keys($refIds, true);
        if (empty($existing)) {
            $this->db->table($table)->truncate();
            return;
        }

        $rows = $this->db->table($table)->select('id,' . $column)->get()->getResultArray();
        $orphanIds = [];
        foreach ($rows as $row) {
            $refId = (int) $row[$column];
            if (! isset($existing[$refId])) {
                $orphanIds[] = (int) $row['id'];
            }
        }
        if (! empty($orphanIds)) {
            $this->db->table($table)->whereIn('id', $orphanIds)->delete();
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $sql = "SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?";
        $dbName = $this->db->getDatabase();
        $row = $this->db->query($sql, [$dbName, $table, $constraint])->getRowArray();
        return ! empty($row);
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        if ($this->foreignKeyExists($table, $constraint)) {
            $this->forge->dropForeignKey($table, $constraint);
        }
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
