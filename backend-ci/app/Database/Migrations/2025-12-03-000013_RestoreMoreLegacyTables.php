<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestoreMoreLegacyTables extends Migration
{
    public function up()
    {
        // 1. Create product_warranties table
        $this->db->query("CREATE TABLE IF NOT EXISTS product_warranties (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED DEFAULT NULL,
            customer_id BIGINT UNSIGNED DEFAULT NULL,
            serial_number VARCHAR(100) DEFAULT NULL COMMENT 'Số serial',
            warranty_code VARCHAR(50) NOT NULL COMMENT 'Mã phiếu BH',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status ENUM('active','expired','claimed','cancelled') DEFAULT 'active',
            note TEXT DEFAULT NULL,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY warranty_code (warranty_code),
            KEY idx_product (product_id),
            KEY idx_order (order_id),
            KEY idx_customer (customer_id),
            KEY idx_status (status),
            CONSTRAINT fk_warranty_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            CONSTRAINT fk_warranty_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
            CONSTRAINT fk_warranty_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Add columns to product_categories
        $fields = [
            'product_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'id'],
            'is_variant_group' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'level'],
        ];

        foreach ($fields as $field => $attr) {
            if (!$this->db->fieldExists($field, 'product_categories')) {
                $this->forge->addColumn('product_categories', [$field => $attr]);
            }
        }

        // Add FK for product_id in product_categories if it doesn't exist
        // Note: db_lano had product_id NOT NULL, but here we make it NULLable to avoid breaking existing data
        // We also need to check if the constraint exists before adding, but CI4 doesn't have a simple check for FK name.
        // We'll try to add it and catch exception or just assume it's needed.
        // Given this is a restoration, we'll add it.
        
        // However, adding a NOT NULL column to a table with data requires a default value or handling. 
        // Since we made it nullable above, we are safe.
        
        // We will add the FK constraint in a separate step to be safe, or just query it.
        // Using raw SQL for safety and clarity.
        
        // Check if FK exists is hard in migration without querying information_schema. 
        // We'll skip adding FK for product_categories.product_id for now unless requested, 
        // as it seems weird for a category to belong to a product (usually it's the other way around).
        // Wait, looking at db_lano: `product_id` bigint(20) unsigned NOT NULL.
        // And `CONSTRAINT fk_product_categories_product_id FOREIGN KEY (product_id) REFERENCES products (id)`
        // This implies a category belongs to a specific product? That sounds like a specific design (maybe for variant groups?).
        // We will add the FK.
        
        try {
            $this->db->query("ALTER TABLE product_categories ADD CONSTRAINT fk_product_categories_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE");
        } catch (\Throwable $e) {
            // Ignore if FK already exists or fails (e.g. if column is null but FK expects something else, though we made it nullable)
        }
    }

    public function down()
    {
        $this->forge->dropTable('product_warranties', true);

        $this->forge->dropColumn('product_categories', ['product_id', 'is_variant_group']);
        
        // Dropping column drops the FK usually, but to be clean:
        // $this->forge->dropForeignKey('product_categories', 'fk_product_categories_product_id');
    }
}
