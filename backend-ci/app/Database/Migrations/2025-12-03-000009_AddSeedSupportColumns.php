<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bổ sung cột/bảng hỗ trợ seed demo (áp dụng cho môi trường deploy).
 *
 * @agent-migration: Seed support columns
 * @agent-pattern: Conditional alter + create if missing
 */
class AddSeedSupportColumns extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            return;
        }

        // Suppliers
        $this->ensureColumn('suppliers', 'name', "VARCHAR(255) NULL AFTER code");
        $this->ensureColumn('suppliers', 'type', "VARCHAR(50) DEFAULT 'company'");
        $this->ensureColumn('suppliers', 'payment_terms', "INT DEFAULT 0");

        // Purchase orders
        $this->ensureColumn('purchase_orders', 'supplier_id', "BIGINT UNSIGNED NULL AFTER code");
        $this->ensureColumn('purchase_orders', 'warehouse_id', "BIGINT UNSIGNED NULL AFTER branch_id");
        $this->ensureColumn('purchase_orders', 'order_date', "DATETIME NULL AFTER warehouse_id");
        $this->ensureColumn('purchase_orders', 'expected_date', "DATETIME NULL AFTER order_date");
        $this->ensureColumn('purchase_orders', 'total_amount', "DECIMAL(14,2) DEFAULT 0");
        $this->ensureColumn('purchase_orders', 'paid_amount', "DECIMAL(14,2) DEFAULT 0");
        $this->ensureColumn('purchase_orders', 'payment_status', "VARCHAR(50) DEFAULT 'unpaid'");
        $this->ensureColumn('purchase_orders', 'notes', "TEXT NULL");
        $this->ensureColumn('purchase_orders', 'created_by', "BIGINT UNSIGNED NULL");
        $this->ensureColumn('purchase_order_items', 'unit_price', "DECIMAL(14,2) NULL");
        $this->ensureColumn('purchase_order_items', 'total_price', "DECIMAL(14,2) NULL");

        // CRM
        $this->ensureColumn('leads', 'first_name', "VARCHAR(100) NULL");
        $this->ensureColumn('leads', 'last_name', "VARCHAR(100) NULL");
        $this->ensureColumn('leads', 'assigned_to', "BIGINT UNSIGNED NULL");
        $this->ensureColumn('opportunities', 'name', "VARCHAR(255) NULL");
        $this->ensureColumn('opportunities', 'amount', "DECIMAL(14,2) DEFAULT 0");
        $this->ensureColumn('opportunities', 'close_date', "DATE NULL");
        $this->ensureColumn('opportunities', 'assigned_to', "BIGINT UNSIGNED NULL");
        $this->ensureColumn('tasks', 'subject', "VARCHAR(255) NULL");
        $this->ensureColumn('tasks', 'description', "TEXT NULL");
        $this->ensureColumn('tasks', 'priority', "VARCHAR(20) NULL");
        $this->ensureColumn('tasks', 'related_to_type', "VARCHAR(50) NULL");
        $this->ensureColumn('tasks', 'related_to_id', "BIGINT UNSIGNED NULL");

        // Accounting
        $this->ensureColumn('chart_of_accounts', 'type', "VARCHAR(50) NULL");

        // HR
        $this->ensureColumn('employees', 'user_id', "BIGINT UNSIGNED NULL");
        $this->ensureColumn('employees', 'code', "VARCHAR(60) NULL");
        $this->ensureColumn('employees', 'first_name', "VARCHAR(120) NULL");
        $this->ensureColumn('employees', 'last_name', "VARCHAR(120) NULL");
        $this->ensureColumn('employees', 'email', "VARCHAR(120) NULL");
        $this->ensureColumn('employees', 'phone', "VARCHAR(50) NULL");
        $this->ensureColumn('employees', 'department_id', "BIGINT UNSIGNED NULL");
        $this->ensureColumn('employees', 'position_id', "BIGINT UNSIGNED NULL");
        $this->ensureColumn('employees', 'hire_date', "DATE NULL");
        $this->createTableIfMissing('departments', "
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(50) NOT NULL,
            description VARCHAR(255) NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ");
        $this->createTableIfMissing('positions', "
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            code VARCHAR(50) NOT NULL,
            department_id BIGINT UNSIGNED NULL,
            level INT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ");
        $this->createTableIfMissing('attendance', "
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            employee_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            check_in TIME NULL,
            check_out TIME NULL,
            status VARCHAR(20) DEFAULT 'present',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ");

        // Manufacturing
        $this->createTableIfMissing('bom', "
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            version VARCHAR(50) NULL,
            is_active TINYINT(1) DEFAULT 1,
            is_default TINYINT(1) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ");

        // Journal
        $this->createTableIfMissing('journal_entries', "
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            reference_number VARCHAR(50) NULL,
            date DATE NOT NULL,
            description VARCHAR(255) NULL,
            status VARCHAR(30) DEFAULT 'draft',
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ");
        $this->createTableIfMissing('journal_entry_lines', "
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            journal_entry_id BIGINT UNSIGNED NOT NULL,
            account_code VARCHAR(50) NOT NULL,
            debit DECIMAL(14,2) DEFAULT 0,
            credit DECIMAL(14,2) DEFAULT 0,
            description VARCHAR(255) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ");
    }

    public function down()
    {
        if ($this->isSqlite()) {
            return;
        }
        // Không drop cột để tránh mất dữ liệu; chỉ giữ down rỗng.
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        if ($this->db->tableExists($table) && ! $this->db->fieldExists($column, $table)) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function createTableIfMissing(string $table, string $definitionSql): void
    {
        if (! $this->db->tableExists($table)) {
            $this->db->query("CREATE TABLE {$table} ({$definitionSql}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
