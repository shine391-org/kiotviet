<?php

namespace Tests\Support\Database;

/**
 * PriceListSchemaTrait - Price list database schema (MySQL-only)
 * 
 * @agent-trait: Price list tables testing schema
 * @agent-pattern: MySQL-only schema creation (SQLite removed)
 * @agent-reusable: HIGH
 */
trait PriceListSchemaTrait
{
    /**
     * Reset price list schema for testing (MySQL-only)
     * 
     * @agent-pattern: Standard schema reset - COPY THIS
     * @agent-use: Call this in setUp() for price list table tests
     */
    protected function resetPriceListSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        // Drop all tables
        $tables = [
            'order_items', 'orders',
            'price_list_items', 'price_lists',
            'product_images', 'inventory_movement_items', 'invoice_items',
            'invoices', 'product_attribute_values',
            'inventory_stock', 'stock', 'inventory_movements', 'product_branch_stock',
            'product_stock_by_branch', 'stock_transactions', 'stock_transactions_v2',
            'product_categories', 'product_category_links', 'product_variants_v2',
            'product_attributes', 'products',
            'order_sequences'
        ];

        foreach ($tables as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        // Create tables with MySQL-specific syntax
        $this->createProductTables();
        $this->createProductVariantTables();
        $this->createProductImageTables();
        $this->createProductAttributeTables();
        $this->createProductAttributeValueTables();
        $this->createPriceListTables();
        $this->createPriceListItemTables();
        $this->createOrderTables();
        $this->createOrderItemTables();
        $this->createOrderSequenceTables();
        $this->createInventoryStockTables();
        $this->createSupportTables();
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Create product tables (MySQL-only)
     */
    private function createProductTables(): void
    {
        $this->db->query("CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100),
            name VARCHAR(255),
            selling_price DECIMAL(10,2) DEFAULT 0,
            wholesale_price DECIMAL(10,2),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create product variant tables (MySQL-only)
     */
    private function createProductVariantTables(): void
    {
        $this->db->query("CREATE TABLE product_variants_v2 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            sku VARCHAR(100),
            price DECIMAL(10,2),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create product image tables (MySQL-only)
     */
    private function createProductImageTables(): void
    {
        $this->db->query("CREATE TABLE product_images (
             id INT AUTO_INCREMENT PRIMARY KEY,
             product_id INT,
             variant_id INT,
             image_path VARCHAR(500),
             created_at TIMESTAMP NULL,
             updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create product attribute tables (MySQL-only)
     */
    private function createProductAttributeTables(): void
    {
        $this->db->query("CREATE TABLE product_attributes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            attribute_key VARCHAR(100),
            type VARCHAR(50),
            attribute_values TEXT,
            slug VARCHAR(100),
            sort_order INT,
            status VARCHAR(20),
            is_filterable TINYINT DEFAULT 0,
            is_required TINYINT DEFAULT 0,
            is_visible TINYINT DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create product attribute value tables (MySQL-only)
     */
    private function createProductAttributeValueTables(): void
    {
        $this->db->query("CREATE TABLE product_attribute_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            variant_id INT,
            attribute_id INT,
            option_id INT,
            value_text TEXT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create price list tables (MySQL-only)
     */
    private function createPriceListTables(): void
    {
        $this->db->query("CREATE TABLE price_lists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            type VARCHAR(50),
            description TEXT,
            apply_to_groups TEXT,
            start_date DATE NULL,
            end_date DATE NULL,
            priority INT DEFAULT 0,
            is_active TINYINT DEFAULT 1,
            formula TEXT,
            base_price_list_id INT,
            auto_update TINYINT DEFAULT 0,
            rounding_rule VARCHAR(50),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create price list item tables (MySQL-only)
     */
    private function createPriceListItemTables(): void
    {
        $this->db->query("CREATE TABLE price_list_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            price_list_id INT,
            product_id INT,
            variant_id INT,
            price DECIMAL(10,2),
            discount_percent DECIMAL(5,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create order tables (MySQL-only)
     */
    private function createOrderTables(): void
    {
        $schema = "CREATE TABLE %s (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NULL,
            customer_id INT NULL,
            customer_group_id INT NULL,
            branch_id INT NULL,
            order_date DATE NULL,
            order_type VARCHAR(50) DEFAULT 'online',
            payment_method VARCHAR(50) NULL,
            status VARCHAR(50) DEFAULT 'draft',
            subtotal DECIMAL(10,2) DEFAULT 0,
            discount_total DECIMAL(10,2) DEFAULT 0,
            shipping_fee DECIMAL(10,2) DEFAULT 0,
            total DECIMAL(10,2) DEFAULT 0,
            paid_amount DECIMAL(10,2) DEFAULT 0,
            debt_amount DECIMAL(10,2) DEFAULT 0,
            payment_status VARCHAR(20) NULL,
            is_paid TINYINT DEFAULT 0,
            applied_price_list_id INT NULL,
            shipping_name VARCHAR(255) NULL,
            shipping_phone VARCHAR(50) NULL,
            shipping_address TEXT NULL,
            shipping_ward VARCHAR(100) NULL,
            shipping_district VARCHAR(100) NULL,
            shipping_city VARCHAR(100) NULL,
            notes TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->query(sprintf($schema, 'orders'));
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
            price_list_id INT,
            price_list_name VARCHAR(255),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create order sequence tables (MySQL-only)
     */
    private function createOrderSequenceTables(): void
    {
        $this->db->query("CREATE TABLE order_sequences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            sequence_number INT DEFAULT 1,
            prefix VARCHAR(20) DEFAULT 'ORD',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createInventoryStockTables(): void
    {
        $this->db->query("CREATE TABLE inventory_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            warehouse_id INT NULL,
            product_id INT,
            variant_id INT NULL,
            quantity_on_hand DECIMAL(10,2) DEFAULT 0,
            quantity_reserved DECIMAL(10,2) DEFAULT 0,
            minimum_stock DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createSupportTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS branches (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), created_at DATETIME NULL, updated_at DATETIME NULL)");
        $this->db->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255), created_at DATETIME NULL, updated_at DATETIME NULL)");
    }
}
