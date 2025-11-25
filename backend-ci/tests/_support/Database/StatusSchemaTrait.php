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
        
        foreach ($this->db->listTables() as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        // Create tables with MySQL-specific syntax
        $this->createInventoryStockTables();
        $this->createInventoryMovementTables();
        $this->createOrderStatusLogTables();
        $this->createOrderTables();
        $this->createOrderItemTables();
        
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
            product_id INT,
            variant_id INT,
            quantity_on_hand DECIMAL(10,2),
            quantity_reserved DECIMAL(10,2),
            minimum_stock DECIMAL(10,2)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $this->db->query("CREATE TABLE db_inventory_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            product_id INT,
            variant_id INT,
            quantity_on_hand DECIMAL(10,2),
            quantity_reserved DECIMAL(10,2),
            minimum_stock DECIMAL(10,2)
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
        
        $this->db->query("CREATE TABLE db_inventory_movements (
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
        
        $this->db->query("CREATE TABLE db_order_status_logs (
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
        
        $this->db->query("CREATE TABLE db_orders (
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
        
        $this->db->query("CREATE TABLE db_order_items (
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
}
