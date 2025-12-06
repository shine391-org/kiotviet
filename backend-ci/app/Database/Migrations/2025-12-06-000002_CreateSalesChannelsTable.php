<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSalesChannelsTable extends Migration
{
    public function up()
    {
        // Sales channels config
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'color' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'settings' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_sales_channel_code');
        $this->forge->createTable('sales_channels', true);

        // Shipping zones for fee calculation
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'province_ids' => ['type' => 'JSON', 'null' => true, 'comment' => 'Array of province IDs'],
            'district_ids' => ['type' => 'JSON', 'null' => true, 'comment' => 'Array of district IDs'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('shipping_zones', true);

        // Shipping rates
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'zone_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'delivery_partner_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'min_weight' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'max_weight' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 999999],
            'min_value' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'max_value' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 999999999],
            'base_fee' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'per_kg_fee' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'free_shipping_threshold' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('zone_id', false, false, 'idx_shipping_rate_zone');
        $this->forge->addKey('delivery_partner_id', false, false, 'idx_shipping_rate_partner');
        $this->forge->addForeignKey('zone_id', 'shipping_zones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('shipping_rates', true);
    }

    public function down()
    {
        $this->forge->dropTable('shipping_rates', true);
        $this->forge->dropTable('shipping_zones', true);
        $this->forge->dropTable('sales_channels', true);
    }
}
