<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Extend orders schema for full order creation.
 *
 * @agent-migration: Orders create fields
 * @agent-pattern: Schema evolution
 */
class UpdateOrdersForCreate extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect($this->DBGroup);
        
        // Extra columns for orders
        $fields = [
            'order_number' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'comment' => 'ORD-000001',
                'after' => 'id',
            ],
            'order_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pos',
                'comment' => 'pos|shipping',
                'after' => 'customer_group_id',
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'order_type',
            ],
            'shipping_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'default' => 0,
                'after' => 'discount_total',
            ],
            'paid_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'default' => 0,
                'after' => 'total',
            ],
            'debt_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'default' => 0,
                'after' => 'paid_amount',
            ],
            'is_paid' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'debt_amount',
            ],
            'shipping_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'is_paid',
            ],
            'shipping_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'after' => 'shipping_name',
            ],
            'shipping_address' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'shipping_phone',
            ],
            'shipping_ward' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'shipping_address',
            ],
            'shipping_district' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'shipping_ward',
            ],
            'shipping_city' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'shipping_district',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'shipping_city',
            ],
        ];

        foreach ($fields as $fieldName => $fieldDef) {
            if (! $db->fieldExists($fieldName, 'orders')) {
                $this->forge->addColumn('orders', [$fieldName => $fieldDef]);
            }
        }

        // indexes
        $this->forge->addKey('order_number');
        $this->forge->addUniqueKey('order_number');
    }

    public function down()
    {
        $this->forge->dropColumn('orders', [
            'order_number',
            'order_type',
            'payment_method',
            'shipping_fee',
            'paid_amount',
            'debt_amount',
            'is_paid',
            'shipping_name',
            'shipping_phone',
            'shipping_address',
            'shipping_ward',
            'shipping_district',
            'shipping_city',
            'notes',
        ]);
    }
}
