<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to add missing foreign keys identified in DB audit.
 * 
 * Issues fixed:
 * 1. product_stock_by_branch: Missing FK to products and branches
 * 2. product_prices: Missing FK to customer_groups
 * 3. employees: Missing FK to departments, positions, users
 * 4. journal_entry_lines: Missing FK to journal_entries
 * 5. activity_logs: Missing FK to users
 * 6. positions: Missing FK to departments
 * 
 * Note: Uses IF NOT EXISTS pattern via try-catch to handle cases where FK already exists.
 */
class AddMissingForeignKeys extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // 1. product_stock_by_branch - Add FK to products and branches
        $this->safeAddFK('product_stock_by_branch', 'fk_psbb_product', 'product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->safeAddFK('product_stock_by_branch', 'fk_psbb_branch', 'branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');

        // 2. product_prices - Add FK to customer_groups
        $this->safeAddFK('product_prices', 'fk_product_prices_customer_group', 'customer_group_id', 'customer_groups', 'id', 'SET NULL', 'CASCADE');

        // 3. employees - Add FK to departments, positions, users
        $this->safeAddFK('employees', 'fk_employees_department', 'department_id', 'departments', 'id', 'SET NULL', 'CASCADE');
        $this->safeAddFK('employees', 'fk_employees_position', 'position_id', 'positions', 'id', 'SET NULL', 'CASCADE');
        $this->safeAddFK('employees', 'fk_employees_user', 'user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        // 4. journal_entry_lines - Add FK to journal_entries
        $this->safeAddFK('journal_entry_lines', 'fk_jel_journal_entry', 'journal_entry_id', 'journal_entries', 'id', 'CASCADE', 'CASCADE');

        // 5. activity_logs - Add FK to users (optional, can be NULL for system actions)
        $this->safeAddFK('activity_logs', 'fk_activity_logs_user', 'user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        // 6. positions - Add FK to departments
        $this->safeAddFK('positions', 'fk_positions_department', 'department_id', 'departments', 'id', 'SET NULL', 'CASCADE');

        // 7. attendance (old table) - Add FK to employees if table exists
        if ($this->db->tableExists('attendance')) {
            $this->safeAddFK('attendance', 'fk_attendance_employee', 'employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        }

        // 8. bom (old table) - Add FK to products if table exists
        if ($this->db->tableExists('bom')) {
            $this->safeAddFK('bom', 'fk_bom_product', 'product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        }

        $this->db->enableForeignKeyChecks();
    }

    /**
     * Safely add a foreign key constraint, ignoring if it already exists.
     */
    private function safeAddFK(
        string $table,
        string $constraintName,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete = 'CASCADE',
        string $onUpdate = 'CASCADE'
    ): void {
        try {
            $this->db->query("
                ALTER TABLE `{$table}`
                ADD CONSTRAINT `{$constraintName}` 
                    FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}`(`{$refColumn}`) 
                    ON DELETE {$onDelete} ON UPDATE {$onUpdate}
            ");
        } catch (\Exception $e) {
            // FK already exists or other issue - log and continue
            log_message('debug', "FK {$constraintName} on {$table}: " . $e->getMessage());
        }
    }

    /**
     * Safely drop a foreign key constraint, ignoring if it doesn't exist.
     */
    private function safeDropFK(string $table, string $constraintName): void
    {
        try {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`");
        } catch (\Exception $e) {
            // FK doesn't exist - log and continue
            log_message('debug', "Drop FK {$constraintName} on {$table}: " . $e->getMessage());
        }
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        // Remove FKs in reverse order (safe drop)
        if ($this->db->tableExists('bom')) {
            $this->safeDropFK('bom', 'fk_bom_product');
        }
        if ($this->db->tableExists('attendance')) {
            $this->safeDropFK('attendance', 'fk_attendance_employee');
        }
        $this->safeDropFK('positions', 'fk_positions_department');
        $this->safeDropFK('activity_logs', 'fk_activity_logs_user');
        $this->safeDropFK('journal_entry_lines', 'fk_jel_journal_entry');
        $this->safeDropFK('employees', 'fk_employees_user');
        $this->safeDropFK('employees', 'fk_employees_position');
        $this->safeDropFK('employees', 'fk_employees_department');
        $this->safeDropFK('product_prices', 'fk_product_prices_customer_group');
        $this->safeDropFK('product_stock_by_branch', 'fk_psbb_branch');
        $this->safeDropFK('product_stock_by_branch', 'fk_psbb_product');

        $this->db->enableForeignKeyChecks();
    }
}
