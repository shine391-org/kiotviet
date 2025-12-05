<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterCashTransactionsAddPaymentFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('cash_transactions')) {
            return;
        }

        $fields = [];
        if (! $this->db->fieldExists('payment_method', 'cash_transactions')) {
            $fields['payment_method'] = [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'category',
                'comment' => 'cash|bank|ewallet',
            ];
        }
        if (! $this->db->fieldExists('status', 'cash_transactions')) {
            $fields['status'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'payment_method',
                'comment' => 'approved|cancelled|pending',
            ];
        }
        if (! $this->db->fieldExists('account_name', 'cash_transactions')) {
            $fields['account_name'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'status',
            ];
        }
        if (! $this->db->fieldExists('created_by_name', 'cash_transactions')) {
            $fields['created_by_name'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'created_by',
            ];
        }

        if ($fields) {
            $this->forge->addColumn('cash_transactions', $fields);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('cash_transactions')) {
            return;
        }
        foreach (['payment_method','status','account_name','created_by_name'] as $col) {
            if ($this->db->fieldExists($col, 'cash_transactions')) {
                $this->forge->dropColumn('cash_transactions', $col);
            }
        }
    }
}
