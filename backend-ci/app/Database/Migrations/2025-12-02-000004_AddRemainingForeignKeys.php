<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bổ sung các FK còn thiếu cho các cột đơn lẻ sau đợt hardening.
 *
 * @agent-migration: Remaining FK hardening
 * @agent-pattern: Conditional FK add
 * @agent-reusable: MEDIUM
 */
class AddRemainingForeignKeys extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            return;
        }

        $links = [
            ['order_status_logs', 'order_id', 'orders', 'id', 'CASCADE'],
            ['orders', 'pos_profile_id', 'pos_profiles', 'id', 'SET NULL'],
            ['orders', 'pos_shift_id', 'pos_shifts', 'id', 'SET NULL'],
            ['purchase_invoices', 'credit_account_id', 'chart_of_accounts', 'id', 'SET NULL'],
            ['purchase_invoices', 'debit_account_id', 'chart_of_accounts', 'id', 'SET NULL'],
            ['stock_ledgers', 'batch_id', 'product_batches', 'id', 'SET NULL'],
        ];

        foreach ($links as [$table, $column, $refTable, $refColumn, $onDelete]) {
            $onDelete = $this->adjustOnDelete($table, $column, $onDelete);
            $constraint = $this->constraintName($table, $column, $refTable);

            if (! $this->canAddFk($table, $column, $refTable, $refColumn, $constraint)) {
                continue;
            }

            $this->alignColumnToReference($table, $column, $refTable, $refColumn);

            $this->forge->addColumn($table, [
                "CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE {$onDelete} ON UPDATE CASCADE",
            ]);
        }
    }

    public function down()
    {
        if ($this->isSqlite()) {
            return;
        }

        $links = [
            ['order_status_logs', 'order_id', 'orders'],
            ['orders', 'pos_profile_id', 'pos_profiles'],
            ['orders', 'pos_shift_id', 'pos_shifts'],
            ['purchase_invoices', 'credit_account_id', 'chart_of_accounts'],
            ['purchase_invoices', 'debit_account_id', 'chart_of_accounts'],
            ['stock_ledgers', 'batch_id', 'product_batches'],
        ];

        foreach ($links as [$table, $column, $refTable]) {
            $constraint = $this->constraintName($table, $column, $refTable);
            if ($this->foreignKeyExists($table, $constraint)) {
                $this->forge->dropForeignKey($table, $constraint);
            }
        }
    }

    private function adjustOnDelete(string $table, string $column, string $onDelete): string
    {
        if ($onDelete !== 'SET NULL') {
            return $onDelete;
        }
        $info = $this->columnInfo($table, $column);
        if (empty($info)) {
            return $onDelete;
        }
        $nullable = strtoupper((string) ($info['IS_NULLABLE'] ?? 'YES')) === 'YES';
        return $nullable ? $onDelete : 'CASCADE';
    }

    private function columnInfo(string $table, string $column): array
    {
        $sql = "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1";
        return $this->db->query($sql, [$this->db->getDatabase(), $table, $column])->getRowArray() ?? [];
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $sql = "SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=? AND TABLE_NAME=? AND CONSTRAINT_NAME=? LIMIT 1";
        return (bool) $this->db->query($sql, [$this->db->getDatabase(), $table, $constraint])->getRowArray();
    }

    private function canAddFk(string $table, string $column, string $refTable, string $refColumn, string $constraint): bool
    {
        return $this->db->tableExists($table)
            && $this->db->tableExists($refTable)
            && $this->columnExists($table, $column)
            && $this->columnExists($refTable, $refColumn)
            && ! $this->foreignKeyExists($table, $constraint);
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1";
        return (bool) $this->db->query($sql, [$this->db->getDatabase(), $table, $column])->getRowArray();
    }

    private function constraintName(string $table, string $column, string $refTable): string
    {
        return 'fk_' . substr($table, 0, 20) . '_' . substr($column, 0, 20);
    }

    private function alignColumnToReference(string $table, string $column, string $refTable, string $refColumn): void
    {
        $colInfo = $this->fullColumnInfo($table, $column);
        $refInfo = $this->fullColumnInfo($refTable, $refColumn);
        if (empty($colInfo) || empty($refInfo)) {
            return;
        }
        $colType = strtolower($colInfo['COLUMN_TYPE'] ?? '');
        $refType = strtolower($refInfo['COLUMN_TYPE'] ?? '');
        if ($colType === $refType) {
            return;
        }

        $nullable = strtoupper((string) ($colInfo['IS_NULLABLE'] ?? 'YES')) === 'YES';
        $nullClause = $nullable ? 'NULL' : 'NOT NULL';
        $defaultClause = '';
        if (array_key_exists('COLUMN_DEFAULT', $colInfo) && $colInfo['COLUMN_DEFAULT'] !== null) {
            $defaultValue = $this->db->escape($colInfo['COLUMN_DEFAULT']);
            $defaultClause = " DEFAULT {$defaultValue}";
        } elseif ($nullable) {
            $defaultClause = ' DEFAULT NULL';
        }

        $sql = "ALTER TABLE {$table} MODIFY {$column} {$refInfo['COLUMN_TYPE']} {$nullClause}{$defaultClause}";
        $this->db->query($sql);
    }

    private function fullColumnInfo(string $table, string $column): array
    {
        $sql = "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1";
        return $this->db->query($sql, [$this->db->getDatabase(), $table, $column])->getRowArray() ?? [];
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
