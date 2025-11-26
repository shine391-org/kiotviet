<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderPaymentsTable extends Migration
{
    public function up()
    {
        // Add payment_status to orders if missing
        if ($this->db->tableExists('orders') && ! $this->db->fieldExists('payment_status', 'orders')) {
            $this->forge->addColumn('orders', [
                'payment_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'after' => 'debt_amount',
                    'comment' => 'unpaid|partial|paid|overpaid'
                ]
            ]);
        }

        // order_payments table
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'order_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => false,
            ],
            'payment_method' => [
                'type' => 'ENUM',
                'constraint' => ['CASH','BANK_TRANSFER','CARD','COD','EWALLET'],
                'null' => false,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => false,
                'default' => '0.00',
            ],
            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        if (! $this->db->tableExists('order_payments')) {
            $this->forge->addField($fields);
            $this->forge->addKey('id', true);
            $this->forge->addKey('order_id');
            $this->forge->createTable('order_payments');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('order_payments')) {
            $this->forge->dropTable('order_payments', true);
        }
        if ($this->db->tableExists('orders') && $this->db->fieldExists('payment_status', 'orders')) {
            $this->forge->dropColumn('orders', 'payment_status');
        }
    }
}
