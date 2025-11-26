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
        // Debug: Log that we're creating tables
        error_log("Creating supporting tables...");
        
        // Create branches table
        $sql = "CREATE TABLE branches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(50) NOT NULL,
            address TEXT NULL,
            phone VARCHAR(50) NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $result = $this->db->query($sql);
        error_log("Branches table creation result: " . ($result ? 'SUCCESS' : 'FAILED'));

        // Create users table
        $sql = "CREATE TABLE users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $result = $this->db->query($sql);
        error_log("Users table creation result: " . ($result ? 'SUCCESS' : 'FAILED'));

        // Create orders table
        $sql = "CREATE TABLE orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NOT NULL,
            code VARCHAR(50) NOT NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $result = $this->db->query($sql);
        error_log("Orders table creation result: " . ($result ? 'SUCCESS' : 'FAILED'));

        // Create purchase_orders table
        $sql = "CREATE TABLE purchase_orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            po_number VARCHAR(50) NOT NULL,
            code VARCHAR(50) NOT NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $result = $this->db->query($sql);
        error_log("Purchase orders table creation result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        // Debug: List tables after creation
        $tables = $this->db->listTables();
        error_log("Tables after creation: " . implode(', ', $tables));
        
        // Debug: Check individual tables with raw SQL
        $branchesCheck = $this->db->query("SHOW TABLES LIKE 'branches'")->getResultArray();
        $usersCheck = $this->db->query("SHOW TABLES LIKE 'users'")->getResultArray();
        $ordersCheck = $this->db->query("SHOW TABLES LIKE 'orders'")->getResultArray();
        $purchaseOrdersCheck = $this->db->query("SHOW TABLES LIKE 'purchase_orders'")->getResultArray();
        
        error_log("Branches exists (raw SQL): " . (count($branchesCheck) > 0 ? 'YES' : 'NO'));
        error_log("Users exists (raw SQL): " . (count($usersCheck) > 0 ? 'YES' : 'NO'));
        error_log("Orders exists (raw SQL): " . (count($ordersCheck) > 0 ? 'YES' : 'NO'));
        error_log("Purchase orders exists (raw SQL): " . (count($purchaseOrdersCheck) > 0 ? 'YES' : 'NO'));
        
        // Debug: Check individual tables with CI4 methods
        error_log("Branches exists (CI4): " . ($this->db->tableExists('branches') ? 'YES' : 'NO'));
        error_log("Users exists (CI4): " . ($this->db->tableExists('users') ? 'YES' : 'NO'));
        error_log("Orders exists (CI4): " . ($this->db->tableExists('orders') ? 'YES' : 'NO'));
        error_log("Purchase orders exists (CI4): " . ($this->db->tableExists('purchase_orders') ? 'YES' : 'NO'));
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