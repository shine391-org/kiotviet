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
        $this->createApprovalTables();
        $this->createStockLedgerTables();
        $this->createReorderPlanningTables();
        $this->createAdvancedPricingTables();
        $this->createPriceListTables();
        $this->createPriceListItemTables();
        $this->createPaymentMethodTables();
        $this->createTaxTemplateTables();
        $this->createLoyaltyTables();
        $this->createCouponTables();
        $this->createPurchaseOrderTables();
        $this->createOrderTables();
        $this->createOrderItemTables();
        $this->createOrderPaymentTables();
        $this->createPaymentEntryTables();
        $this->createBankReconciliationTables();
        $this->createAccountingTables();
        $this->createOrderSequenceTables();
        $this->createPOSTables();
        $this->createPOSOfflineTables();
        $this->createLeadTables();
        $this->createOpportunityTables();
        $this->createQuotationTables();
        $this->createCampaignTables();
        $this->createSupportTables();
        $this->createContractTables();
        $this->createSalesInvoiceTables();
        $this->createPurchaseInvoiceTables();
        $this->createReturnTables();
        $this->createReturnItemTables();
        $this->createInvoiceTables();
        $this->createInvoiceOrderTables();
        $this->createInventoryStockTables();
        $this->createInventoryMovementTables();
        $this->createInventoryAlertTables();
        $this->createOrderStatusLogTables();
        $this->createWebhookTables();
        $this->createQualityTables();
        $this->createOrderTemplateTables();
        $this->createEcommerceTables();
        $this->createManufacturingTables();
        $this->createSubscriptionTables();
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
            'approval_actions','approvals','order_approval_rules',
            'stock_reconciliation_items','stock_reconciliations','stock_bins','stock_ledgers','reorder_levels','purchase_suggestions',
            'pricing_rules','customer_price_lists','project_price_lists','price_history',
            'price_lists','price_list_items',
            'payment_methods','purchase_orders',
            'chart_of_accounts','gl_entries',
            'bank_reconciliation_logs','bank_reconciliations','bank_statements','payment_entry_allocations',
            'purchase_invoice_taxes','purchase_invoice_items','purchase_invoices',
            'sales_invoice_taxes','sales_invoice_items','sales_invoices','payment_schedules',
            'orders','order_items','order_sequences','order_payments','order_status_logs',
            'returns','return_items',
            'invoices','invoice_orders',
            'inventory_stock','inventory_movements','inventory_alerts',
            'webhook_subscriptions','webhook_events',
            'quality_inspection_items','quality_inspections','quality_parameters',
            'order_template_items','order_templates','order_subscriptions',
            'ecommerce_webhook_logs',
            'subscription_cycles','subscriptions',
            'bom_items','bill_of_materials','work_orders',
            'cash_transactions',
            'pos_profiles','pos_payment_methods','pos_shifts','pos_shift_payments','pos_shift_logs','pos_offline_queue',
            'loyalty_programs','loyalty_wallets','loyalty_transactions','coupons','coupon_usages',
            'tax_templates','tax_charges','payment_entries',
            'leads','opportunities','opportunity_items','quotations','quotation_items',
            'campaigns','campaign_members','email_campaigns','email_campaign_logs',
            'ticket_events','ticket_communications','support_tickets',
            'contract_templates','contracts','contract_terms','appointments'
        ];
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createBaseTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NULL UNIQUE,
            email VARCHAR(255) NULL UNIQUE,
            password VARCHAR(255) NULL,
            full_name VARCHAR(255) NULL,
            phone VARCHAR(20) NULL,
            avatar VARCHAR(255) NULL,
            branch_id BIGINT UNSIGNED NULL,
            status ENUM('active','inactive','suspended') DEFAULT 'active',
            last_login_at DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            remember_token VARCHAR(100) NULL,
            two_factor_secret VARCHAR(255) NULL,
            two_factor_enabled TINYINT(1) DEFAULT 0,
            password_changed_at DATETIME NULL,
            failed_login_attempts INT DEFAULT 0,
            account_locked_until DATETIME NULL,
            timezone VARCHAR(50) DEFAULT 'Asia/Ho_Chi_Minh',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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

    private function createApprovalTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS order_approval_rules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            condition_type VARCHAR(50) DEFAULT 'amount',
            threshold_amount DECIMAL(14,2) DEFAULT 0,
            customer_id BIGINT UNSIGNED NULL,
            custom_condition TEXT NULL,
            approver_ids TEXT NOT NULL,
            priority INT DEFAULT 100,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_rule_condition (condition_type, customer_id),
            KEY idx_rule_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS approvals (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) DEFAULT 'order',
            entity_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            status VARCHAR(20) DEFAULT 'pending',
            approver_queue TEXT NOT NULL,
            current_index INT DEFAULT 0,
            current_approver_id BIGINT UNSIGNED NULL,
            requested_by BIGINT UNSIGNED NULL,
            requested_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_approval_entity (entity_type, entity_id),
            KEY idx_approval_order (order_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS approval_actions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            approval_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(20) NOT NULL,
            actor_id BIGINT UNSIGNED NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            KEY idx_actions_approval (approval_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createStockLedgerTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS stock_ledgers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            branch_id BIGINT UNSIGNED NULL,
            warehouse_id BIGINT UNSIGNED NULL,
            batch_id BIGINT UNSIGNED NULL,
            serial_number VARCHAR(160) NULL,
            movement_date DATETIME NOT NULL,
            reference_type VARCHAR(80) NOT NULL,
            reference_id BIGINT UNSIGNED NOT NULL,
            reference_seq INT UNSIGNED DEFAULT 1,
            qty_delta DECIMAL(12,3) DEFAULT 0,
            unit_cost DECIMAL(14,4) DEFAULT 0,
            total_cost DECIMAL(14,4) DEFAULT 0,
            created_at DATETIME NULL,
            UNIQUE KEY uq_ledger_ref_seq (reference_type, reference_id, reference_seq),
            KEY idx_ledger_product (product_id, branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS stock_bins (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            batch_id BIGINT UNSIGNED NULL,
            on_hand_qty DECIMAL(12,3) DEFAULT 0,
            reserved_qty DECIMAL(12,3) DEFAULT 0,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_stock_bin (product_id, variant_id, branch_id, batch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS stock_reconciliations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recon_number VARCHAR(60) NOT NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) DEFAULT 'draft',
            notes TEXT NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            approved_by BIGINT UNSIGNED NULL,
            approved_at DATETIME NULL,
            UNIQUE KEY uq_recon_number (recon_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS stock_reconciliation_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reconciliation_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            batch_id BIGINT UNSIGNED NULL,
            counted_qty DECIMAL(12,3) DEFAULT 0,
            current_qty DECIMAL(12,3) DEFAULT 0,
            variance_qty DECIMAL(12,3) DEFAULT 0,
            unit_cost DECIMAL(14,4) DEFAULT 0,
            remarks TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_recon_item (reconciliation_id),
            KEY idx_recon_product (product_id, batch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createReorderPlanningTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS reorder_levels (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            min_level DECIMAL(12,3) DEFAULT 0,
            max_level DECIMAL(12,3) DEFAULT 0,
            safety_stock DECIMAL(12,3) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL,
            UNIQUE KEY uq_reorder_level (product_id, variant_id, branch_id),
            KEY idx_reorder_branch (branch_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS purchase_suggestions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reorder_level_id BIGINT UNSIGNED NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            generated_for_date DATE NOT NULL,
            suggested_qty DECIMAL(12,3) DEFAULT 0,
            on_hand_qty DECIMAL(12,3) DEFAULT 0,
            reserved_qty DECIMAL(12,3) DEFAULT 0,
            available_qty DECIMAL(12,3) DEFAULT 0,
            min_level DECIMAL(12,3) DEFAULT 0,
            max_level DECIMAL(12,3) DEFAULT 0,
            safety_stock DECIMAL(12,3) DEFAULT 0,
            status VARCHAR(30) DEFAULT 'pending',
            reason VARCHAR(255) NULL,
            purchase_order_id BIGINT UNSIGNED NULL,
            acknowledged_by BIGINT UNSIGNED NULL,
            acknowledged_at DATETIME NULL,
            converted_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_purchase_suggestion_day (product_id, variant_id, branch_id, generated_for_date),
            KEY idx_purchase_suggestion_status (branch_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    private function createQualityTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS quality_parameters (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            uom VARCHAR(50) NULL,
            min_value DECIMAL(14,4) NULL,
            max_value DECIMAL(14,4) NULL,
            specification VARCHAR(255) NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_quality_parameter_name (name),
            KEY idx_quality_parameter_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS quality_inspections (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reference_type VARCHAR(80) NOT NULL,
            reference_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) DEFAULT 'draft',
            result VARCHAR(20) DEFAULT 'pending',
            inspected_by BIGINT UNSIGNED NULL,
            inspected_at DATETIME NULL,
            submitted_at DATETIME NULL,
            approved_by BIGINT UNSIGNED NULL,
            approved_at DATETIME NULL,
            rejected_by BIGINT UNSIGNED NULL,
            rejected_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_quality_reference (reference_type, reference_id),
            KEY idx_quality_status (status),
            KEY idx_quality_result (result)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS quality_inspection_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            inspection_id BIGINT UNSIGNED NOT NULL,
            parameter_id BIGINT UNSIGNED NOT NULL,
            parameter_name VARCHAR(150) NOT NULL,
            uom VARCHAR(50) NULL,
            value_numeric DECIMAL(14,4) NULL,
            value_text VARCHAR(255) NULL,
            pass_flag TINYINT(1) NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_quality_item_inspection (inspection_id),
            KEY idx_quality_item_parameter (parameter_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    private function createEcommerceTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS ecommerce_webhook_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source VARCHAR(50) NULL,
            event_type VARCHAR(100) NOT NULL,
            idempotency_key VARCHAR(150) NOT NULL,
            status VARCHAR(30) DEFAULT 'processed',
            payload_hash VARCHAR(64) NULL,
            processed_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_ecommerce_idempotency (idempotency_key),
            KEY idx_ecommerce_event_status (event_type, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    private function createOrderTemplateTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS order_templates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            frequency VARCHAR(50) NULL,
            is_active TINYINT(1) DEFAULT 1,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_order_template_name (name),
            KEY idx_order_template_customer (customer_id),
            KEY idx_order_template_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS order_template_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            template_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            quantity DECIMAL(12,3) DEFAULT 0,
            price DECIMAL(14,2) NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_template_item_template (template_id),
            KEY idx_template_item_product (product_id, variant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS order_subscriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            template_id BIGINT UNSIGNED NOT NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            order_type VARCHAR(20) DEFAULT 'shipping',
            next_run_at DATETIME NULL,
            last_run_at DATETIME NULL,
            frequency_interval INT DEFAULT 7,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_order_subscription_template (template_id),
            KEY idx_order_subscription_next (next_run_at),
            KEY idx_order_subscription_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    private function createManufacturingTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS bill_of_materials (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            version VARCHAR(50) NULL,
            quantity DECIMAL(12,3) DEFAULT 1,
            uom VARCHAR(50) NULL,
            cost DECIMAL(14,4) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_bom_product (product_id),
            KEY idx_bom_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS bom_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bom_id BIGINT UNSIGNED NOT NULL,
            component_product_id BIGINT UNSIGNED NOT NULL,
            quantity DECIMAL(12,3) DEFAULT 0,
            uom VARCHAR(50) NULL,
            scrap_percent DECIMAL(6,3) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_bom_item_bom (bom_id),
            KEY idx_bom_item_component (component_product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS work_orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            bom_id BIGINT UNSIGNED NOT NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            quantity DECIMAL(12,3) DEFAULT 0,
            status VARCHAR(30) DEFAULT 'draft',
            planned_start DATETIME NULL,
            planned_end DATETIME NULL,
            actual_start DATETIME NULL,
            actual_end DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_work_order_product (product_id),
            KEY idx_work_order_bom (bom_id),
            KEY idx_work_order_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    private function createSubscriptionTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS subscriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NULL,
            template_id BIGINT UNSIGNED NULL,
            plan_name VARCHAR(150) NOT NULL,
            interval_days INT DEFAULT 30,
            next_run_at DATETIME NULL,
            status VARCHAR(30) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_subscription_status (status),
            KEY idx_subscription_next (next_run_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS subscription_cycles (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subscription_id BIGINT UNSIGNED NOT NULL,
            run_date DATE NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            status VARCHAR(30) DEFAULT 'processed',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_subscription_cycle (subscription_id, run_date),
            KEY idx_cycle_status (status)
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
        $this->db->query("CREATE TABLE IF NOT EXISTS orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NULL,
            order_number VARCHAR(50) NULL,
            customer_id INT NULL,
            customer_group_id INT NULL,
            branch_id INT NULL,
            warehouse_id INT NULL,
            order_date DATE NULL,
            order_type VARCHAR(50) DEFAULT 'online',
            pos_profile_id BIGINT UNSIGNED NULL,
            pos_shift_id BIGINT UNSIGNED NULL,
            tax_template_id INT NULL,
            tax_total DECIMAL(14,2) DEFAULT 0,
            rounding_adjustment DECIMAL(14,2) DEFAULT 0,
            payment_method VARCHAR(50) NULL,
            status VARCHAR(50) DEFAULT 'draft',
            coupon_code VARCHAR(120) NULL,
            coupon_discount DECIMAL(14,2) DEFAULT 0,
            loyalty_points_redeemed INT DEFAULT 0,
            loyalty_discount DECIMAL(14,2) DEFAULT 0,
            loyalty_points_earned INT DEFAULT 0,
            subtotal DECIMAL(14,2) DEFAULT 0,
            discount_total DECIMAL(14,2) DEFAULT 0,
            shipping_fee DECIMAL(14,2) DEFAULT 0,
            total DECIMAL(14,2) DEFAULT 0,
            paid_amount DECIMAL(14,2) DEFAULT 0,
            debt_amount DECIMAL(14,2) DEFAULT 0,
            payment_status VARCHAR(20) NULL,
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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

    private function createPOSTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS pos_profiles (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            role_id BIGINT UNSIGNED NULL,
            price_list_id INT NULL,
            tax_template_id INT NULL,
            warehouse_id INT NULL,
            branch_id INT NULL,
            company VARCHAR(150) NULL,
            allow_offline TINYINT(1) DEFAULT 0,
            require_shift TINYINT(1) DEFAULT 1,
            credit_limit DECIMAL(14,2) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_pos_profile_user_branch (user_id, branch_id),
            KEY idx_pos_profile_role (role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS pos_payment_methods (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            profile_id BIGINT UNSIGNED NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            is_allowed TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_profile_method (profile_id, payment_method),
            KEY idx_pos_payment_profile (profile_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS pos_shifts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            profile_id BIGINT UNSIGNED NULL,
            branch_id INT NULL,
            opening_balance DECIMAL(14,2) DEFAULT 0,
            expected_total DECIMAL(14,2) DEFAULT 0,
            expected_cash DECIMAL(14,2) DEFAULT 0,
            expected_card DECIMAL(14,2) DEFAULT 0,
            actual_total DECIMAL(14,2) DEFAULT 0,
            actual_cash DECIMAL(14,2) DEFAULT 0,
            actual_card DECIMAL(14,2) DEFAULT 0,
            discrepancy DECIMAL(14,2) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'open',
            opened_at DATETIME NULL,
            closed_at DATETIME NULL,
            closing_note TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_pos_shift_user_status (user_id, status),
            KEY idx_pos_shift_profile (profile_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS pos_shift_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shift_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            payment_method VARCHAR(50) NOT NULL,
            amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            reference_type VARCHAR(50) NULL,
            reference_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_pos_shift_payment (shift_id),
            KEY idx_pos_shift_payment_method (shift_id, payment_method)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS pos_shift_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shift_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            message TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_pos_shift_log (shift_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createPOSOfflineTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS pos_offline_queue (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            temp_id VARCHAR(120) NOT NULL,
            device_id VARCHAR(120) NOT NULL,
            idempotency_key VARCHAR(200) NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            branch_id INT NULL,
            payload JSON NULL,
            status VARCHAR(20) DEFAULT 'pending',
            order_id BIGINT UNSIGNED NULL,
            error_message TEXT NULL,
            created_at DATETIME NULL,
            synced_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_pos_offline_key (device_id, temp_id),
            KEY idx_pos_offline_status (status),
            KEY idx_pos_offline_idem (idempotency_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createTaxTemplateTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS tax_templates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            rate_percent DECIMAL(8,3) DEFAULT 0,
            is_inclusive TINYINT(1) DEFAULT 0,
            rounding_rule VARCHAR(20) DEFAULT 'nearest',
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tax_charges (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            template_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            rate_percent DECIMAL(8,3) DEFAULT 0,
            charge_type VARCHAR(20) DEFAULT 'on_net_total',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_tax_charges_template (template_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createPaymentEntryTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS payment_entries (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NULL,
            payment_method VARCHAR(50) NULL,
            mode_of_payment VARCHAR(50) NULL,
            party_type VARCHAR(50) NULL,
            party_id BIGINT UNSIGNED NULL,
            reference_type VARCHAR(120) NULL,
            reference_id BIGINT UNSIGNED NULL,
            debit_account_id BIGINT UNSIGNED NULL,
            credit_account_id BIGINT UNSIGNED NULL,
            amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            currency VARCHAR(10) DEFAULT 'VND',
            exchange_rate DECIMAL(12,4) DEFAULT 1,
            reference VARCHAR(120) NULL,
            reference_no VARCHAR(120) NULL,
            reference_date DATE NULL,
            status VARCHAR(20) DEFAULT 'posted',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_payment_entry_order (order_id, payment_method, reference),
            KEY idx_payment_party (party_type, party_id),
            KEY idx_payment_ref (reference_type, reference_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createBankReconciliationTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS payment_entry_allocations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            payment_entry_id BIGINT UNSIGNED NOT NULL,
            reference_type VARCHAR(120) NULL,
            reference_id BIGINT UNSIGNED NULL,
            allocated_amount DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_payment_alloc_entry (payment_entry_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS bank_statements (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            account_number VARCHAR(50) NOT NULL,
            amount DECIMAL(14,2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'VND',
            reference_no VARCHAR(120) NULL,
            reference_date DATE NULL,
            description VARCHAR(255) NULL,
            status VARCHAR(20) DEFAULT 'imported',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_bank_statement_ref (reference_no, reference_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS bank_reconciliations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bank_statement_id BIGINT UNSIGNED NOT NULL,
            payment_entry_id BIGINT UNSIGNED NULL,
            status VARCHAR(20) DEFAULT 'pending',
            matched_amount DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_bank_reco_statement (bank_statement_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS bank_reconciliation_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bank_reconciliation_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            message TEXT NULL,
            created_at DATETIME NULL,
            KEY idx_bank_reco_log (bank_reconciliation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createAccountingTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS chart_of_accounts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(255) NOT NULL,
            account_type VARCHAR(50) NOT NULL,
            currency VARCHAR(10) NULL,
            parent_id BIGINT UNSIGNED NULL,
            is_group TINYINT(1) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_coa_code (code),
            KEY idx_coa_parent (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS gl_entries (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            posting_date DATE NOT NULL,
            account_id BIGINT UNSIGNED NOT NULL,
            debit DECIMAL(14,2) DEFAULT 0,
            credit DECIMAL(14,2) DEFAULT 0,
            party_type VARCHAR(60) NULL,
            party_id BIGINT UNSIGNED NULL,
            reference_type VARCHAR(100) NULL,
            reference_id BIGINT UNSIGNED NULL,
            remarks TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_gl_account (account_id),
            KEY idx_gl_posting_date (posting_date),
            KEY idx_gl_party (party_type, party_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createLeadTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS leads (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            lead_number VARCHAR(50) NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            source VARCHAR(100) NULL,
            status VARCHAR(50) DEFAULT 'new',
            company VARCHAR(255) NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createOpportunityTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS opportunities (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            lead_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            title VARCHAR(255) NOT NULL,
            stage VARCHAR(50) DEFAULT 'qualification',
            probability INT DEFAULT 10,
            expected_value DECIMAL(14,2) DEFAULT 0,
            closing_date DATE NULL,
            status VARCHAR(30) DEFAULT 'open',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS opportunity_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            opportunity_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            quantity DECIMAL(14,2) DEFAULT 1,
            price DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_opp_items_opp (opportunity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createQuotationTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS quotations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quote_number VARCHAR(50) NULL,
            opportunity_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            lead_id BIGINT UNSIGNED NULL,
            status VARCHAR(50) DEFAULT 'draft',
            validity_date DATE NULL,
            subtotal DECIMAL(14,2) DEFAULT 0,
            discount_total DECIMAL(14,2) DEFAULT 0,
            tax_total DECIMAL(14,2) DEFAULT 0,
            total DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS quotation_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quotation_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            quantity DECIMAL(14,2) DEFAULT 1,
            price DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_quote_items_quote (quotation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createCampaignTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS campaigns (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            status VARCHAR(50) DEFAULT 'draft',
            source VARCHAR(100) NULL,
            budget DECIMAL(14,2) DEFAULT 0,
            start_date DATE NULL,
            end_date DATE NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS campaign_members (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id BIGINT UNSIGNED NOT NULL,
            lead_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_campaign_member_campaign (campaign_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS email_campaigns (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id BIGINT UNSIGNED NULL,
            subject VARCHAR(255) NOT NULL,
            template TEXT NULL,
            schedule_at DATETIME NULL,
            status VARCHAR(50) DEFAULT 'draft',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS email_campaign_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email_campaign_id BIGINT UNSIGNED NOT NULL,
            member_id BIGINT UNSIGNED NULL,
            status VARCHAR(30) DEFAULT 'queued',
            message TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_email_campaign_log (email_campaign_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createSupportTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS support_tickets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject VARCHAR(255) NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            lead_id BIGINT UNSIGNED NULL,
            priority VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(30) DEFAULT 'open',
            assigned_to BIGINT UNSIGNED NULL,
            description TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_support_ticket_status (status),
            KEY idx_support_ticket_customer (customer_id),
            KEY idx_support_ticket_lead (lead_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS ticket_communications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(30) DEFAULT 'note',
            content TEXT NULL,
            attachments TEXT NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_ticket_comm_ticket (ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS ticket_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(30) NOT NULL,
            from_status VARCHAR(30) NULL,
            to_status VARCHAR(30) NULL,
            description TEXT NULL,
            created_at DATETIME NULL,
            KEY idx_ticket_event_ticket (ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createSalesInvoiceTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS sales_invoices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            posting_date DATE NOT NULL,
            due_date DATE NULL,
            status VARCHAR(20) DEFAULT 'draft',
            currency VARCHAR(10) DEFAULT 'VND',
            exchange_rate DECIMAL(12,4) DEFAULT 1,
            total DECIMAL(14,2) DEFAULT 0,
            taxes_total DECIMAL(14,2) DEFAULT 0,
            grand_total DECIMAL(14,2) DEFAULT 0,
            rounding_adjustment DECIMAL(12,2) DEFAULT 0,
            debit_account_id BIGINT UNSIGNED NULL,
            credit_account_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_sales_invoice_number (invoice_number),
            KEY idx_sales_invoice_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS sales_invoice_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            description VARCHAR(255) NULL,
            quantity DECIMAL(12,2) DEFAULT 0,
            rate DECIMAL(14,2) DEFAULT 0,
            amount DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_sales_invoice_item_invoice (invoice_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS sales_invoice_taxes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id BIGINT UNSIGNED NOT NULL,
            tax_name VARCHAR(150) NOT NULL,
            rate_percent DECIMAL(8,3) DEFAULT 0,
            amount DECIMAL(14,2) DEFAULT 0,
            template_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_sales_invoice_tax_invoice (invoice_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS payment_schedules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id BIGINT UNSIGNED NOT NULL,
            due_date DATE NOT NULL,
            amount DECIMAL(14,2) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_payment_schedule_invoice (invoice_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createPurchaseInvoiceTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS purchase_invoices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) NOT NULL,
            supplier_id BIGINT UNSIGNED NULL,
            posting_date DATE NOT NULL,
            due_date DATE NULL,
            status VARCHAR(20) DEFAULT 'draft',
            currency VARCHAR(10) DEFAULT 'VND',
            exchange_rate DECIMAL(12,4) DEFAULT 1,
            total DECIMAL(14,2) DEFAULT 0,
            taxes_total DECIMAL(14,2) DEFAULT 0,
            grand_total DECIMAL(14,2) DEFAULT 0,
            rounding_adjustment DECIMAL(12,2) DEFAULT 0,
            debit_account_id BIGINT UNSIGNED NULL,
            credit_account_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_purchase_invoice_number (invoice_number),
            KEY idx_purchase_invoice_supplier (supplier_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS purchase_invoice_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            description VARCHAR(255) NULL,
            quantity DECIMAL(12,2) DEFAULT 0,
            rate DECIMAL(14,2) DEFAULT 0,
            amount DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_purchase_invoice_item_invoice (invoice_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS purchase_invoice_taxes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id BIGINT UNSIGNED NOT NULL,
            tax_name VARCHAR(150) NOT NULL,
            rate_percent DECIMAL(8,3) DEFAULT 0,
            amount DECIMAL(14,2) DEFAULT 0,
            template_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_purchase_invoice_tax_invoice (invoice_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createContractTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS contract_templates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            terms TEXT NULL,
            status VARCHAR(30) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS contract_terms (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contract_id BIGINT UNSIGNED NOT NULL,
            description TEXT NULL,
            is_completed TINYINT(1) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_contract_terms (contract_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS contracts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NULL,
            template_id BIGINT UNSIGNED NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            value DECIMAL(14,2) DEFAULT 0,
            status VARCHAR(30) DEFAULT 'draft',
            auto_renew TINYINT(1) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS appointments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NULL,
            lead_id BIGINT UNSIGNED NULL,
            contract_id BIGINT UNSIGNED NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            status VARCHAR(30) DEFAULT 'scheduled',
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_appointment_time (start_time, end_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createLoyaltyTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS loyalty_programs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            customer_group_id INT NULL,
            earn_rate DECIMAL(12,4) DEFAULT 0,
            redeem_rate DECIMAL(12,4) DEFAULT 0,
            expiry_days INT DEFAULT 365,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS loyalty_wallets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NOT NULL,
            points_balance DECIMAL(14,2) DEFAULT 0,
            last_earned_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_loyalty_wallet_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS loyalty_transactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            wallet_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            points_delta DECIMAL(14,2) NOT NULL,
            reason VARCHAR(120) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_loyalty_tx_wallet (wallet_id),
            KEY idx_loyalty_tx_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createCouponTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS coupons (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100) NOT NULL UNIQUE,
            discount_type VARCHAR(20) DEFAULT 'percent',
            discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
            min_amount DECIMAL(14,2) DEFAULT 0,
            expiry_date DATE NULL,
            usage_limit INT DEFAULT 0,
            used_count INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS coupon_usages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            coupon_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            used_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_coupon_usage_coupon (coupon_id),
            KEY idx_coupon_usage_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createWebhookTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS webhook_subscriptions (id INT AUTO_INCREMENT PRIMARY KEY, event VARCHAR(100), target_url VARCHAR(500), secret VARCHAR(255) NULL, is_active TINYINT(1) DEFAULT 1, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS webhook_events (id INT AUTO_INCREMENT PRIMARY KEY, event VARCHAR(100), payload JSON, status VARCHAR(50), attempts INT DEFAULT 0, last_error TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function createAdvancedPricingTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS customer_price_lists (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NOT NULL,
            price_list_id BIGINT UNSIGNED NOT NULL,
            valid_from DATE NULL,
            valid_to DATE NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_cpl_customer (customer_id, price_list_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS project_price_lists (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id BIGINT UNSIGNED NOT NULL,
            price_list_id BIGINT UNSIGNED NOT NULL,
            valid_from DATE NULL,
            valid_to DATE NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_ppl_project (project_id, price_list_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS pricing_rules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            condition_type VARCHAR(50) DEFAULT 'amount',
            customer_id BIGINT UNSIGNED NULL,
            project_id BIGINT UNSIGNED NULL,
            product_id BIGINT UNSIGNED NULL,
            variant_id BIGINT UNSIGNED NULL,
            min_qty DECIMAL(12,3) DEFAULT 0,
            start_date DATE NULL,
            end_date DATE NULL,
            price DECIMAL(14,4) DEFAULT 0,
            discount_percent DECIMAL(6,3) DEFAULT 0,
            priority INT DEFAULT 100,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_pricing_rule (condition_type, customer_id, project_id),
            KEY idx_pricing_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS price_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED NULL,
            source_type VARCHAR(50) NOT NULL,
            source_id BIGINT UNSIGNED NULL,
            old_price DECIMAL(14,4) DEFAULT 0,
            new_price DECIMAL(14,4) DEFAULT 0,
            changed_by BIGINT UNSIGNED NULL,
            changed_at DATETIME NULL,
            KEY idx_price_history (product_id, variant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
