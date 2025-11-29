<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Golden schema for test DB (group "tests").
 * Single source of truth to avoid scattered DDL in traits.
 */
class TestSchemaSetup extends Migration
{
    protected $DBGroup = 'tests';

    public function up()
    {
        $this->dropAll();

        $this->createBaseTables();
        $this->createProductTables();
        $this->createProductCategoryTables();
        $this->createProductVariantTables();
        $this->createProductImageTables();
        $this->createProductAttributeTables();
        $this->createProductAttributeOptionTables();
        $this->createProductAttributeValueTables();
        $this->createProductBatchTables();
        $this->createProductSerialNumberTables();
        $this->createDeliveryNoteTables();
        $this->createPriceListTables();
        $this->createPriceListItemTables();
        $this->createPaymentMethodTables();
        $this->createPurchaseOrderTables();
        $this->createOrderTables();
        $this->createOrderItemTables();
        $this->createOrderPaymentTables();
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
        $this->createCashTransactionTables();
    }

    public function down()
    {
        $this->dropAll();
    }

    private function dropAll(): void
    {
        $tables = [
            'users','branches','customers','factories',
            'warehouses','inventory_valuation',
            'attributes','attribute_options',
            'products','product_categories','product_category_links','product_variants_v2',
            'product_images','product_attributes','product_attribute_options','product_attribute_values',
            'product_batches','product_serial_numbers','delivery_note_items','delivery_notes',
            'price_lists','price_list_items',
            'payment_methods','purchase_orders',
            'orders','order_items','order_sequences','order_payments','order_status_logs',
            'returns','return_items',
            'invoices','invoice_orders',
            'inventory_stock','inventory_movements','inventory_alerts',
            'webhook_subscriptions','webhook_events',
            'cash_transactions'
        ];
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createBaseTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) NULL, email VARCHAR(100) NULL, status VARCHAR(20) DEFAULT 'active', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS branches (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, code VARCHAR(20) NULL, status VARCHAR(20) DEFAULT 'active', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS warehouses (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, code VARCHAR(50) NULL, branch_id INT NULL, status VARCHAR(20) DEFAULT 'active', created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS customers (id INT AUTO_INCREMENT PRIMARY KEY, organization_id INT UNSIGNED NOT NULL DEFAULT 1, customer_group_id INT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NULL, phone VARCHAR(50) NULL, phone2 VARCHAR(50) NULL, gender ENUM('MALE','FEMALE','OTHER') NULL, facebook VARCHAR(255) NULL, customer_type ENUM('INDIVIDUAL','COMPANY','HOUSEHOLD') NOT NULL DEFAULT 'INDIVIDUAL', company_name VARCHAR(255) NULL, tax_code VARCHAR(20) NULL, buyer_name VARCHAR(255) NULL, invoice_company_name VARCHAR(255) NULL, invoice_address VARCHAR(500) NULL, invoice_province VARCHAR(120) NULL, invoice_district VARCHAR(120) NULL, invoice_ward VARCHAR(120) NULL, invoice_email VARCHAR(255) NULL, invoice_phone VARCHAR(50) NULL, cccd_cmnd VARCHAR(50) NULL, id_number VARCHAR(50) NULL, bank_account VARCHAR(50) NULL, bank_name VARCHAR(255) NULL, notes TEXT NULL, code VARCHAR(50) NULL, address VARCHAR(500) NULL, province VARCHAR(120) NULL, district VARCHAR(120) NULL, ward VARCHAR(120) NULL, birthday DATE NULL, created_by INT NULL, status VARCHAR(20) DEFAULT 'active', last_transaction_at DATETIME NULL, current_debt DECIMAL(15,2) DEFAULT 0, total_sales DECIMAL(15,2) DEFAULT 0, total_sales_net DECIMAL(15,2) DEFAULT 0, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL, UNIQUE KEY unique_tax_code_per_org (organization_id, tax_code), KEY idx_customers_tax_code (tax_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, product_type VARCHAR(50) NULL, code VARCHAR(100) NOT NULL, barcode VARCHAR(100) NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NULL, brand VARCHAR(255) NULL, unit VARCHAR(50) NULL, has_variants TINYINT(1) DEFAULT 0, image VARCHAR(255) NULL, images TEXT NULL, weight DECIMAL(10,2) NULL, dimensions VARCHAR(100) NULL, description TEXT NULL, content TEXT NULL, is_active TINYINT(1) DEFAULT 1, is_available_online TINYINT(1) DEFAULT 0, is_featured TINYINT(1) DEFAULT 0, status ENUM('active','inactive') DEFAULT 'active', selling_price DECIMAL(10,2) DEFAULT 0, wholesale_price DECIMAL(10,2) DEFAULT 0, purchase_price DECIMAL(10,2) DEFAULT 0, stock_quantity INT DEFAULT 0, alert_stock INT DEFAULT 0, meta_title VARCHAR(255) NULL, meta_description VARCHAR(500) NULL, meta_keywords VARCHAR(500) NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductCategoryTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_categories (id INT AUTO_INCREMENT PRIMARY KEY, parent_id INT NULL, product_id INT DEFAULT 0, level TINYINT DEFAULT 1, is_variant_group TINYINT DEFAULT 0, code VARCHAR(50), name VARCHAR(255), slug VARCHAR(255), description TEXT NULL, image VARCHAR(255) NULL, sort_order INT DEFAULT 0, status ENUM('active','inactive') DEFAULT 'active', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS product_category_links (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, category_id INT, created_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductVariantTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_variants_v2 (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, variant_name VARCHAR(255) NULL, variant_signature VARCHAR(255) NULL, sku VARCHAR(100) NULL, barcode VARCHAR(100) NULL, price DECIMAL(10,2) DEFAULT 0, cost_price DECIMAL(10,2) DEFAULT 0, stock_quantity DECIMAL(10,2) DEFAULT 0, min_stock DECIMAL(10,2) DEFAULT 0, max_stock DECIMAL(10,2) DEFAULT 0, image_url VARCHAR(255) NULL, attributes TEXT NULL, status ENUM('active','inactive') DEFAULT 'active', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductImageTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_images (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NULL, variant_id INT NULL, image_path VARCHAR(255) NULL, image_url VARCHAR(255) NULL, is_primary TINYINT DEFAULT 0, sort_order INT DEFAULT 0, file_name VARCHAR(255) NULL, deleted_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductAttributeTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_attributes (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), slug VARCHAR(255), attribute_key VARCHAR(100), type VARCHAR(50), is_required TINYINT DEFAULT 0, is_filterable TINYINT DEFAULT 0, sort_order INT DEFAULT 0, status VARCHAR(20) DEFAULT 'active', is_visible TINYINT DEFAULT 1, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductAttributeOptionTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_attribute_options (id INT AUTO_INCREMENT PRIMARY KEY, attribute_id INT, option_name VARCHAR(255), color_code VARCHAR(50) NULL, sort_order INT DEFAULT 0, status VARCHAR(20) DEFAULT 'active', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductAttributeValueTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_attribute_values (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, variant_id INT NULL, attribute_id INT, option_id INT, value_text TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductBatchTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_batches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            branch_id BIGINT UNSIGNED NULL,
            warehouse_id BIGINT UNSIGNED NULL,
            batch_number VARCHAR(120) NOT NULL,
            manufacture_date DATE NULL,
            expiry_date DATE NULL,
            initial_quantity DECIMAL(12,3) DEFAULT 0,
            current_quantity DECIMAL(12,3) DEFAULT 0,
            cost_per_unit DECIMAL(14,4) DEFAULT 0,
            supplier_name VARCHAR(255) NULL,
            reference_document VARCHAR(160) NULL,
            status VARCHAR(30) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_product_batch_number (product_id, batch_number),
            KEY idx_product_batch_expiry (expiry_date),
            KEY idx_product_batch_product (product_id, variant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createProductSerialNumberTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_serial_numbers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            batch_id BIGINT UNSIGNED NULL,
            serial_number VARCHAR(160) NOT NULL,
            status VARCHAR(30) DEFAULT 'available',
            warranty_expiry_date DATE NULL,
            reserved_for_order_id BIGINT UNSIGNED NULL,
            reserved_at DATETIME NULL,
            sold_to_order_id BIGINT UNSIGNED NULL,
            sold_date DATETIME NULL,
            returned_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_serial_number (serial_number),
            KEY idx_serial_status_product (status, product_id),
            KEY idx_serial_reserved (reserved_for_order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createDeliveryNoteTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS delivery_notes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            delivery_number VARCHAR(50) NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            branch_id BIGINT UNSIGNED NULL,
            delivery_date DATE NULL,
            expected_delivery_date DATE NULL,
            status VARCHAR(30) DEFAULT 'draft',
            shipping_address TEXT NULL,
            tracking_number VARCHAR(120) NULL,
            carrier VARCHAR(120) NULL,
            notes TEXT NULL,
            confirmed_by BIGINT UNSIGNED NULL,
            confirmed_at DATETIME NULL,
            delivered_by BIGINT UNSIGNED NULL,
            delivered_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_delivery_number (delivery_number),
            KEY idx_delivery_order_branch (order_id, branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS delivery_note_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            delivery_note_id BIGINT UNSIGNED NOT NULL,
            order_item_id BIGINT UNSIGNED NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            batch_id BIGINT UNSIGNED NULL,
            serial_number VARCHAR(160) NULL,
            quantity DECIMAL(12,3) DEFAULT 0,
            delivered_quantity DECIMAL(12,3) DEFAULT 0,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_dn_items_note (delivery_note_id),
            KEY idx_dn_items_product (product_id, variant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createPriceListTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS price_lists (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, type VARCHAR(50) DEFAULT 'custom', description TEXT NULL, apply_to_groups JSON NULL, start_date DATE NULL, end_date DATE NULL, priority INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, formula TEXT NULL, base_price_list_id INT NULL, auto_update TINYINT(1) DEFAULT 0, rounding_rule VARCHAR(50) DEFAULT 'none', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createPriceListItemTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS price_list_items (id INT AUTO_INCREMENT PRIMARY KEY, price_list_id INT NOT NULL, product_id INT NULL, variant_id INT NULL, price DECIMAL(10,2) NOT NULL, discount_percent DECIMAL(5,2) DEFAULT 0, discount_amount DECIMAL(10,2) DEFAULT 0, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createOrderSequenceTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS order_sequences (id INT AUTO_INCREMENT PRIMARY KEY, branch_id INT, sequence_number INT DEFAULT 1, prefix VARCHAR(20) DEFAULT 'ORD', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createPaymentMethodTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS payment_methods (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) UNIQUE, name VARCHAR(255) NOT NULL, name_translations JSON NULL, description TEXT NULL, is_active TINYINT(1) DEFAULT 1, display_order INT DEFAULT 0, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createPurchaseOrderTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS purchase_orders (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, po_number VARCHAR(50) NULL, code VARCHAR(50) NULL, branch_id BIGINT UNSIGNED NULL, payment_method VARCHAR(50) NULL, total DECIMAL(12,2) NOT NULL DEFAULT 0.00, status VARCHAR(50) DEFAULT 'draft', received_at DATETIME NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createOrderTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS orders (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) NULL, order_number VARCHAR(50) NULL, customer_id INT NULL, customer_group_id INT NULL, branch_id INT NULL, order_date DATE NULL, order_type VARCHAR(50) DEFAULT 'online', payment_method VARCHAR(50) NULL, status VARCHAR(50) DEFAULT 'draft', subtotal DECIMAL(14,2) DEFAULT 0, discount_total DECIMAL(14,2) DEFAULT 0, shipping_fee DECIMAL(14,2) DEFAULT 0, total DECIMAL(14,2) DEFAULT 0, paid_amount DECIMAL(14,2) DEFAULT 0, debt_amount DECIMAL(14,2) DEFAULT 0, payment_status VARCHAR(20) NULL, is_paid TINYINT(1) DEFAULT 0, applied_price_list_id INT NULL, shipping_name VARCHAR(255) NULL, shipping_phone VARCHAR(50) NULL, shipping_address TEXT NULL, shipping_ward VARCHAR(100) NULL, shipping_district VARCHAR(100) NULL, shipping_city VARCHAR(100) NULL, notes TEXT NULL, confirmed_at DATETIME NULL, processing_at DATETIME NULL, shipping_at DATETIME NULL, delivered_at DATETIME NULL, completed_at DATETIME NULL, cancelled_at DATETIME NULL, cancellation_reason TEXT NULL, cod_collected TINYINT(1) DEFAULT 0, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createOrderItemTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS order_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id INT NULL,
            variant_id INT NULL,
            batch_id BIGINT UNSIGNED NULL,
            serial_numbers TEXT NULL,
            quantity DECIMAL(14,3) DEFAULT 0,
            base_price DECIMAL(14,2) DEFAULT 0,
            final_price DECIMAL(14,2) DEFAULT 0,
            price_list_id INT NULL,
            price_list_name VARCHAR(255) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createOrderPaymentTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS order_payments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, payment_method VARCHAR(20), amount DECIMAL(15,2) NOT NULL DEFAULT 0, paid_at DATETIME NULL, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createCashTransactionTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS cash_transactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, type ENUM('RECEIPT','PAYMENT') NOT NULL, amount DECIMAL(12,2) NOT NULL, category VARCHAR(50) NOT NULL, payment_method VARCHAR(50) NULL, status VARCHAR(20) NULL, account_name VARCHAR(120) NULL, description TEXT NULL, reference_type VARCHAR(50) NULL, reference_id BIGINT UNSIGNED NULL, reference_code VARCHAR(100) NULL, branch_id BIGINT UNSIGNED NOT NULL, created_by BIGINT UNSIGNED NOT NULL, created_by_name VARCHAR(120) NULL, staff_name VARCHAR(120) NULL, payer_code VARCHAR(60) NULL, payer_name VARCHAR(180) NULL, payer_phone VARCHAR(50) NULL, payer_address VARCHAR(255) NULL, bank_account VARCHAR(60) NULL, transfer_note VARCHAR(255) NULL, transaction_date DATE NOT NULL, note TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL, KEY idx_type_category (type, category), KEY idx_branch (branch_id), KEY idx_reference (reference_type, reference_id), KEY idx_transaction_date (transaction_date), KEY idx_created_by (created_by), KEY idx_deleted_at (deleted_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createReturnTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS returns (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, return_number VARCHAR(50) UNIQUE, order_id INT, customer_id INT, return_amount DECIMAL(14,2), refund_shipping_fee TINYINT(1), refund_amount DECIMAL(14,2), refund_method VARCHAR(50) NULL, reason VARCHAR(50), reason_detail TEXT NULL, status VARCHAR(50), approved_by INT NULL, approved_at DATETIME NULL, rejected_by INT NULL, rejected_at DATETIME NULL, completed_at DATETIME NULL, notes TEXT NULL, lock_version INT DEFAULT 0, created_by INT NULL, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createReturnItemTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS return_items (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, return_id INT, order_item_id INT, quantity_returned DECIMAL(14,3), item_condition VARCHAR(50), created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createInvoiceTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS invoices (id INT AUTO_INCREMENT PRIMARY KEY, invoice_number VARCHAR(50) UNIQUE NULL, invoice_status VARCHAR(30), invoice_type VARCHAR(20), e_invoice_status VARCHAR(30), customer_id INT, branch_id INT, issue_date DATE NULL, due_date DATE NULL, subtotal DECIMAL(10,2) DEFAULT 0, goods_total DECIMAL(10,2) DEFAULT 0, discount_total DECIMAL(10,2) DEFAULT 0, net_total DECIMAL(10,2) DEFAULT 0, vat_rate DECIMAL(5,2) DEFAULT 0, vat_amount DECIMAL(10,2) DEFAULT 0, tax_amount DECIMAL(10,2) DEFAULT 0, other_fee DECIMAL(10,2) DEFAULT 0, shipping_fee DECIMAL(10,2) DEFAULT 0, customer_payable DECIMAL(10,2) DEFAULT 0, customer_paid DECIMAL(10,2) DEFAULT 0, cod_amount DECIMAL(10,2) DEFAULT 0, rounding_adjustment DECIMAL(10,2) DEFAULT 0, payment_status VARCHAR(20), total_paid DECIMAL(10,2) DEFAULT 0, currency_code VARCHAR(10) DEFAULT 'VND', exchange_rate DECIMAL(15,6) DEFAULT 1, last_payment_date DATETIME NULL, total DECIMAL(10,2) DEFAULT 0, pdf_path VARCHAR(255) NULL, notes TEXT NULL, meta JSON NULL, created_by INT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createInvoiceOrderTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS invoice_orders (id INT AUTO_INCREMENT PRIMARY KEY, invoice_id INT, order_id INT, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createInventoryStockTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS inventory_stock (id INT AUTO_INCREMENT PRIMARY KEY, branch_id INT, warehouse_id INT, product_id INT, variant_id INT, quantity_on_hand DECIMAL(10,2) DEFAULT 0, quantity_reserved DECIMAL(10,2) DEFAULT 0, minimum_stock DECIMAL(10,2) DEFAULT 0, last_movement_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS inventory_valuation (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, variant_id INT NULL, avg_cost DECIMAL(12,2) DEFAULT 0, total_cost DECIMAL(14,2) DEFAULT 0, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createInventoryMovementTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS inventory_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT,
            product_id INT,
            variant_id INT NULL,
            batch_id BIGINT UNSIGNED NULL,
            serial_number VARCHAR(160) NULL,
            type VARCHAR(50),
            quantity DECIMAL(10,2),
            reference_type VARCHAR(50) NULL,
            reference_id INT NULL,
            notes TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createInventoryAlertTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS inventory_alerts (id INT AUTO_INCREMENT PRIMARY KEY, alert_type VARCHAR(50), product_id INT, variant_id INT, warehouse_id INT, current_quantity DECIMAL(10,2), threshold_quantity DECIMAL(10,2), status VARCHAR(50), resolved_by INT NULL, resolved_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createOrderStatusLogTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS order_status_logs (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, from_status VARCHAR(50) NULL, to_status VARCHAR(50) NOT NULL, changed_by INT NULL, notes TEXT NULL, changed_at DATETIME NULL, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createWebhookTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS webhook_subscriptions (id INT AUTO_INCREMENT PRIMARY KEY, event VARCHAR(100), target_url VARCHAR(500), secret VARCHAR(255) NULL, is_active TINYINT(1) DEFAULT 1, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS webhook_events (id INT AUTO_INCREMENT PRIMARY KEY, event VARCHAR(100), payload JSON, status VARCHAR(50), attempts INT DEFAULT 0, last_error TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
