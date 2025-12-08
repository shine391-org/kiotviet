<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add missing columns to invoices table for filtering and soft delete support.
 */
class AddDeletedAtToInvoices extends Migration
{
    public function up(): void
    {
        // Add delivery/shipping columns after e_invoice_status
        $this->forge->addColumn('invoices', [
            'delivery_status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'after' => 'e_invoice_status',
            ],
            'shipment_code' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'delivery_status',
            ],
            'shipping_partner' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'shipment_code',
            ],
            'delivery_time' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'shipping_partner',
            ],
            'delivery_note' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'after' => 'delivery_time',
            ],
            'sales_channel' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'delivery_note',
            ],
            'seller_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'sales_channel',
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'payment_status',
            ],
            'tax_discount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
                'after' => 'tax_amount',
            ],
            'payment_discount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
                'after' => 'payment_method',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('invoices', [
            'delivery_status',
            'shipment_code',
            'shipping_partner',
            'delivery_time',
            'delivery_note',
            'sales_channel',
            'seller_id',
            'payment_method',
            'tax_discount',
            'payment_discount',
            'deleted_at',
        ]);
    }
}
