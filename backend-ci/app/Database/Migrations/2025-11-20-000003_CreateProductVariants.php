<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductVariants extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'price' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'stock_quantity' => ['type' => 'INT', 'default' => 0],
            'attributes' => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON key/value'],
            'image' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id']);
        $this->forge->createTable('product_variants', true);
    }

    public function down()
    {
        $this->forge->dropTable('product_variants', true);
    }
}
