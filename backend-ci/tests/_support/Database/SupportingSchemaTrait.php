<?php

namespace Tests\Support\Database;

/**
 * SupportingSchemaTrait - Supporting database schema (MySQL-only)
 * 
 * @agent-trait: Supporting tables testing schema
 * @agent-pattern: MySQL-only schema creation (SQLite removed)
 * @agent-reusable: HIGH
 */
trait SupportingSchemaTrait
{
    /**
     * Reset supporting schema for testing (MySQL-only)
     * 
     * @agent-pattern: Standard schema reset - COPY THIS
     * @agent-use: Call this in setUp() for supporting table tests
     */
    protected function resetSupportingSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        
        foreach ($this->db->listTables() as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        // Create tables with MySQL-specific syntax
        $this->createUserTables();
        $this->createBranchTables();
        $this->createOrderTables();
        $this->createOrderStatusLogTables();
        $this->createInventoryMovementTables();
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Create branch tables (MySQL-only)
     */
    private function createBranchTables(): void
    {
        $this->db->query("CREATE TABLE branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(20),
            name VARCHAR(255),
            phone VARCHAR(20),
            address TEXT,
            ward VARCHAR(100),
            district VARCHAR(100),
            city VARCHAR(100),
            status VARCHAR(20),
            is_active TINYINT DEFAULT 1,
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
            id INT AUTO_INCREMENT PRIMARY KEY,
            status VARCHAR(50),
            branch_id INT,
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
     * Create user tables (MySQL-only)
     */
    private function createUserTables(): void
    {
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
