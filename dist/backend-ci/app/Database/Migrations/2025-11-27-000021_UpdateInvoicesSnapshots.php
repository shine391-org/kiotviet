<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Update invoices schema with snapshot fields + payment status, and create order_payments table.
 *
 * @agent-migration: Invoice snapshots & payments
 */
class UpdateInvoicesSnapshots extends Migration
{
    public function up()
    {
        // Invoices: add snapshot/payment fields and allow nullable invoice_number for draft.
        $this->forge->modifyColumn('invoices', [
            'invoice_number' => [
                'name'       => 'invoice_number',
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Generated when issuing invoice',
            ],
        ]);

        $db = \Config\Database::connect($this->DBGroup);
        $fields = [
            'invoice_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft', 'null' => false],
            'invoice_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'standard', 'null' => false],
            'e_invoice_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending', 'null' => true],
            'goods_total' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'discount_total' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'net_total' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'other_fee' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'shipping_fee' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'customer_payable' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'customer_paid' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'cod_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'rounding_adjustment' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'payment_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'unpaid', 'null' => false],
            'total_paid' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
            'currency_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'VND', 'null' => false],
            'exchange_rate' => ['type' => 'DECIMAL', 'constraint' => '15,6', 'default' => 1, 'null' => false],
            'last_payment_date' => ['type' => 'DATETIME', 'null' => true],
        ];
        
        foreach ($fields as $fieldName => $fieldDef) {
            if (! $db->fieldExists($fieldName, 'invoices')) {
                $this->forge->addColumn('invoices', [$fieldName => $fieldDef]);
            }
        }

        // Order payments table for multi-method payments.
        if (! $this->db->tableExists('order_payments')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'method' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'completed', 'null' => false],
                'ref_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'paid_at' => ['type' => 'DATETIME', 'null' => true],
                'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('order_id');
            $this->forge->addKey('paid_at');
            $this->forge->createTable('order_payments', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('order_payments')) {
            $this->forge->dropTable('order_payments', true);
        }
        // Drop added invoice columns (safe if exist)
        foreach ([
            'invoice_status','invoice_type','e_invoice_status','goods_total','discount_total','net_total',
            'tax_amount','other_fee','shipping_fee','customer_payable','customer_paid','cod_amount',
            'rounding_adjustment','payment_status','total_paid','currency_code','exchange_rate','last_payment_date'
        ] as $col) {
            if ($this->db->fieldExists($col, 'invoices')) {
                $this->forge->dropColumn('invoices', $col);
            }
        }
        // revert invoice_number nullable change
        $this->forge->modifyColumn('invoices', [
            'invoice_number' => [
                'name'       => 'invoice_number',
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'comment'    => 'Format: HD-{branch}-{counter}',
            ],
        ]);
    }
}
