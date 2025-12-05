<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Subscription tables for recurring orders.
 *
 * @agent-migration: Subscriptions
 * @agent-pattern: Schema + cycles log
 */
class CreateSubscriptionTables extends Migration
{
    public function up()
    {
        $this->createSubscriptions();
        $this->createCycles();
    }

    public function down()
    {
        $this->forge->dropTable('subscription_cycles', true);
        $this->forge->dropTable('subscriptions', true);
    }

    private function createSubscriptions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'template_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'plan_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'interval_days' => ['type' => 'INT', 'default' => 30],
            'next_run_at' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'next_run_at']);
        $this->forge->createTable('subscriptions', true);
    }

    private function createCycles(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'subscription_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'run_date' => ['type' => 'DATE', 'null' => false],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'processed'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['subscription_id', 'run_date']);
        $this->forge->addKey('status');
        $this->forge->createTable('subscription_cycles', true);
    }
}
