<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create returns and return_items tables.
 *
 * @agent-migration: Returns
 * @agent-pattern: Schema + FK
 */
class CreateReturnTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'return_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'return_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
            'refund_shipping_fee' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'refund_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'refund_method' => ['type' => 'ENUM', 'constraint' => ['cash', 'bank_transfer'], 'null' => true],
            'reason' => ['type' => 'ENUM', 'constraint' => ['defective', 'wrong_item', 'not_satisfied', 'other'], 'null' => false],
            'reason_detail' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected', 'completed'], 'default' => 'pending'],
            'approved_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'rejected_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'rejected_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'lock_version' => ['type' => 'INT', 'constraint' => 10, 'default' => 0],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('return_number');
        $this->forge->addKey('order_id');
        $this->forge->addKey('customer_id');
        $this->forge->addKey(['status', 'created_at'], false, false, 'idx_status_created');
        $this->forge->createTable('returns', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'return_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'order_item_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'quantity_returned' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => false],
            'item_condition' => ['type' => 'ENUM', 'constraint' => ['new', 'used', 'damaged'], 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('return_id');
        $this->forge->addKey('order_item_id');
        $this->forge->createTable('return_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('return_items', true);
        $this->forge->dropTable('returns', true);
    }
}
