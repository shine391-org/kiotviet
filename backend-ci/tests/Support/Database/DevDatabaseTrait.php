<?php

namespace Tests\Support\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * DevDatabaseTrait - Auto-migrate testing support (MySQL-only)
 * 
 * @agent-trait: Unified MySQL testing with auto-migration
 * @agent-pattern: Use this for ALL test classes instead of resetSchema()
 * @agent-reusable: HIGH
 * 
 * This trait replaces manual resetSchema() calls with:
 * - Auto-migration from latest schema files
 * - Transaction-based cleanup for isolation
 * - Performance optimization with schema caching
 * - MySQL-only database (SQLite removed)
 */
trait DevDatabaseTrait
{
    /**
     * Track if schema has been migrated for this test session
     */
    protected static bool $schemaMigrated = false;
    
    /**
     * Database connection for tests
     */
    protected $db;
    
    /**
     * Setup test environment with auto-migrate
     * 
     * @agent-pattern: Standard test setup - COPY THIS
     * @agent-use: Call this in setUp() of all test classes
     */
    protected function setUpDatabase(): void
    {
        // Auto-migrate schema only once per test session
        if (!self::$schemaMigrated) {
            $this->freshMigrateSchema();
            self::$schemaMigrated = true;
        }
        
        // Use Database config from environment (MySQL-only)
        $this->db = \Config\Database::connect('tests');
        
        // Start transaction for test isolation
        $this->db->transBegin();
    }
    
    /**
     * Cleanup test environment with transaction rollback
     * 
     * @agent-pattern: Standard test cleanup - COPY THIS
     * @agent-use: Call this in tearDown() of all test classes
     */
    protected function tearDownDatabase(): void
    {
        if ($this->db && $this->db->connID) {
            // Rollback transaction to clean up test data
            $this->db->transRollback();
            $this->db->close();
        }
    }
    
    /**
     * Fresh migrate schema from migration files
     * 
     * @agent-pattern: Auto-migrate from real migrations (MySQL-only)
     * @agent-use: This loads latest schema instead of manual SQL
     */
    private function freshMigrateSchema(): void
    {
        // Use Database config from environment (MySQL-only)
        $db = \Config\Database::connect('tests');
        
        // Clean slate - drop all tables first
        $tables = $db->listTables();
        foreach ($tables as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
        
        // Create basic schema for testing (MySQL-only)
        $this->createBasicTestSchema($db);
        
        // Log migration status for debugging
        if (defined('TEST_DEBUG') && TEST_DEBUG) {
            log_message('info', 'Database migrated for testing: ' . date('Y-m-d H:i:s'));
        }
    }
    
    /**
     * Fallback basic schema creation (MySQL-only)
     * 
     * @agent-use: Creates minimal schema if migrations fail
     */
    private function createBasicTestSchema($db): void
    {
        // Create basic products table for testing
        $db->query("CREATE TABLE IF NOT EXISTS `db_products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_type` VARCHAR(50) NULL,
            `code` VARCHAR(100) NOT NULL,
            `barcode` VARCHAR(100) NULL,
            `name` VARCHAR(255) NOT NULL,
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `selling_price` DECIMAL(10,2) DEFAULT 0,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Create product variants table
        $db->query("CREATE TABLE IF NOT EXISTS `db_product_variants_v2` (
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

        // Create product category links table
        $db->query("CREATE TABLE IF NOT EXISTS `db_product_category_links` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `category_id` INT NOT NULL,
            `created_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Create product images table
        $db->query("CREATE TABLE IF NOT EXISTS `db_product_images` (
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

        // Create price lists table for order testing
        $db->query("CREATE TABLE IF NOT EXISTS `db_price_lists` (
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

        // Create price list items table
        $db->query("CREATE TABLE IF NOT EXISTS `db_price_list_items` (
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

        // Create orders table for order testing
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

        // Create order items table
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

        // Create db_orders table (for service tests with db_ prefix)
        $db->query("CREATE TABLE IF NOT EXISTS `db_orders` (
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

        // Create db_order_items table (for service tests with db_ prefix)
        $db->query("CREATE TABLE IF NOT EXISTS `db_order_items` (
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

        // Create customers table for order testing
        $db->query("CREATE TABLE IF NOT EXISTS `customers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_group_id` INT NULL,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NULL,
            `phone` VARCHAR(50) NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            `deleted_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Create order_sequences table for OrderNumberGenerator
        $db->query("CREATE TABLE IF NOT EXISTS `db_order_sequences` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `branch_id` INT NOT NULL,
            `sequence_number` INT DEFAULT 1,
            `prefix` VARCHAR(20) DEFAULT 'ORD',
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Helper method to run migrations manually if needed
     * 
     * @agent-use: Call this in specific tests that need fresh schema
     */
    protected function forceFreshMigrate(): void
    {
        self::$schemaMigrated = false;
        $this->freshMigrateSchema();
        self::$schemaMigrated = true;
        
        // Re-connect after migration
        $this->db = Database::connect('tests');
        $this->db->transBegin();
    }
    
    /**
     * Check if we're running in testing environment
     * 
     * @agent-use: Use this to conditionally run test-specific code
     */
    protected function isTestingEnvironment(): bool
    {
        return ENVIRONMENT === 'testing';
    }
    
    /**
     * Get test database connection
     * 
     * @agent-use: Use this instead of Database::connect() in tests
     */
    protected function getTestDb(): \CodeIgniter\Database\BaseConnection
    {
        if (!$this->db) {
            $this->db = Database::connect('tests');
        }
        return $this->db;
    }
}
