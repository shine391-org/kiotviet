<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterCashTransactionsAddPayerFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('cash_transactions')) {
            return;
        }

        $fields = [];
        $add = function(string $col, array $def) use (&$fields) {
            if (! $this->db->fieldExists($col, 'cash_transactions')) {
                $fields[$col] = $def;
            }
        };

        $add('staff_name', ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'created_by_name']);
        $add('payer_code', ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'staff_name']);
        $add('payer_name', ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true, 'after' => 'payer_code']);
        $add('payer_phone', ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'payer_name']);
        $add('payer_address', ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'payer_phone']);
        $add('bank_account', ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'account_name']);
        $add('transfer_note', ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'bank_account']);

        if ($fields) {
            $this->forge->addColumn('cash_transactions', $fields);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('cash_transactions')) {
            return;
        }
        foreach (['staff_name','payer_code','payer_name','payer_phone','payer_address','bank_account','transfer_note'] as $col) {
            if ($this->db->fieldExists($col, 'cash_transactions')) {
                $this->forge->dropColumn('cash_transactions', $col);
            }
        }
    }
}
