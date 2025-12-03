<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWarehouseIdToPurchaseOrders extends Migration
{
    public function up()
    {
        $this->forge->addColumn('purchase_orders', [
            'warehouse_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'branch_id'
            ],
        ]);
        
        // Add foreign key
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('purchase_orders');
    }

    public function down()
    {
        $this->forge->dropForeignKey('purchase_orders', 'purchase_orders_warehouse_id_foreign');
        $this->forge->dropColumn('purchase_orders', 'warehouse_id');
    }
}
