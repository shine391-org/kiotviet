<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerNameToInvoices extends Migration
{
    public function up()
    {
        $this->forge->addColumn('invoices', [
            'customer_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'customer_id',
            ],
        ]);
        
        // Optimize: index customer_name for search? maybe later.
    }

    public function down()
    {
        $this->forge->dropColumn('invoices', 'customer_name');
    }
}
