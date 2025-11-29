<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Approval engine schema.
 *
 * @agent-migration: Approvals
 * @agent-pattern: Schema + constraints
 */
class CreateApprovalTables extends Migration
{
    public function up()
    {
        $this->createApprovalRules();
        $this->createApprovals();
        $this->createApprovalActions();
    }

    public function down()
    {
        $this->forge->dropTable('approval_actions', true);
        $this->forge->dropTable('approvals', true);
        $this->forge->dropTable('order_approval_rules', true);
    }

    private function createApprovalRules(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'condition_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'amount'],
            'threshold_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'custom_condition' => ['type' => 'TEXT', 'null' => true],
            'approver_ids' => ['type' => 'TEXT', 'null' => false],
            'priority' => ['type' => 'INT', 'default' => 100],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['condition_type', 'customer_id']);
        $this->forge->addKey('priority');
        $this->forge->createTable('order_approval_rules', true);
    }

    private function createApprovals(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'order'],
            'entity_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'approver_queue' => ['type' => 'TEXT', 'null' => false],
            'current_index' => ['type' => 'INT', 'default' => 0],
            'current_approver_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'requested_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'requested_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey(['order_id', 'status']);
        $this->forge->createTable('approvals', true);
    }

    private function createApprovalActions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'approval_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'action' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'actor_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('approval_id');
        $this->forge->createTable('approval_actions', true);
    }
}
