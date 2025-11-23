<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Create price list tables. @agent-migration: Price lists schema @agent-pattern: Schema + index */
class CreatePriceListTables extends Migration
{
    public function up()
    {
        $isSqlite = strtolower($this->db->DBDriver) === 'sqlite3';

        $typeField = $isSqlite
            ? ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'custom']
            : ['type' => 'ENUM', 'constraint' => ['base', 'wholesale', 'retail', 'vip', 'custom'], 'default' => 'custom'];

        $applyToGroupsField = $isSqlite
            ? ['type' => 'TEXT', 'null' => true]
            : ['type' => 'JSON', 'null' => true];

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'type' => $typeField,
            'description' => ['type' => 'TEXT', 'null' => true],
            'apply_to_groups' => $applyToGroupsField,
            'start_date' => ['type' => 'DATE', 'null' => true],
            'end_date' => ['type' => 'DATE', 'null' => true],
            'priority' => ['type' => 'INT', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'deleted_at'], false, false, 'idx_active');
        $this->forge->addKey(['start_date', 'end_date'], false, false, 'idx_dates');
        $this->forge->createTable('price_lists', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'price_list_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'price' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'discount_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id', false, false, 'idx_product');
        $this->forge->addUniqueKey(['price_list_id', 'product_id', 'variant_id'], 'unique_price_item');
        $this->forge->addForeignKey('price_list_id', 'price_lists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('price_list_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('price_list_items', true);
        $this->forge->dropTable('price_lists', true);
    }
}
