<?php

namespace Tests\Support\Database;

/**
 * Cash Transaction Schema Trait for Testing.
 *
 * @agent-schema: Cash transactions testing
 * @agent-pattern: DevDatabaseTrait + Schema creation
 */
trait CashTransactionSchemaTrait
{
    /**
     * Create cash_transactions table for testing.
     */
    protected function resetCashTransactionSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        
        // Drop all tables first
        $this->db->query('DROP TABLE IF EXISTS cash_transactions');
        $this->db->query('DROP TABLE IF EXISTS branches');
        $this->db->query('DROP TABLE IF EXISTS users');
        $this->db->query('DROP TABLE IF EXISTS orders');
        $this->db->query('DROP TABLE IF EXISTS purchase_orders');
        
        // Create all tables
        $this->createCashTransactionTable();
        $this->createSupportingTablesDirect();
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createCashTransactionTable(): void
    {
        $this->db->query("CREATE TABLE cash_transactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('RECEIPT', 'PAYMENT') NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            category VARCHAR(50) NOT NULL,
            payment_method VARCHAR(50) NULL,
            status ENUM('approved','cancelled','pending') DEFAULT 'approved',
            account_name VARCHAR(255) NULL,
            bank_account VARCHAR(120) NULL,
            created_by_name VARCHAR(120) NULL,
            staff_name VARCHAR(120) NULL,
            payer_code VARCHAR(60) NULL,
            payer_name VARCHAR(180) NULL,
            payer_phone VARCHAR(30) NULL,
            payer_address VARCHAR(255) NULL,
            transfer_note VARCHAR(255) NULL,
            description TEXT NULL,
            reference_type VARCHAR(50) NULL,
            reference_id BIGINT UNSIGNED NULL,
            reference_code VARCHAR(100) NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            transaction_date DATE NOT NULL,
            note TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL,
            KEY idx_type_category (type, category),
            KEY idx_branch (branch_id),
            KEY idx_reference (reference_type, reference_id),
            KEY idx_transaction_date (transaction_date),
            KEY idx_created_by (created_by),
            KEY idx_deleted_at (deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Seed a cash transaction for testing.
     */
    protected function seedCashTransaction(array $data): int
    {
        $payload = array_merge([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('cash_transactions')->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * Seed multiple cash transactions.
     */
    protected function seedCashTransactions(array $transactions): array
    {
        $ids = [];
        foreach ($transactions as $data) {
            $ids[] = $this->seedCashTransaction($data);
        }
        return $ids;
    }

    /**
     * Create supporting tables directly (simplified).
     */
    protected function createSupportingTablesDirect(): void
    {
        $this->db->query("CREATE TABLE branches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(50) NOT NULL,
            address TEXT NULL,
            phone VARCHAR(50) NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NOT NULL,
            code VARCHAR(50) NOT NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE purchase_orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            po_number VARCHAR(50) NOT NULL,
            code VARCHAR(50) NOT NULL,
            branch_id BIGINT UNSIGNED NULL,
            payment_method VARCHAR(50) NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            received_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Create supporting tables for cash transaction testing (legacy method).
     */
    protected function createSupportingTables(): void
    {
        // This method is now called from resetCashTransactionSchema
        // Keeping for backward compatibility
    }

    /**
     * Clean up cash transaction test data.
     */
    protected function cleanupCashTransactionData(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('cash_transactions')->truncate();
        $this->db->table('branches')->truncate();
        $this->db->table('users')->truncate();
        $this->db->table('orders')->truncate();
        $this->db->table('purchase_orders')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
