<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Advanced pricing tables (customer/project rules/history).
 *
 * @agent-migration: Advanced pricing
 * @agent-pattern: Schema + constraints
 */
class CreateAdvancedPricingTables extends Migration
{
    public function up()
    {
        $this->createCustomerPriceLists();
        $this->createProjectPriceLists();
        $this->createPricingRules();
        $this->createPriceHistory();
    }

    public function down()
    {
        $this->forge->dropTable('price_history', true);
        $this->forge->dropTable('pricing_rules', true);
        $this->forge->dropTable('project_price_lists', true);
        $this->forge->dropTable('customer_price_lists', true);
    }

    private function createCustomerPriceLists(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'price_list_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'valid_from' => ['type' => 'DATE', 'null' => true],
            'valid_to' => ['type' => 'DATE', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['customer_id', 'price_list_id']);
        $this->forge->createTable('customer_price_lists', true);
    }

    private function createProjectPriceLists(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'project_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'price_list_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'valid_from' => ['type' => 'DATE', 'null' => true],
            'valid_to' => ['type' => 'DATE', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['project_id', 'price_list_id']);
        $this->forge->createTable('project_price_lists', true);
    }

    private function createPricingRules(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'condition_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'amount'],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'project_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'min_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'start_date' => ['type' => 'DATE', 'null' => true],
            'end_date' => ['type' => 'DATE', 'null' => true],
            'price' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'discount_percent' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'default' => 0],
            'priority' => ['type' => 'INT', 'default' => 100],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['condition_type', 'customer_id', 'project_id']);
        $this->forge->addKey('priority');
        $this->forge->createTable('pricing_rules', true);
    }

    private function createPriceHistory(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'source_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'source_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'old_price' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'new_price' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'changed_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'changed_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id', 'variant_id']);
        $this->forge->createTable('price_history', true);
    }
}
