<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Delivery note tables.
 *
 * @agent-migration: Delivery notes
 * @agent-pattern: Schema + constraints
 */
class CreateDeliveryNoteTables extends Migration
{
    public function up()
    {
        $this->createDeliveryNotes();
        $this->createDeliveryNoteItems();
    }

    public function down()
    {
        $this->forge->dropTable('delivery_note_items', true);
        $this->forge->dropTable('delivery_notes', true);
    }

    private function createDeliveryNotes(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'delivery_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'delivery_date' => ['type' => 'DATE', 'null' => true],
            'expected_delivery_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'shipping_address' => ['type' => 'TEXT', 'null' => true],
            'tracking_number' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'carrier' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'confirmed_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'confirmed_at' => ['type' => 'DATETIME', 'null' => true],
            'delivered_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'delivered_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('delivery_number');
        $this->forge->addKey(['order_id', 'branch_id']);
        $this->forge->createTable('delivery_notes', true);
    }

    private function createDeliveryNoteItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'delivery_note_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'order_item_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'serial_number' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'delivered_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('delivery_note_id');
        $this->forge->addKey(['product_id', 'variant_id']);
        $this->forge->createTable('delivery_note_items', true);
    }
}
