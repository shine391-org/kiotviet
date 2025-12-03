<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestoreLegacyProductTables extends Migration
{
    public function up()
    {
        // 1. Add missing columns to products table
        $fields = [
            'warehouse_location' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'brand'],
            'base_unit_code'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'unit'],
            'unit_conversion'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1.00, 'after' => 'base_unit_code'],
            'commission_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00, 'after' => 'selling_price'],
            'commission_amount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00, 'after' => 'commission_percent'],
            'expiry_days'        => ['type' => 'INT', 'null' => true, 'after' => 'alert_stock'],
            'customer_ordered'   => ['type' => 'INT', 'default' => 0, 'after' => 'expiry_days'],
            'expected_out_date'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'customer_ordered'],
            'min_stock_alert'    => ['type' => 'INT', 'default' => 0, 'after' => 'expected_out_date'],
            'max_stock_alert'    => ['type' => 'INT', 'default' => 0, 'after' => 'min_stock_alert'],
            'related_product_codes' => ['type' => 'TEXT', 'null' => true, 'after' => 'has_variants'],
            'image_count'        => ['type' => 'INT', 'default' => 0, 'after' => 'images'],
        ];
        
        // Check if columns exist before adding to avoid errors if run multiple times or partially
        foreach ($fields as $field => $attr) {
            if (!$this->db->fieldExists($field, 'products')) {
                $this->forge->addColumn('products', [$field => $attr]);
            }
        }

        // 2. Create product_images table
        $this->db->query("CREATE TABLE IF NOT EXISTS product_images (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED DEFAULT NULL,
            image_url VARCHAR(500) NOT NULL,
            image_path VARCHAR(500) DEFAULT NULL,
            is_primary TINYINT(1) DEFAULT 0 COMMENT '1=ảnh chính, 0=ảnh phụ',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            file_name VARCHAR(255) DEFAULT NULL,
            KEY idx_product (product_id),
            KEY idx_primary (product_id, is_primary),
            KEY idx_deleted (deleted_at),
            KEY idx_variant_id (variant_id)
            -- Foreign keys will be added separately or assumed managed by app to avoid circular deps during restore
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. Create product_prices table
        $this->db->query("CREATE TABLE IF NOT EXISTS product_prices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_id BIGINT UNSIGNED DEFAULT NULL,
            customer_group_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL = giá chung',
            price_type ENUM('retail','wholesale','special') DEFAULT 'retail',
            price DECIMAL(15,2) NOT NULL,
            min_quantity INT DEFAULT 1 COMMENT 'SL tối thiểu',
            valid_from DATE DEFAULT NULL,
            valid_to DATE DEFAULT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_by BIGINT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY product_id (product_id),
            KEY variant_id (variant_id),
            KEY customer_group_id (customer_group_id),
            KEY price_type (price_type),
            KEY status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 4. Create product_stock_by_branch table
        $this->db->query("CREATE TABLE IF NOT EXISTS product_stock_by_branch (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            stock_quantity INT DEFAULT 0 COMMENT 'Số lượng tồn',
            alert_stock INT DEFAULT 10 COMMENT 'Ngưỡng cảnh báo',
            reserved_stock INT DEFAULT 0 COMMENT 'Hàng đang giữ',
            available_stock INT DEFAULT 0 COMMENT 'Có thể bán',
            expiry_days INT DEFAULT NULL COMMENT 'Dự kiến hết hàng (ngày)',
            last_stock_date DATETIME DEFAULT NULL COMMENT 'Lần cập nhật tồn cuối',
            status ENUM('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_product_branch (product_id, branch_id),
            KEY idx_branch (branch_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 5. Modify product_variants_v2 precision
        // Check if table exists first
        if ($this->db->tableExists('product_variants_v2')) {
            $this->db->query("ALTER TABLE product_variants_v2 MODIFY price DECIMAL(15,2) DEFAULT 0.00");
            $this->db->query("ALTER TABLE product_variants_v2 MODIFY cost_price DECIMAL(15,2) DEFAULT 0.00");
        }

        // 6. Create model_has_permissions (RBAC)
        $this->db->query("CREATE TABLE IF NOT EXISTS model_has_permissions (
            permission_id BIGINT UNSIGNED NOT NULL,
            model_type VARCHAR(255) NOT NULL,
            model_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (permission_id, model_id, model_type),
            KEY model_has_permissions_model_id_model_type_index (model_id, model_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 7. Create activity_logs
        $this->db->query("CREATE TABLE IF NOT EXISTS activity_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            module VARCHAR(50) NOT NULL,
            model_type VARCHAR(100) DEFAULT NULL,
            model_id BIGINT UNSIGNED DEFAULT NULL,
            old_values TEXT DEFAULT NULL,
            new_values TEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_date (user_id, created_at),
            KEY idx_module_model (module, model_type, model_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    public function down()
    {
        $this->forge->dropTable('activity_logs', true);
        $this->forge->dropTable('model_has_permissions', true);
        $this->forge->dropTable('product_stock_by_branch', true);
        $this->forge->dropTable('product_prices', true);
        $this->forge->dropTable('product_images', true);
        
        // Remove added columns from products
        $fields = [
            'warehouse_location', 'base_unit_code', 'unit_conversion', 
            'commission_percent', 'commission_amount', 'expiry_days', 
            'customer_ordered', 'expected_out_date', 'min_stock_alert', 
            'max_stock_alert', 'related_product_codes', 'image_count'
        ];
        $this->forge->dropColumn('products', $fields);
    }
}
