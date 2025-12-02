<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Chuẩn hóa soft delete, index và kiểu dữ liệu quan trọng.
 *
 * @agent-migration: Soft delete + schema align
 * @agent-pattern: Column alters + indexes
 */
class SoftDeleteAndSchemaAdjust extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            return;
        }

        $this->addDeletedAtColumns();
        $this->updateOrderMoneyColumns();
        $this->updateProductColumns();
        $this->addIndexes();
    }

    public function down()
    {
        // Không rollback cột để tránh mất dữ liệu; indexes có thể bỏ qua.
    }

    private function addDeletedAtColumns(): void
    {
        $tables = [
            'returns',
            'return_items',
            'order_items',
            'delivery_note_items',
            'stock_reconciliation_items',
            'subscription_cycles',
        ];

        foreach ($tables as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $columns = $this->db->getFieldData($table);
            $hasDeletedAt = array_filter($columns, static fn ($col) => $col->name === 'deleted_at');
            if (! $hasDeletedAt) {
                $this->forge->addColumn($table, [
                    'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'],
                ]);
            }
        }
    }

    private function updateOrderMoneyColumns(): void
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }

        $this->fillMissingOrderNumbers();
        $this->normalizeOrderMoneyNulls();

        $cols = [
            'order_number' => 'VARCHAR(50) NOT NULL',
            'tax_total' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'rounding_adjustment' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'coupon_discount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'loyalty_discount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'subtotal' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'discount_total' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'shipping_fee' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'total' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'paid_amount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'debt_amount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
        ];
        foreach ($cols as $col => $type) {
            if ($this->columnExists('orders', $col)) {
                $this->db->query("ALTER TABLE orders MODIFY {$col} {$type}");
            }
        }
    }

    private function updateProductColumns(): void
    {
        if (! $this->db->tableExists('products')) {
            return;
        }
        $this->db->query("ALTER TABLE products MODIFY code VARCHAR(100) NOT NULL");
        $this->db->query("ALTER TABLE products MODIFY name VARCHAR(255) NOT NULL");
        $this->db->query("ALTER TABLE products MODIFY slug VARCHAR(255) NOT NULL");
    }

    private function addIndexes(): void
    {
        // Thêm index cho các FK truy vấn thường xuyên
        $indexes = [
            'orders' => [
                ['customer_group_id'],
                ['warehouse_id'],
                ['pos_profile_id'],
                ['pos_shift_id'],
                ['tax_template_id'],
            ],
            'cash_transactions' => [
                ['reference_id'],
            ],
        ];

        foreach ($indexes as $table => $keys) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            foreach ($keys as $cols) {
                $name = 'idx_' . implode('_', $cols);
                if ($this->indexExists($table, $name)) {
                    continue;
                }
                $this->forge->addKey($cols, false, false, $name);
                $this->forge->processIndexes($table);
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $sql = 'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1';
        return (bool) $this->db->query($sql, [$this->db->getDatabase(), $table, $indexName])->getRowArray();
    }

    private function fillMissingOrderNumbers(): void
    {
        if (! $this->columnExists('orders', 'order_number')) {
            return;
        }

        // Đảm bảo giá trị non-null trước khi set NOT NULL
        $this->db->query("UPDATE orders SET order_number = CONCAT('ORD-', LPAD(id, 6, '0')) WHERE order_number IS NULL OR order_number = ''");
    }

    private function normalizeOrderMoneyNulls(): void
    {
        $numericCols = [
            'tax_total',
            'rounding_adjustment',
            'coupon_discount',
            'loyalty_discount',
            'subtotal',
            'discount_total',
            'shipping_fee',
            'total',
            'paid_amount',
            'debt_amount',
        ];

        foreach ($numericCols as $col) {
            if ($this->columnExists('orders', $col)) {
                $this->db->query("UPDATE orders SET {$col} = 0 WHERE {$col} IS NULL");
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }

        $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
        return (bool) $this->db->query($sql, [$this->db->getDatabase(), $table, $column])->getRowArray();
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
