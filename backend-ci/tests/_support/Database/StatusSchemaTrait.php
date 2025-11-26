<?php

namespace Tests\Support\Database;

/**
 * StatusSchemaTrait - Status-related database schema (MySQL-only)
 * 
 * @agent-trait: Status tables testing schema
 * @agent-pattern: MySQL-only schema creation (SQLite removed)
 * @agent-reusable: HIGH
 */
trait StatusSchemaTrait
{
    /**
     * Reset status schema for testing (MySQL-only)
     * 
     * @agent-pattern: Standard schema reset - COPY THIS
     * @agent-use: Call this in setUp() for status table tests
     */
    protected function resetStatusSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'inventory_stock',
            'inventory_movements',
            'order_status_logs',
            'order_items',
            'orders',
            'cash_transactions',
            'branches',
            'users'
        ] as $table) {
            $this->db->query("DROP TABLE IF EXISTS `{$table}`");
        }

        foreach ($this->db->listTables() as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        // Create tables with MySQL-specific syntax
        $this->createBaseTables();
        $this->createInventoryStockTables();
        $this->createInventoryMovementTables();
        $this->createOrderStatusLogTables();
        $this->createOrderTables();
        $this->createOrderItemTables();
        $this->createOrderPaymentTables();
        $this->createCashTransactionTables();
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Create inventory stock tables (MySQL-only)
     */
    private function createInventoryStockTables(): void
    {
        $this->db->query("CREATE TABLE inventory_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            warehouse_id INT NULL,
            product_id INT,
            variant_id INT,
            quantity_on_hand DECIMAL(10,2),
            quantity_reserved DECIMAL(10,2),
            minimum_stock DECIMAL(10,2),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create inventory movement tables (MySQL-only)
     */
    private function createInventoryMovementTables(): void
    {
        $this->db->query("CREATE TABLE inventory_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            product_id INT,
            variant_id INT,
            type VARCHAR(50),
            quantity DECIMAL(10,2),
            reference_type VARCHAR(50),
            reference_id INT,
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create order status log tables (MySQL-only)
     */
    private function createOrderStatusLogTables(): void
    {
        $this->db->query("CREATE TABLE order_status_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            from_status VARCHAR(50),
            to_status VARCHAR(50),
            notes TEXT,
            changed_by INT,
            changed_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create order tables (MySQL-only)
     */
    private function createOrderTables(): void
    {
        $this->db->query("CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(30),
            customer_id INT,
            customer_group_id INT,
            branch_id INT,
            order_date DATE NULL,
            status VARCHAR(50),
            order_type VARCHAR(50),
            payment_method VARCHAR(50),
            subtotal DECIMAL(10,2),
            discount_total DECIMAL(10,2),
            total DECIMAL(10,2),
            shipping_fee DECIMAL(10,2),
            paid_amount DECIMAL(10,2),
            debt_amount DECIMAL(10,2),
            payment_status VARCHAR(20) NULL,
            is_paid TINYINT,
            cod_collected TINYINT,
            applied_price_list_id INT NULL,
            shipping_name VARCHAR(50),
            shipping_phone VARCHAR(50),
            shipping_address TEXT,
            shipping_ward VARCHAR(50),
            shipping_district VARCHAR(50),
            shipping_city VARCHAR(50),
            notes TEXT,
            confirmed_at TIMESTAMP NULL,
            processing_at TIMESTAMP NULL,
            shipping_at TIMESTAMP NULL,
            delivered_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            cancelled_at TIMESTAMP NULL,
            cancellation_reason TEXT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Create order item tables (MySQL-only)
     */
    private function createOrderItemTables(): void
    {
        $this->db->query("CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            product_id INT,
            variant_id INT,
            quantity DECIMAL(10,2),
            base_price DECIMAL(10,2),
            final_price DECIMAL(10,2),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            price_list_id INT NULL,
            price_list_name VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createOrderPaymentTables(): void
    {
        $this->db->query("CREATE TABLE order_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            payment_method ENUM('CASH','BANK_TRANSFER','CARD','COD','EWALLET') NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            paid_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createCashTransactionTables(): void
    {
        $this->db->query("CREATE TABLE cash_transactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('RECEIPT','PAYMENT') NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
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
            KEY idx_reference (reference_type, reference_id),
            KEY idx_transaction_date (transaction_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Base tables required by status-related tests.
     */
    private function createBaseTables(): void
    {
        $this->db->query("CREATE TABLE branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NULL,
            code VARCHAR(50) NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NULL,
            email VARCHAR(100) NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
