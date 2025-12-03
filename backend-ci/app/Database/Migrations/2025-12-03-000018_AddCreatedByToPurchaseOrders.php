<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedByToPurchaseOrders extends Migration
{
    public function up()
    {
        $this->forge->addColumn('purchase_orders', [
            'created_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'user_id'
            ],
        ]);
        
        // Add foreign key to users table
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('purchase_orders');
    }

    public function down()
    {
        $this->forge->dropForeignKey('purchase_orders', 'purchase_orders_created_by_foreign');
        $this->forge->dropColumn('purchase_orders', 'created_by');
    }
}
