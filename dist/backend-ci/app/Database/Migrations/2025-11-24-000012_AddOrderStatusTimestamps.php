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
        $db = \Config\Database::connect($this->DBGroup);
        
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
        
        // Add columns only if they don't exist (idempotent)
        foreach ($fields as $fieldName => $fieldDef) {
            if (!$db->fieldExists($fieldName, 'orders')) {
                $this->forge->addColumn('orders', [$fieldName => $fieldDef]);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect($this->DBGroup);
        $columns = [
            'confirmed_at','processing_at','shipping_at','delivered_at','completed_at',
            'cancelled_at','cancellation_reason','cod_collected'
        ];
        
        // Drop columns only if they exist (idempotent)
        $existingColumns = [];
        foreach ($columns as $col) {
            if ($db->fieldExists($col, 'orders')) {
                $existingColumns[] = $col;
            }
        }
        
        if (!empty($existingColumns)) {
            $this->forge->dropColumn('orders', $existingColumns);
        }
    }
}
