<?php

namespace Tests\Support\Database;

/**
 * CompleteSchemaTrait - unified MySQL schema for cross-module service tests.
 *
 * @agent-trait: Comprehensive MySQL schema for tests
 * @agent-pattern: DevDatabaseTrait companion schema
 * @agent-reusable: HIGH
 */
trait CompleteSchemaTrait
{
    /**
     * Reset and recreate all tables needed by service tests that span
     * orders, payments, pricing, invoices, returns, and inventory.
     *
     * @agent-pattern: Full schema reset
     * @agent-use: Call in setUp() after setUpDatabase()
     */
    protected function resetCompleteSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        $allTables = [
            'users','branches','customers',
            'products','product_categories',
            'product_category_links','product_variants_v2',
            'product_images','product_attributes',
            'product_attribute_options','product_attribute_values',
            'price_lists','price_list_items',
            'payment_methods',
            'orders','order_items','order_sequences',
            'returns','return_items',
            'invoices','invoice_orders',
            'inventory_stock','inventory_movements','inventory_alerts',
            'order_status_logs',
            'webhook_subscriptions','webhook_events'
        ];
        foreach ($allTables as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }
        // extra safety: drop any remaining tables
        foreach ($this->db->listTables() as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        $this->createBaseTables();
        $this->createProductTables();
        $this->createProductCategoryTables();
        $this->createProductVariantTables();
        $this->createProductImageTables();
        $this->createProductAttributeTables();
        $this->createProductAttributeOptionTables();
        $this->createProductAttributeValueTables();
        $this->createPriceListTables();
        $this->createPriceListItemTables();
        $this->createPaymentMethodTables();
        $this->createOrderTables();
        $this->createOrderItemTables();
        $this->createOrderSequenceTables();
        $this->createReturnTables();
        $this->createReturnItemTables();
        $this->createInvoiceTables();
        $this->createInvoiceOrderTables();
        $this->createInventoryStockTables();
        $this->createInventoryMovementTables();
        $this->createInventoryAlertTables();
        $this->createOrderStatusLogTables();
        $this->createWebhookTables();

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createBaseTables(): void
    {
        $this->db->query("DROP TABLE IF EXISTS users");
        $this->db->query("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NULL,
            email VARCHAR(100) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("DROP TABLE IF EXISTS branches");
        $this->db->query("CREATE TABLE branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("DROP TABLE IF EXISTS customers");
        $this->db->query("CREATE TABLE customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_group_id INT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductTables(): void
    {
        $sql = "CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_type VARCHAR(50) NULL,
            code VARCHAR(100) NOT NULL,
            barcode VARCHAR(100) NULL,
            name VARCHAR(255) NOT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            selling_price DECIMAL(10,2) DEFAULT 0,
            wholesale_price DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createProductCategoryTables(): void
    {
        $sql = "CREATE TABLE product_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NULL,
            product_id INT DEFAULT 0,
            level TINYINT DEFAULT 1,
            is_variant_group TINYINT DEFAULT 0,
            code VARCHAR(50),
            name VARCHAR(255),
            slug VARCHAR(255),
            description TEXT NULL,
            image VARCHAR(255) NULL,
            sort_order INT DEFAULT 0,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);

        $link = "CREATE TABLE product_category_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            category_id INT,
            created_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($link);
    }

    private function createProductVariantTables(): void
    {
        $sql = "CREATE TABLE product_variants_v2 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            variant_name VARCHAR(255) NULL,
            variant_signature VARCHAR(255) NULL,
            sku VARCHAR(100) NULL,
            barcode VARCHAR(100) NULL,
            price DECIMAL(10,2) DEFAULT 0,
            cost_price DECIMAL(10,2) DEFAULT 0,
            stock_quantity DECIMAL(10,2) DEFAULT 0,
            min_stock DECIMAL(10,2) DEFAULT 0,
            max_stock DECIMAL(10,2) DEFAULT 0,
            image_url VARCHAR(255) NULL,
            attributes TEXT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createProductImageTables(): void
    {
        $sql = "CREATE TABLE product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NULL,
            variant_id INT NULL,
            image_path VARCHAR(255) NULL,
            image_url VARCHAR(255) NULL,
            is_primary TINYINT DEFAULT 0,
            sort_order INT DEFAULT 0,
            file_name VARCHAR(255) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createProductAttributeTables(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS product_attributes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            slug VARCHAR(255),
            attribute_key VARCHAR(100),
            type VARCHAR(50),
            is_required TINYINT DEFAULT 0,
            is_filterable TINYINT DEFAULT 0,
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            is_visible TINYINT DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createProductAttributeOptionTables(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS product_attribute_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            attribute_id INT,
            option_name VARCHAR(255),
            color_code VARCHAR(50) NULL,
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createProductAttributeValueTables(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS product_attribute_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            variant_id INT NULL,
            attribute_id INT,
            option_id INT,
            value_text TEXT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createPriceListTables(): void
    {
        $sql = "CREATE TABLE price_lists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) DEFAULT 'custom',
            description TEXT NULL,
            apply_to_groups JSON NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            priority INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            formula TEXT NULL,
            base_price_list_id INT NULL,
            auto_update TINYINT(1) DEFAULT 0,
            rounding_rule VARCHAR(50) DEFAULT 'none',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createPriceListItemTables(): void
    {
        $sql = "CREATE TABLE price_list_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            price_list_id INT NOT NULL,
            product_id INT NULL,
            variant_id INT NULL,
            price DECIMAL(10,2) NOT NULL,
            discount_percent DECIMAL(5,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createOrderSequenceTables(): void
    {
        $sql = "CREATE TABLE order_sequences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            sequence_number INT DEFAULT 1,
            prefix VARCHAR(20) DEFAULT 'ORD',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createPaymentMethodTables(): void
    {
        $sql = "CREATE TABLE payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE,
            name VARCHAR(255) NOT NULL,
            name_translations JSON NULL,
            description TEXT NULL,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createOrderTables(): void
    {
        $sql = "CREATE TABLE orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NULL,
            customer_id INT NULL,
            customer_group_id INT NULL,
            branch_id INT NULL,
            order_date DATE NULL,
            order_type VARCHAR(50) DEFAULT 'online',
            payment_method VARCHAR(50) NULL,
            status VARCHAR(50) DEFAULT 'draft',
            subtotal DECIMAL(14,2) DEFAULT 0,
            discount_total DECIMAL(14,2) DEFAULT 0,
            shipping_fee DECIMAL(14,2) DEFAULT 0,
            total DECIMAL(14,2) DEFAULT 0,
            paid_amount DECIMAL(14,2) DEFAULT 0,
            debt_amount DECIMAL(14,2) DEFAULT 0,
            is_paid TINYINT(1) DEFAULT 0,
            applied_price_list_id INT NULL,
            shipping_name VARCHAR(255) NULL,
            shipping_phone VARCHAR(50) NULL,
            shipping_address TEXT NULL,
            shipping_ward VARCHAR(100) NULL,
            shipping_district VARCHAR(100) NULL,
            shipping_city VARCHAR(100) NULL,
            notes TEXT NULL,
            confirmed_at DATETIME NULL,
            processing_at DATETIME NULL,
            shipping_at DATETIME NULL,
            delivered_at DATETIME NULL,
            completed_at DATETIME NULL,
            cancelled_at DATETIME NULL,
            cancellation_reason TEXT NULL,
            cod_collected TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createOrderItemTables(): void
    {
        $sql = "CREATE TABLE order_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id INT NULL,
            variant_id INT NULL,
            quantity DECIMAL(14,3) DEFAULT 0,
            base_price DECIMAL(14,2) DEFAULT 0,
            final_price DECIMAL(14,2) DEFAULT 0,
            price_list_id INT NULL,
            price_list_name VARCHAR(255) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createReturnTables(): void
    {
        $sql = "CREATE TABLE returns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            return_number VARCHAR(50) UNIQUE,
            order_id INT,
            customer_id INT,
            return_amount DECIMAL(14,2),
            refund_shipping_fee TINYINT(1),
            refund_amount DECIMAL(14,2),
            refund_method VARCHAR(50) NULL,
            reason VARCHAR(50),
            reason_detail TEXT NULL,
            status VARCHAR(50),
            approved_by INT NULL,
            approved_at DATETIME NULL,
            rejected_by INT NULL,
            rejected_at DATETIME NULL,
            completed_at DATETIME NULL,
            notes TEXT NULL,
            lock_version INT DEFAULT 0,
            created_by INT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createReturnItemTables(): void
    {
        $sql = "CREATE TABLE return_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            return_id INT,
            order_item_id INT,
            quantity_returned DECIMAL(14,3),
            item_condition VARCHAR(50),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createInvoiceTables(): void
    {
        $sql = "CREATE TABLE invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) UNIQUE,
            customer_id INT,
            branch_id INT,
            issue_date DATE NULL,
            due_date DATE NULL,
            subtotal DECIMAL(10,2) DEFAULT 0,
            vat_rate DECIMAL(5,2) DEFAULT 0,
            vat_amount DECIMAL(10,2) DEFAULT 0,
            total DECIMAL(10,2) DEFAULT 0,
            pdf_path VARCHAR(255) NULL,
            notes TEXT NULL,
            meta JSON NULL,
            created_by INT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createInvoiceOrderTables(): void
    {
        $sql = "CREATE TABLE invoice_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT,
            order_id INT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createInventoryStockTables(): void
    {
        $sql = "CREATE TABLE inventory_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            warehouse_id INT,
            product_id INT,
            variant_id INT,
            quantity_on_hand DECIMAL(10,2) DEFAULT 0,
            quantity_reserved DECIMAL(10,2) DEFAULT 0,
            minimum_stock DECIMAL(10,2) DEFAULT 0,
            last_movement_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createInventoryMovementTables(): void
    {
        $sql = "CREATE TABLE inventory_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            product_id INT,
            variant_id INT NULL,
            type VARCHAR(50),
            quantity DECIMAL(10,2),
            reference_type VARCHAR(50) NULL,
            reference_id INT NULL,
            notes TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createInventoryAlertTables(): void
    {
        $sql = "CREATE TABLE inventory_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alert_type VARCHAR(50),
            product_id INT,
            variant_id INT,
            warehouse_id INT,
            current_quantity DECIMAL(10,2),
            threshold_quantity DECIMAL(10,2),
            status VARCHAR(50),
            resolved_by INT NULL,
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createOrderStatusLogTables(): void
    {
        $sql = "CREATE TABLE order_status_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            from_status VARCHAR(50) NULL,
            to_status VARCHAR(50) NOT NULL,
            changed_by INT NULL,
            notes TEXT NULL,
            changed_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    private function createWebhookTables(): void
    {
        $subs = "CREATE TABLE webhook_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event VARCHAR(100),
            target_url VARCHAR(500),
            secret VARCHAR(255) NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($subs);

        $events = "CREATE TABLE webhook_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event VARCHAR(100),
            payload JSON,
            status VARCHAR(50),
            attempts INT DEFAULT 0,
            last_error TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($events);
    }
}
