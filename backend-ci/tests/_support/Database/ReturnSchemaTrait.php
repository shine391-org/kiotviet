<?php

namespace Tests\Support\Database;

/**
 * ReturnSchemaTrait - Return database schema (MySQL-only)
 * 
 * @agent-trait: Return tables testing schema
 * @agent-pattern: MySQL-only schema creation (SQLite removed)
 * @agent-reusable: HIGH
 */
trait ReturnSchemaTrait
{
    /**
     * Reset return schema for testing (MySQL-only)
     * 
     * @agent-pattern: Standard schema reset - COPY THIS
     * @agent-use: Call this in setUp() for return table tests
     */
    protected function resetReturnSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        foreach (['return_items','returns','order_items','orders','customers','branches','users'] as $tbl) {
            $this->db->query("DROP TABLE IF EXISTS {$tbl}");
        }

        // Create tables with MySQL-specific syntax
        $this->createUserTables();
        $this->createCustomerTables();
        $this->createBranchTables();
        $this->createOrderTables();
        $this->createOrderItemTables();
        $this->createReturnTables();
        $this->createReturnItemTables();
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Create user tables (MySQL-only)
     */
    private function createUserTables(): void
    {
        $this->db->query('CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    /**
     * Create branch tables (MySQL-only)
     */
    private function createBranchTables(): void
    {
        $this->db->query('CREATE TABLE branches (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NULL,
            code VARCHAR(50) NULL,
            status VARCHAR(20) DEFAULT \'active\',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    
    /**
     * Create customer tables (MySQL-only)
     */
    private function createCustomerTables(): void
    {
        $this->db->query("CREATE TABLE customers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL DEFAULT 1,
            customer_group_id INT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            phone2 VARCHAR(50) NULL,
            gender ENUM('MALE','FEMALE','OTHER') NULL,
            facebook VARCHAR(255) NULL,
            customer_type ENUM('INDIVIDUAL','COMPANY','HOUSEHOLD') NOT NULL DEFAULT 'INDIVIDUAL',
            company_name VARCHAR(255) NULL,
            tax_code VARCHAR(20) NULL,
            buyer_name VARCHAR(255) NULL,
            invoice_company_name VARCHAR(255) NULL,
            invoice_address VARCHAR(500) NULL,
            invoice_province VARCHAR(120) NULL,
            invoice_district VARCHAR(120) NULL,
            invoice_ward VARCHAR(120) NULL,
            invoice_email VARCHAR(255) NULL,
            invoice_phone VARCHAR(50) NULL,
            cccd_cmnd VARCHAR(50) NULL,
            id_number VARCHAR(50) NULL,
            bank_account VARCHAR(50) NULL,
            bank_name VARCHAR(255) NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL,
            UNIQUE KEY unique_tax_code_per_org (organization_id, tax_code),
            KEY idx_customers_tax_code (tax_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create order tables (MySQL-only)
     */
    private function createOrderTables(): void
    {
        $this->db->query('CREATE TABLE orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            branch_id INT,
            status VARCHAR(50),
            total DECIMAL(14,2),
            shipping_fee DECIMAL(14,2),
            completed_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    
    /**
     * Create order item tables (MySQL-only)
     */
    private function createOrderItemTables(): void
    {
        $this->db->query('CREATE TABLE order_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            product_id INT,
            variant_id INT NULL,
            quantity DECIMAL(14,3),
            base_price DECIMAL(14,2),
            final_price DECIMAL(14,2),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    
    /**
     * Create return tables (MySQL-only)
     */
    private function createReturnTables(): void
    {
        $this->db->query('CREATE TABLE returns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            return_number VARCHAR(50) UNIQUE,
            order_id INT,
            customer_id INT,
            return_amount DECIMAL(14,2),
            refund_shipping_fee TINYINT(1),
            refund_amount DECIMAL(14,2),
            refund_method VARCHAR(50),
            reason VARCHAR(50),
            reason_detail TEXT,
            status VARCHAR(50),
            approved_by INT NULL,
            approved_at DATETIME NULL,
            rejected_by INT NULL,
            rejected_at DATETIME NULL,
            completed_at DATETIME NULL,
            notes TEXT,
            lock_version INT DEFAULT 0,
            created_by INT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    
    /**
     * Create return item tables (MySQL-only)
     */
    private function createReturnItemTables(): void
    {
        $this->db->query('CREATE TABLE return_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            return_id INT,
            order_item_id INT,
            quantity_returned DECIMAL(14,3),
            item_condition VARCHAR(50),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
}
