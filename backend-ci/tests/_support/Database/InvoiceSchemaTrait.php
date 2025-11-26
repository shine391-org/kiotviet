<?php

namespace Tests\Support\Database;

/**
 * InvoiceSchemaTrait - Invoice database schema (MySQL-only)
 * 
 * @agent-trait: Invoice testing schema
 * @agent-pattern: MySQL-only schema creation (SQLite removed)
 * @agent-reusable: HIGH
 */
trait InvoiceSchemaTrait
{
    /**
     * Reset invoice schema for testing (MySQL-only)
     * 
     * @agent-pattern: Standard schema reset - COPY THIS
     * @agent-use: Call this in setUp() for invoice-related tests
     */
    protected function resetInvoiceSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        
        // Drop all existing tables in isolated test DB to avoid FK conflicts.
        foreach ($this->db->listTables() as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        // Drop invoice-related tables
        $this->db->query('DROP TABLE IF EXISTS activity_logs');
        $this->db->query('DROP TABLE IF EXISTS invoice_orders');
        $this->db->query('DROP TABLE IF EXISTS invoices');
        $this->db->query('DROP TABLE IF EXISTS orders');
        $this->db->query('DROP TABLE IF EXISTS customers');
        $this->db->query('DROP TABLE IF EXISTS branches');
        $this->db->query('DROP TABLE IF EXISTS users');

        // Create tables with MySQL-specific syntax
        $this->createBaseTables();
        $this->createOrderTables();
        $this->createInvoiceTables();
        $this->createInvoiceOrderTables();
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Create base user/branch/customer tables (MySQL-only)
     */
    private function createBaseTables(): void
    {
        $this->db->query("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
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
            customer_id INT,
            branch_id INT,
            order_date DATE,
            total DECIMAL(10,2),
            status VARCHAR(50),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create invoice tables (MySQL-only)
     */
    private function createInvoiceTables(): void
    {
        $this->db->query("CREATE TABLE invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) UNIQUE,
            customer_id INT,
            branch_id INT,
            issue_date DATE,
            due_date DATE,
            subtotal DECIMAL(10,2),
            vat_rate DECIMAL(5,2),
            vat_amount DECIMAL(10,2),
            total DECIMAL(10,2),
            pdf_path VARCHAR(255),
            notes TEXT,
            meta JSON,
            created_by INT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create invoice-order junction tables (MySQL-only)
     */
    private function createInvoiceOrderTables(): void
    {
        $this->db->query("CREATE TABLE invoice_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT,
            order_id INT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
