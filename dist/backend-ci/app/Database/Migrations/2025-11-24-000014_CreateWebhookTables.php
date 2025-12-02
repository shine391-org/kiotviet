<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Webhook subscriptions and events.
 *
 * @agent-migration: Webhooks
 * @agent-pattern: Schema + queue
 */
class CreateWebhookTables extends Migration
{
    public function up()
    {
        $jsonType = strtolower($this->db->DBDriver) === 'sqlite3' ? 'TEXT' : 'JSON';

        // webhook_subscriptions
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'event' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'target_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'secret' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['event', 'is_active']);
        $this->forge->createTable('webhook_subscriptions', true);

        // webhook_events (queue)
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'event' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'payload' => ['type' => $jsonType, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'attempts' => ['type' => 'INT', 'default' => 0],
            'last_error' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event');
        $this->forge->addKey('status');
        $this->forge->createTable('webhook_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('webhook_events', true);
        $this->forge->dropTable('webhook_subscriptions', true);
    }
}
