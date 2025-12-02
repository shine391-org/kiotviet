<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Order templates and subscriptions tables.
 *
 * @agent-migration: Order templates
 * @agent-pattern: Schema + constraints
 */
class CreateOrderTemplateTables extends Migration
{
    public function up()
    {
        $this->createOrderTemplates();
        $this->createOrderTemplateItems();
        $this->createOrderSubscriptions();
    }

    public function down()
    {
        $this->forge->dropTable('order_template_items', true);
        $this->forge->dropTable('order_subscriptions', true);
        $this->forge->dropTable('order_templates', true);
    }

    private function createOrderTemplates(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'frequency' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->addKey(['customer_id', 'is_active']);
        $this->forge->createTable('order_templates', true);
    }

    private function createOrderTemplateItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('template_id');
        $this->forge->addKey(['product_id', 'variant_id']);
        $this->forge->createTable('order_template_items', true);
    }

    private function createOrderSubscriptions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'payment_method' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'order_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'shipping'],
            'next_run_at' => ['type' => 'DATETIME', 'null' => true],
            'last_run_at' => ['type' => 'DATETIME', 'null' => true],
            'frequency_interval' => ['type' => 'INT', 'default' => 7],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('template_id');
        $this->forge->addKey('next_run_at');
        $this->forge->addKey('status');
        $this->forge->createTable('order_subscriptions', true);
    }
}
