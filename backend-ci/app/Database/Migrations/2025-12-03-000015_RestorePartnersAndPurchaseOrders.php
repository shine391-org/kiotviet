<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestorePartnersAndPurchaseOrders extends Migration
{
    public function up()
    {
        // 1. Create partners table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'type' => ['type' => 'ENUM', 'constraint' => ['supplier','vendor','distributor','manufacturer']],
            'contact_person' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 20],
            'address' => ['type' => 'TEXT', 'null' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tax_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'bank_account' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'bank_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'credit_limit' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'debt_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'total_purchased' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'status' => ['type' => 'ENUM', 'constraint' => ['active','inactive'], 'default' => 'active'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'deleted_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('partners', true);

        // 2. Create purchase_orders table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'partner_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft','pending','approved','received','completed','cancelled'], 'default' => 'draft'],
            'order_date' => ['type' => 'DATETIME'],
            'expected_date' => ['type' => 'DATETIME', 'null' => true],
            'received_date' => ['type' => 'DATETIME', 'null' => true],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'shipping_fee' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'paid_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'payment_status' => ['type' => 'ENUM', 'constraint' => ['unpaid','partial','paid'], 'default' => 'unpaid'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'deleted_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('order_number');
        $this->forge->addKey(['order_number', 'status']);
        $this->forge->addKey('partner_id');
        $this->forge->addKey('branch_id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('purchase_orders', true);
    }

    public function down()
    {
        $this->forge->dropTable('purchase_orders', true);
        $this->forge->dropTable('partners', true);
    }
}
