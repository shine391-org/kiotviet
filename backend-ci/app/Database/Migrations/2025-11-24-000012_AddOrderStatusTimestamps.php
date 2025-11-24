<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add status timestamps and cancellation fields for orders.
 *
 * @agent-migration: Orders status timestamps
 * @agent-pattern: Schema evolution
 */
class AddOrderStatusTimestamps extends Migration
{
    public function up()
    {
        $fields = [
            'confirmed_at' => ['type' => 'DATETIME', 'null' => true],
            'processing_at' => ['type' => 'DATETIME', 'null' => true],
            'shipping_at' => ['type' => 'DATETIME', 'null' => true],
            'delivered_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'cancelled_at' => ['type' => 'DATETIME', 'null' => true],
            'cancellation_reason' => ['type' => 'TEXT', 'null' => true],
            'cod_collected' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ];
        $this->forge->addColumn('orders', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('orders', [
            'confirmed_at','processing_at','shipping_at','delivered_at','completed_at',
            'cancelled_at','cancellation_reason','cod_collected'
        ]);
    }
}
