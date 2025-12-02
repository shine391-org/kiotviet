<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create invoices and invoice_orders tables.
 *
 * @agent-migration: Invoices
 * @agent-pattern: Schema + FK
 */
class CreateInvoiceTables extends Migration
{
    public function up()
    {
        $jsonType = strtolower($this->db->DBDriver) === 'sqlite3' ? 'TEXT' : 'JSON';

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'comment' => 'Format: HD-{branch}-{counter}'],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'issue_date' => ['type' => 'DATE', 'null' => false],
            'due_date' => ['type' => 'DATE', 'null' => true],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
            'vat_rate' => ['type' => 'DECIMAL', 'constraint' => '5,4', 'null' => false],
            'vat_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
            'total' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
            'pdf_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'meta' => ['type' => $jsonType, 'null' => true, 'comment' => 'Extra invoice metadata'],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('invoice_number');
        $this->forge->addKey(['customer_id', 'issue_date'], false, false, 'idx_customer_issue');
        $this->forge->addKey('branch_id');
        $this->forge->addKey('issue_date');
        $this->forge->addKey('due_date');
        $this->forge->createTable('invoices', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('invoice_id');
        $this->forge->addKey('order_id');
        $this->forge->addUniqueKey(['invoice_id', 'order_id'], 'unique_invoice_order');
        $this->forge->createTable('invoice_orders', true);
    }

    public function down()
    {
        $this->forge->dropTable('invoice_orders', true);
        $this->forge->dropTable('invoices', true);
    }
}
