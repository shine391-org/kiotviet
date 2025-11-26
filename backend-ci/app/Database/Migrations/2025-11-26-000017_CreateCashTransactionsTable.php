<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create cash_transactions table for cash management.
 *
 * @agent-migration: Cash transactions table
 * @agent-pattern: Financial tracking schema
 */
class CreateCashTransactionsTable extends Migration
{
    public function up()
    {
        // Create cash_transactions table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['RECEIPT', 'PAYMENT'],
                'null' => false,
                'comment' => 'Transaction type: RECEIPT (thu) or PAYMENT (chi)',
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => false,
                'comment' => 'Transaction amount (always positive)',
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Transaction category (sales, purchase, salary, etc.)',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Transaction description',
            ],
            'reference_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Reference type: order, purchase_order, expense, manual',
            ],
            'reference_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'comment' => 'Reference ID (order_id, purchase_order_id, etc.)',
            ],
            'reference_code' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Reference code (HD001, PO001, etc.)',
            ],
            'branch_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => false,
                'comment' => 'Branch ID',
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => false,
                'comment' => 'User who created this transaction',
            ],
            'transaction_date' => [
                'type' => 'DATE',
                'null' => false,
                'comment' => 'Transaction date',
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Additional notes',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Soft delete timestamp',
            ],
        ]);

        // Primary key
        $this->forge->addKey('id', true);

        // Indexes for performance
        $this->forge->addKey(['type', 'category'], false, false, 'idx_type_category');
        $this->forge->addKey('branch_id', false, false, 'idx_branch');
        $this->forge->addKey(['reference_type', 'reference_id'], false, false, 'idx_reference');
        $this->forge->addKey('transaction_date', false, false, 'idx_transaction_date');
        $this->forge->addKey('created_by', false, false, 'idx_created_by');
        $this->forge->addKey('deleted_at', false, false, 'idx_deleted_at');

        // Create table
        $this->forge->createTable('cash_transactions', true);

        // Add foreign keys (only if tables exist)
        if ($this->db->tableExists('branches')) {
            $this->forge->addForeignKey(
                'branch_id',
                'branches',
                'id',
                'RESTRICT',
                'RESTRICT',
                'fk_cash_transactions_branch'
            );
        }

        if ($this->db->tableExists('users')) {
            $this->forge->addForeignKey(
                'created_by',
                'users',
                'id',
                'RESTRICT',
                'RESTRICT',
                'fk_cash_transactions_user'
            );
        }
    }

    public function down()
    {
        // Drop foreign keys first
        if ($this->db->tableExists('cash_transactions')) {
            // Drop foreign keys if they exist
            $this->forge->dropForeignKey('cash_transactions', 'fk_cash_transactions_branch');
            $this->forge->dropForeignKey('cash_transactions', 'fk_cash_transactions_user');
        }

        // Drop table
        $this->forge->dropTable('cash_transactions', true);
    }
}