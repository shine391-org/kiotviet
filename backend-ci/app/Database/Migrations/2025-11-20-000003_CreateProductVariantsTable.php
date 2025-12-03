<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductVariantsTable extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS product_variants_v2 (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
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
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->forge->dropTable('product_variants_v2', true);
    }
}