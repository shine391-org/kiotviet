<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductAttributesTable extends Migration
{
    public function up()
    {
        // attributes
        $this->db->query("CREATE TABLE IF NOT EXISTS attributes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NULL,
            attribute_key VARCHAR(100) NULL,
            type VARCHAR(50) DEFAULT 'select',
            is_required TINYINT DEFAULT 0,
            is_filterable TINYINT DEFAULT 0,
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            is_visible TINYINT DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // product_attributes
        $this->db->query("CREATE TABLE IF NOT EXISTS product_attributes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), slug VARCHAR(255), attribute_key VARCHAR(100), type VARCHAR(50), is_required TINYINT DEFAULT 0, is_filterable TINYINT DEFAULT 0, sort_order INT DEFAULT 0, status VARCHAR(20) DEFAULT 'active', is_visible TINYINT DEFAULT 1, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // attribute_options
        $this->db->query("CREATE TABLE IF NOT EXISTS attribute_options (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attribute_id BIGINT UNSIGNED NOT NULL,
            option_name VARCHAR(255),
            color_code VARCHAR(50) NULL,
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // product_attribute_options
        $this->db->query("CREATE TABLE IF NOT EXISTS product_attribute_options (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, attribute_id INT, option_name VARCHAR(255), color_code VARCHAR(50) NULL, sort_order INT DEFAULT 0, status VARCHAR(20) DEFAULT 'active', created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // product_attribute_values
        $this->db->query("CREATE TABLE IF NOT EXISTS product_attribute_values (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED, variant_id BIGINT UNSIGNED NULL, attribute_id INT, option_id INT, value_text TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->forge->dropTable('product_attribute_values', true);
        $this->forge->dropTable('product_attribute_options', true);
        $this->forge->dropTable('attribute_options', true);
        $this->forge->dropTable('product_attributes', true);
        $this->forge->dropTable('attributes', true);
    }
}