<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Reorder planning tables for reorder levels and purchase suggestions.
 *
 * @agent-migration: Reorder planning
 * @agent-pattern: Schema + constraints
 */
class CreateReorderPlanningTables extends Migration
{
    public function up()
    {
        $this->createReorderLevels();
        $this->createPurchaseSuggestions();
    }

    public function down()
    {
        $this->forge->dropTable('purchase_suggestions', true);
        $this->forge->dropTable('reorder_levels', true);
    }

    private function createReorderLevels(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'min_level' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'max_level' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'safety_stock' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['branch_id', 'product_id']);
        $this->forge->addKey('is_active');
        $this->forge->addUniqueKey(['product_id', 'variant_id', 'branch_id']);
        $this->forge->createTable('reorder_levels', true);
    }

    private function createPurchaseSuggestions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'reorder_level_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'generated_for_date' => ['type' => 'DATE', 'null' => false],
            'suggested_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'on_hand_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'reserved_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'available_qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'min_level' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'max_level' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'safety_stock' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'purchase_order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'acknowledged_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'acknowledged_at' => ['type' => 'DATETIME', 'null' => true],
            'converted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['branch_id', 'status']);
        $this->forge->addKey('reorder_level_id');
        $this->forge->addKey('purchase_order_id');
        $this->forge->addUniqueKey(['product_id', 'variant_id', 'branch_id', 'generated_for_date'], 'uq_purchase_suggestions_day');
        $this->forge->createTable('purchase_suggestions', true);
    }
}
