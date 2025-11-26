<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Add pricing formula fields to price_lists. @agent-migration: Price list formula @agent-pattern: Schema evolution */
class AddPriceListFormula extends Migration
{
    public function up()
    {
        // Idempotent: skip if all schema elements already added (common in seeded dev DB)
        $db = \Config\Database::connect();
        
        // Check if all columns exist
        $columnsExist = $db->fieldExists('formula', 'price_lists') &&
                       $db->fieldExists('base_price_list_id', 'price_lists') &&
                       $db->fieldExists('auto_update', 'price_lists') &&
                       $db->fieldExists('rounding_rule', 'price_lists');
        
        // Check if index exists
        $indexExists = false;
        if ($columnsExist) {
            $indexes = $db->getIndexData('price_lists');
            foreach ($indexes as $index) {
                if ($index->name === 'idx_base_price_list') {
                    $indexExists = true;
                    break;
                }
            }
        }
        
        // Check if foreign key exists
        $foreignKeyExists = false;
        if ($columnsExist) {
            $foreignKeys = $db->getForeignKeyData('price_lists');
            foreach ($foreignKeys as $fk) {
                if ($fk->constraint_name === 'price_lists_base_price_list_id_foreign') {
                    $foreignKeyExists = true;
                    break;
                }
            }
        }
        
        // Skip migration if everything already exists
        if (! $columnsExist) {
            // Add only missing columns to avoid duplicate errors
            $columns = [];
            if (! $db->fieldExists('formula', 'price_lists')) {
                $columns['formula'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'comment' => 'Công thức tính giá (VD: base * 0.9)',
                ];
            }
            if (! $db->fieldExists('base_price_list_id', 'price_lists')) {
                $columns['base_price_list_id'] = [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Bảng giá gốc dùng làm base',
                ];
            }
            if (! $db->fieldExists('auto_update', 'price_lists')) {
                $columns['auto_update'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'comment' => 'Tự động cập nhật khi bảng giá gốc đổi',
                ];
            }
            if (! $db->fieldExists('rounding_rule', 'price_lists')) {
                $columns['rounding_rule'] = [
                    'type' => 'ENUM',
                    'constraint' => ['none', 'thousand', 'ten_thousand', 'hundred'],
                    'default' => 'none',
                    'null' => false,
                    'comment' => 'Quy tắc làm tròn giá',
                ];
            }

            if (! empty($columns)) {
                $this->forge->addColumn('price_lists', $columns);
            }
        }

        if (! $indexExists) {
            $this->forge->addKey('base_price_list_id', false, false, 'idx_base_price_list');
        }
        if (! $foreignKeyExists) {
            $this->forge->addForeignKey('base_price_list_id', 'price_lists', 'id', 'SET NULL', 'CASCADE');
        }

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
