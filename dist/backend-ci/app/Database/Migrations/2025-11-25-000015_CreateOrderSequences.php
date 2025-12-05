<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create order_sequences table for order number generation.
 *
 * @agent-migration: Order sequences
 * @agent-pattern: Schema + FK ready
 */
class CreateOrderSequences extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'sequence_number' => ['type' => 'INT', 'constraint' => 10, 'default' => 1],
            'prefix' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ORD'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['branch_id', 'prefix'], 'uk_branch_prefix');
        $this->forge->addKey('branch_id');
        $this->forge->createTable('order_sequences', true);
    }

    public function down()
    {
        $this->forge->dropTable('order_sequences', true);
    }
}