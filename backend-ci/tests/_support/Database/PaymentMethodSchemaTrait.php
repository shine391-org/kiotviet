<?php

namespace Tests\Support\Database;

/**
 * PaymentMethodSchemaTrait - Payment method database schema (MySQL-only)
 * 
 * @agent-trait: Payment method tables testing schema
 * @agent-pattern: MySQL-only schema creation (SQLite removed)
 * @agent-reusable: HIGH
 */
trait PaymentMethodSchemaTrait
{
    /**
     * Reset payment method schema for testing (MySQL-only)
     * 
     * @agent-pattern: Standard schema reset - COPY THIS
     * @agent-use: Call this in setUp() for payment method table tests
     */
    protected function resetPaymentSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        $this->db->query('DROP TABLE IF EXISTS `payment_methods`');
        $this->db->query('DROP TABLE IF EXISTS `order_items`');
        $this->db->query('DROP TABLE IF EXISTS `orders`');
        $this->db->query('DROP TABLE IF EXISTS `branches`');
        $this->db->query('DROP TABLE IF EXISTS `users`');

        // Create tables with MySQL-specific syntax
        $this->createSupportTables();
        $this->createPaymentMethodTables();
        $this->createOrderTables();
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Create payment method tables (MySQL-only)
     */
    private function createPaymentMethodTables(): void
    {
        $this->db->query("CREATE TABLE payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE,
            name VARCHAR(255),
            name_translations JSON,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create order tables (MySQL-only)
     */
    private function createOrderTables(): void
    {
        $this->db->query("CREATE TABLE orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            payment_method VARCHAR(50),
            total DECIMAL(10,2),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Base tables required by several feature tests.
     */
    private function createSupportTables(): void
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
