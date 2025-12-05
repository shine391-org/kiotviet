<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerGroupsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'discount_percent' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->createTable('customer_groups');
        
        // Insert default groups
        $this->db->table('customer_groups')->insertBatch([
            ['name' => 'Khách lẻ', 'description' => 'Khách hàng mua lẻ thông thường', 'discount_percent' => 0, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Khách sỉ', 'description' => 'Khách hàng mua sỉ, đại lý', 'discount_percent' => 5, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Khách VIP', 'description' => 'Khách hàng VIP, thân thiết', 'discount_percent' => 10, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('customer_groups');
    }
}
