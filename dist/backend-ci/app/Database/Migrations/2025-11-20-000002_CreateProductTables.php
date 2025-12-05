<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductTables extends Migration
{
    public function up()
    {
        // products
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT','unsigned' => true,'auto_increment' => true],
            'product_type'       => ['type' => 'ENUM','constraint' => ['goods','service','combo'],'default' => 'goods'],
            'code'               => ['type' => 'VARCHAR','constraint' => 50],
            'barcode'            => ['type' => 'VARCHAR','constraint' => 100,'null' => true],
            'name'               => ['type' => 'VARCHAR','constraint' => 500],
            'slug'               => ['type' => 'VARCHAR','constraint' => 500],
            'brand'              => ['type' => 'VARCHAR','constraint' => 100,'null' => true],
            'unit'               => ['type' => 'VARCHAR','constraint' => 50,'default' => 'Cái'],
            'purchase_price'     => ['type' => 'DECIMAL','constraint' => '15,2','default' => 0],
            'selling_price'      => ['type' => 'DECIMAL','constraint' => '15,2','default' => 0],
            'wholesale_price'    => ['type' => 'DECIMAL','constraint' => '15,2','null' => true],
            'stock_quantity'     => ['type' => 'INT','default' => 0],
            'alert_stock'        => ['type' => 'INT','default' => 0],
            'has_variants'       => ['type' => 'TINYINT','constraint' => 1,'default' => 0],
            'image'              => ['type' => 'VARCHAR','constraint' => 500,'null' => true],
            'images'             => ['type' => 'TEXT','null' => true],
            'weight'             => ['type' => 'DECIMAL','constraint' => '10,2','default' => 0],
            'dimensions'         => ['type' => 'VARCHAR','constraint' => 100,'null' => true],
            'description'        => ['type' => 'TEXT','null' => true],
            'content'            => ['type' => 'LONGTEXT','null' => true],
            'is_active'          => ['type' => 'TINYINT','constraint' => 1,'default' => 1],
            'is_available_online'=> ['type' => 'TINYINT','constraint' => 1,'default' => 1],
            'is_featured'        => ['type' => 'TINYINT','constraint' => 1,'default' => 0],
            'status'             => ['type' => 'ENUM','constraint' => ['active','inactive','discontinued'],'default' => 'active'],
            'meta_title'         => ['type' => 'VARCHAR','constraint' => 255,'null' => true],
            'meta_description'   => ['type' => 'TEXT','null' => true],
            'meta_keywords'      => ['type' => 'TEXT','null' => true],
            'created_at'         => ['type' => 'DATETIME','null' => true],
            'updated_at'         => ['type' => 'DATETIME','null' => true],
            'deleted_at'         => ['type' => 'DATETIME','null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('products', true);

        // product_categories
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT','unsigned' => true,'auto_increment' => true],
            'parent_id'   => ['type' => 'BIGINT','unsigned' => true,'null' => true],
            'level'       => ['type' => 'TINYINT','default' => 1],
            'code'        => ['type' => 'VARCHAR','constraint' => 50],
            'name'        => ['type' => 'VARCHAR','constraint' => 255],
            'slug'        => ['type' => 'VARCHAR','constraint' => 255],
            'description' => ['type' => 'TEXT','null' => true],
            'image'       => ['type' => 'VARCHAR','constraint' => 255,'null' => true],
            'sort_order'  => ['type' => 'INT','default' => 0],
            'status'      => ['type' => 'ENUM','constraint' => ['active','inactive'],'default' => 'active'],
            'created_at'  => ['type' => 'DATETIME','null' => true],
            'updated_at'  => ['type' => 'DATETIME','null' => true],
            'deleted_at'  => ['type' => 'DATETIME','null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('product_categories', true);

        // product_category_links
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT','unsigned' => true,'auto_increment' => true],
            'product_id'  => ['type' => 'BIGINT','unsigned' => true],
            'category_id' => ['type' => 'BIGINT','unsigned' => true],
            'created_at'  => ['type' => 'DATETIME','null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addKey('category_id');
        $this->forge->createTable('product_category_links', true);
    }

    public function down()
    {
        $this->forge->dropTable('product_category_links', true);
        $this->forge->dropTable('product_categories', true);
        $this->forge->dropTable('products', true);
    }
}
