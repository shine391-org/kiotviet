<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ecommerce webhook logs for idempotency.
 *
 * @agent-migration: Ecommerce webhook logs
 * @agent-pattern: Schema + idempotency key
 */
class CreateEcommerceWebhookLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'source' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'processed'],
            'payload_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'processed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('idempotency_key');
        $this->forge->addKey(['event_type', 'status']);
        $this->forge->createTable('ecommerce_webhook_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('ecommerce_webhook_logs', true);
    }
}
