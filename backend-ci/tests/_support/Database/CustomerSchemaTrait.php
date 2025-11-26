<?php

namespace Tests\Support\Database;

/**
 * CustomerSchemaTrait - Customer database schema (MySQL-only)
 *
 * @agent-trait: Customer tables testing schema
 * @agent-pattern: MySQL-only schema creation with DevDatabaseTrait
 * @agent-reusable: HIGH
 */
trait CustomerSchemaTrait
{
    /**
     * Reset customer schema for testing (keeps other tables intact).
     *
     * @agent-use: Call in setUp() after setUpDatabase()
     */
    protected function resetCustomerSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach (['customers', 'branches', 'users'] as $table) {
            $this->db->query("DROP TABLE IF EXISTS {$table}");
        }
        $this->createBaseTables();
        $this->createCustomerTables();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createBaseTables(): void
    {
        $this->db->query("CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NULL,
            email VARCHAR(100) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE branches (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(50) NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createCustomerTables(): void
    {
        $this->db->query("CREATE TABLE customers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL DEFAULT 1,
            customer_group_id INT NULL,
            code VARCHAR(50) NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            phone2 VARCHAR(50) NULL,
            address VARCHAR(255) NULL,
            province VARCHAR(120) NULL,
            district VARCHAR(120) NULL,
            ward VARCHAR(120) NULL,
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
            birthday DATE NULL,
            created_by INT NULL,
            status VARCHAR(20) NULL,
            last_transaction_at DATETIME NULL,
            current_debt DECIMAL(14,2) DEFAULT 0,
            total_sales DECIMAL(14,2) DEFAULT 0,
            total_sales_net DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL,
            UNIQUE KEY unique_tax_code_per_org (organization_id, tax_code),
            KEY idx_customers_tax_code (tax_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
