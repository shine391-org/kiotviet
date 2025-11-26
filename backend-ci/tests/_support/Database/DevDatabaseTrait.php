<?php

namespace Tests\Support\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

trait DevDatabaseTrait
{
    protected $db;
    protected static bool $schemaMigrated = false;
    
    protected function setUpDatabase(): void
    {
        $this->freshMigrateSchema();
        $this->db = \Config\Database::connect('tests');
        $this->db->transBegin();
    }
    
    protected function tearDownDatabase(): void
    {
        if ($this->db && $this->db->connID) {
            $this->db->transRollback();
            $this->db->close();
        }
    }
    
    private function freshMigrateSchema(): void
    {
        $this->db = \Config\Database::connect('tests');

        $builder = new class($this->db) {
            use CompleteSchemaTrait;
            public function __construct($db) { $this->db = $db; }
            public function build(): void { $this->resetCompleteSchema(); }
        };
        $builder->build();
        if (defined('TEST_DEBUG') && TEST_DEBUG) {
            log_message('info', 'Database migrated for testing: ' . date('Y-m-d H:i:s'));
        }
    }
    
    private function createBasicTestSchema($db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS `branches` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NULL,
            `code` VARCHAR(50) NULL,
            `status` VARCHAR(20) DEFAULT 'active',
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_type` VARCHAR(50) NULL,
            `code` VARCHAR(100) NOT NULL,
            `barcode` VARCHAR(100) NULL,
            `name` VARCHAR(255) NOT NULL,
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `selling_price` DECIMAL(10,2) DEFAULT 0,
            `stock_quantity` INT DEFAULT 0,
            `alert_stock` INT DEFAULT 0,
            `purchase_price` DECIMAL(10,2) DEFAULT 0,
            `wholesale_price` DECIMAL(10,2) DEFAULT 0,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `product_variants_v2` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `variant_name` VARCHAR(255) NULL,
            `variant_signature` VARCHAR(255) NULL,
            `sku` VARCHAR(100) NULL,
            `barcode` VARCHAR(100) NULL,
            `price` DECIMAL(10,2) DEFAULT 0,
            `cost_price` DECIMAL(10,2) DEFAULT 0,
            `stock_quantity` DECIMAL(10,2) DEFAULT 0,
            `min_stock` DECIMAL(10,2) DEFAULT 0,
            `max_stock` DECIMAL(10,2) DEFAULT 0,
            `image_url` VARCHAR(255) NULL,
            `attributes` TEXT NULL,
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `product_category_links` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `category_id` INT NOT NULL,
            `created_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `product_images` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NULL,
            `variant_id` INT NULL,
            `image_path` VARCHAR(255) NULL,
            `image_url` VARCHAR(255) NULL,
            `is_primary` TINYINT DEFAULT 0,
            `sort_order` INT DEFAULT 0,
            `file_name` VARCHAR(255) NULL,
            `deleted_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `inventory_stock` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `branch_id` INT,
            `warehouse_id` INT,
            `product_id` INT,
            `variant_id` INT,
            `quantity_on_hand` DECIMAL(10,2) DEFAULT 0,
            `quantity_reserved` DECIMAL(10,2) DEFAULT 0,
            `minimum_stock` DECIMAL(10,2) DEFAULT 0,
            `last_movement_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `price_lists` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `type` VARCHAR(50) DEFAULT 'custom',
            `priority` INT DEFAULT 0,
            `is_active` TINYINT DEFAULT 1,
            `start_date` DATE NULL,
            `end_date` DATE NULL,
            `apply_to_groups` JSON NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `price_list_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `price_list_id` INT NOT NULL,
            `product_id` INT NULL,
            `variant_id` INT NULL,
            `price` DECIMAL(10,2) NOT NULL,
            `discount_percent` DECIMAL(5,2) DEFAULT 0,
            `discount_amount` DECIMAL(10,2) DEFAULT 0,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL,
            `customer_id` INT NULL,
            `customer_group_id` INT NULL,
            `branch_id` INT NULL,
            `order_date` DATE NULL,
            `order_type` VARCHAR(50) DEFAULT 'online',
            `payment_method` VARCHAR(50) NULL,
            `status` VARCHAR(50) DEFAULT 'draft',
            `subtotal` DECIMAL(10,2) DEFAULT 0,
            `discount_total` DECIMAL(10,2) DEFAULT 0,
            `shipping_fee` DECIMAL(10,2) DEFAULT 0,
            `total` DECIMAL(10,2) DEFAULT 0,
            `paid_amount` DECIMAL(10,2) DEFAULT 0,
            `debt_amount` DECIMAL(10,2) DEFAULT 0,
            `is_paid` TINYINT DEFAULT 0,
            `applied_price_list_id` INT NULL,
            `shipping_name` VARCHAR(255) NULL,
            `shipping_phone` VARCHAR(50) NULL,
            `shipping_address` TEXT NULL,
            `shipping_ward` VARCHAR(100) NULL,
            `shipping_district` VARCHAR(100) NULL,
            `shipping_city` VARCHAR(100) NULL,
            `notes` TEXT NULL,
            `created_by` INT NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NULL,
            `variant_id` INT NULL,
            `quantity` DECIMAL(10,2) DEFAULT 0,
            `base_price` DECIMAL(10,2) DEFAULT 0,
            `final_price` DECIMAL(10,2) DEFAULT 0,
            `price_list_id` INT NULL,
            `price_list_name` VARCHAR(255) NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $db->query("CREATE TABLE IF NOT EXISTS `customers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `organization_id` INT UNSIGNED NOT NULL DEFAULT 1,
            `customer_group_id` INT NULL,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NULL,
            `phone` VARCHAR(50) NULL,
            `phone2` VARCHAR(50) NULL,
            `gender` ENUM('MALE','FEMALE','OTHER') NULL,
            `facebook` VARCHAR(255) NULL,
            `customer_type` ENUM('INDIVIDUAL','COMPANY','HOUSEHOLD') NOT NULL DEFAULT 'INDIVIDUAL',
            `company_name` VARCHAR(255) NULL,
            `tax_code` VARCHAR(20) NULL,
            `buyer_name` VARCHAR(255) NULL,
            `invoice_company_name` VARCHAR(255) NULL,
            `invoice_address` VARCHAR(500) NULL,
            `invoice_province` VARCHAR(120) NULL,
            `invoice_district` VARCHAR(120) NULL,
            `invoice_ward` VARCHAR(120) NULL,
            `invoice_email` VARCHAR(255) NULL,
            `invoice_phone` VARCHAR(50) NULL,
            `cccd_cmnd` VARCHAR(50) NULL,
            `id_number` VARCHAR(50) NULL,
            `bank_account` VARCHAR(50) NULL,
            `bank_name` VARCHAR(255) NULL,
            `notes` TEXT NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL,
            UNIQUE KEY `unique_tax_code_per_org` (`organization_id`,`tax_code`),
            KEY `idx_customers_tax_code` (`tax_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `order_sequences` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `branch_id` INT NOT NULL,
            `sequence_number` INT DEFAULT 1,
            `prefix` VARCHAR(20) DEFAULT 'ORD',
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    private function createAttributesSchema($db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS `attributes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `code` VARCHAR(100) NOT NULL,
            `type` ENUM('text', 'number', 'select', 'multiselect', 'boolean', 'date') DEFAULT 'text',
            `is_required` TINYINT DEFAULT 0,
            `is_filterable` TINYINT DEFAULT 0,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `attribute_options` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `attribute_id` INT NOT NULL,
            `value` VARCHAR(255) NOT NULL,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `product_attribute_values` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `variant_id` INT NULL,
            `attribute_id` INT NOT NULL,
            `attribute_option_id` INT NULL,
            `value_text` TEXT NULL,
            `value` TEXT NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `webhook_subscriptions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `url` VARCHAR(500) NOT NULL,
            `event` VARCHAR(100) NOT NULL,
            `secret` VARCHAR(255) NULL,
            `is_active` TINYINT DEFAULT 1,
            `retry_count` INT DEFAULT 0,
            `last_triggered_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE IF NOT EXISTS `webhook_events` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `subscription_id` INT NOT NULL,
            `event_data` JSON NULL,
            `status` ENUM('pending', 'success', 'failed') DEFAULT 'pending',
            `response_code` INT NULL,
            `response_body` TEXT NULL,
            `attempts` INT DEFAULT 0,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    protected function forceFreshMigrate(): void
    {
        self::$schemaMigrated = false;
        $this->freshMigrateSchema();
        self::$schemaMigrated = true;
        
        $this->db = Database::connect('tests');
        $this->db->transBegin();
    }
    
    protected function isTestingEnvironment(): bool
    {
        return ENVIRONMENT === 'testing';
    }
    
    protected function getTestDb(): \CodeIgniter\Database\BaseConnection
    {
        if (!$this->db) {
            $this->db = Database::connect('tests');
        }
        return $this->db;
    }
}
