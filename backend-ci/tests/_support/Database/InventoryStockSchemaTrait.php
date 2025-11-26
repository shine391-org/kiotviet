<?php

namespace Tests\Support\Database;

/**
 * InventoryStockSchemaTrait - Inventory stock database schema (MySQL-only)
 * 
 * @agent-trait: Inventory stock tables testing schema
 * @agent-pattern: MySQL-only schema creation (SQLite removed)
 * @agent-reusable: HIGH
 */
trait InventoryStockSchemaTrait
{
    /**
     * Reset inventory stock schema for testing (MySQL-only)
     * 
     * @agent-pattern: Standard schema reset - COPY THIS
     * @agent-use: Call this in setUp() for inventory stock table tests
     */
    protected function resetInventoryStockSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        
        foreach ($this->db->listTables() as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        // Create tables with MySQL-specific syntax
        $this->createBaseTables();
        $this->createInventoryStockTables();
        $this->createInventoryAlertTables();
        
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
            warehouse_id INT,
            product_id INT,
            variant_id INT,
            quantity_on_hand DECIMAL(10,2),
            quantity_reserved DECIMAL(10,2),
            minimum_stock DECIMAL(10,2),
            last_movement_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create inventory alert tables (MySQL-only)
     */
    private function createInventoryAlertTables(): void
    {
        $this->db->query("CREATE TABLE inventory_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alert_type VARCHAR(50),
            product_id INT,
            variant_id INT,
            warehouse_id INT,
            current_quantity DECIMAL(10,2),
            threshold_quantity DECIMAL(10,2),
            status VARCHAR(50),
            resolved_by INT,
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Ensure supporting base tables exist for inventory-related tests.
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
