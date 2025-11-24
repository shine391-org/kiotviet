<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Add pricing formula fields to price_lists. @agent-migration: Price list formula @agent-pattern: Schema evolution */
class AddPriceListFormula extends Migration
{
    public function up()
    {
        $this->forge->addColumn('price_lists', [
            'formula' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Công thức tính giá (VD: base * 0.9)',
            ],
            'base_price_list_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'comment' => 'Bảng giá gốc dùng làm base',
            ],
            'auto_update' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Tự động cập nhật khi bảng giá gốc đổi',
            ],
            'rounding_rule' => [
                'type' => 'ENUM',
                'constraint' => ['none', 'thousand', 'ten_thousand', 'hundred'],
                'default' => 'none',
                'null' => false,
                'comment' => 'Quy tắc làm tròn giá',
            ],
        ]);

        $this->forge->addKey('base_price_list_id', false, false, 'idx_base_price_list');
        $this->forge->addForeignKey('base_price_list_id', 'price_lists', 'id', 'SET NULL', 'CASCADE');
    }

    public function down()
    {
        $this->forge->dropForeignKey('price_lists', 'price_lists_base_price_list_id_foreign');
        $this->forge->dropKey('price_lists', 'idx_base_price_list');
        $this->forge->dropColumn('price_lists', ['formula', 'base_price_list_id', 'auto_update', 'rounding_rule']);
    }
}
