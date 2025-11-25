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
            'db_order_items', 'order_items', 'db_orders', 'orders',
            'db_price_list_items', 'price_list_items', 'db_price_lists', 'price_lists',
            'db_product_images', 'product_images', 'inventory_movement_items', 'invoice_items',
            'invoices', 'db_invoice_items', 'db_invoices', 'product_attribute_values',
            'db_product_attribute_values', 'inventory_stock', 'db_inventory_stock', 'stock',
            'db_stock', 'inventory_movements', 'db_inventory_movements',
            'db_inventory_movement_items', 'product_branch_stock', 'db_product_branch_stock',
            'product_stock_by_branch', 'db_product_stock_by_branch', 'stock_transactions',
            'db_stock_transactions', 'stock_transactions_v2', 'db_stock_transactions_v2',
            'product_categories', 'db_product_categories', 'product_category_links',
            'db_product_category_links', 'db_product_variants_v2', 'product_variants_v2',
            'db_product_attributes', 'product_attributes', 'db_products', 'products'
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
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Create product tables (MySQL-only)
     */
    private function createProductTables(): void
    {
        $this->db->query("CREATE TABLE db_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100),
            name VARCHAR(255),
            selling_price DECIMAL(10,2) DEFAULT 0,
            wholesale_price DECIMAL(10,2),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
        $this->db->query("CREATE TABLE db_product_variants_v2 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            sku VARCHAR(100),
            price DECIMAL(10,2),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
        $this->db->query("CREATE TABLE db_product_images (
             id INT AUTO_INCREMENT PRIMARY KEY,
             product_id INT,
             variant_id INT,
             image_path VARCHAR(500),
             created_at TIMESTAMP NULL,
             updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
        $this->db->query("CREATE TABLE db_product_attributes (
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
        $this->db->query("CREATE TABLE db_product_attribute_values (
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
        $this->db->query("CREATE TABLE db_price_lists (
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
        $this->db->query("CREATE TABLE db_price_list_items (
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
        $this->db->query("CREATE TABLE db_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            customer_group_id INT,
            order_date DATE NULL,
            status VARCHAR(50),
            subtotal DECIMAL(10,2),
            discount_total DECIMAL(10,2),
            total DECIMAL(10,2),
            applied_price_list_id INT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            customer_group_id INT,
            order_date DATE NULL,
            status VARCHAR(50),
            subtotal DECIMAL(10,2),
            discount_total DECIMAL(10,2),
            total DECIMAL(10,2),
            applied_price_list_id INT,
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
        $this->db->query("CREATE TABLE db_order_items (
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
}
