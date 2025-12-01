<?php

namespace Tests\Support\Database;

trait CashTransactionSchemaTrait
{
    protected function resetCashTransactionSchema(): void
    {
        $db = $this->getSchemaDb();
        // Ensure schema exists (golden migration) if core tables missing
        $existing = array_flip($db->listTables());
        
        // Always drop and recreate cash_transactions to ensure correct schema (AUTO_INCREMENT)
        $db->query("DROP TABLE IF EXISTS cash_transactions");
        $this->createCashTransactionsTableExplicitly($db);
        
        // Ensure reference tables exist even if migration was skipped
        if (! isset($existing['orders'])) {
            $this->createOrdersTableExplicitly($db);
        }
        if (! isset($existing['purchase_orders'])) {
            $this->createPurchaseOrdersTableExplicitly($db);
        }
        
        // Check other dependencies
        if (
            ! isset($existing['branches'])
            || ! isset($existing['users'])
            || ! isset($existing['returns'])
        ) {
            require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
            try {
                (new \App\Database\Migrations\TestSchemaSetup())->up();
            } catch (\Throwable $e) {
                // Ignore migration errors if some tables fail, we just need the basics
                error_log("DEBUG: Migration partial failure: " . $e->getMessage());
            }
        }
        
        $tables = ['orders', 'purchase_orders', 'branches', 'users', 'returns'];
        $this->truncateTables($db, $tables);
    }
    
    /**
     * Explicitly create cash_transactions table as backup when migration fails
     */
    private function createCashTransactionsTableExplicitly($db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS cash_transactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('RECEIPT','PAYMENT') NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            category VARCHAR(50) NOT NULL,
            payment_method VARCHAR(50) NULL,
            status VARCHAR(20) NULL,
            account_name VARCHAR(120) NULL,
            description TEXT NULL,
            reference_type VARCHAR(50) NULL,
            reference_id BIGINT UNSIGNED NULL,
            reference_code VARCHAR(100) NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_by_name VARCHAR(120) NULL,
            staff_name VARCHAR(120) NULL,
            payer_code VARCHAR(60) NULL,
            payer_name VARCHAR(180) NULL,
            payer_phone VARCHAR(50) NULL,
            payer_address VARCHAR(255) NULL,
            bank_account VARCHAR(60) NULL,
            transfer_note VARCHAR(255) NULL,
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
        error_log("DEBUG: CashTransactionSchemaTrait - cash_transactions table created explicitly");
    }

    /**
     * Minimal orders table for reference validation in tests.
     */
    private function createOrdersTableExplicitly($db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NULL,
            order_number VARCHAR(50) NULL,
            total DECIMAL(14,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'draft',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        error_log("DEBUG: CashTransactionSchemaTrait - orders table created explicitly");
    }

    /**
     * Minimal purchase_orders table for reference validation in tests.
     */
    private function createPurchaseOrdersTableExplicitly($db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS purchase_orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            po_number VARCHAR(50) NULL,
            code VARCHAR(50) NULL,
            branch_id BIGINT UNSIGNED NULL,
            payment_method VARCHAR(50) NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) DEFAULT 'draft',
            received_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        error_log("DEBUG: CashTransactionSchemaTrait - purchase_orders table created explicitly");
    }

    /**
     * Schema creation is handled by golden migration; keep stub for backward compat.
     */
    protected function createSupportingTables(): void
    {
        // no-op
    }

    protected function seedCashTransaction(array $data): int
    {
        $db = $this->getSchemaDb();
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
        $db->table('cash_transactions')->insert($payload);
        return (int) $db->insertID();
    }

    protected function seedCashTransactions(array $transactions): array
    {
        $ids = [];
        foreach ($transactions as $data) {
            $ids[] = $this->seedCashTransaction($data);
        }
        return $ids;
    }

    private function getSchemaDb()
    {
        return property_exists($this, 'db') && $this->db ? $this->db : \Config\Database::connect('tests');
    }

    private function truncateTables($db, array $tables): void
    {
        $existing = array_flip($db->listTables());
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) {
            if (isset($existing[$t])) {
                $db->table($t)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
