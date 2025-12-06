<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create location tables for Vietnam administrative divisions
 * provinces -> districts -> wards
 */
class CreateLocationTables extends Migration
{
    public function up()
    {
        // Provinces table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'name_en' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'full_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'full_name_en' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'code_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'sort_order' => [
                'type' => 'INT',
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_province_code');
        $this->forge->addKey('is_active', false, false, 'idx_province_active');
        $this->forge->createTable('provinces', true);

        // Districts table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'province_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => false,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'name_en' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'full_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'full_name_en' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'code_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'sort_order' => [
                'type' => 'INT',
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_district_code');
        $this->forge->addKey('province_id', false, false, 'idx_district_province');
        $this->forge->addKey('is_active', false, false, 'idx_district_active');
        $this->forge->addForeignKey('province_id', 'provinces', 'id', 'CASCADE', 'CASCADE', 'fk_district_province');
        $this->forge->createTable('districts', true);

        // Wards table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'district_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => false,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'name_en' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'full_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'full_name_en' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'code_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'sort_order' => [
                'type' => 'INT',
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_ward_code');
        $this->forge->addKey('district_id', false, false, 'idx_ward_district');
        $this->forge->addKey('is_active', false, false, 'idx_ward_active');
        $this->forge->addForeignKey('district_id', 'districts', 'id', 'CASCADE', 'CASCADE', 'fk_ward_district');
        $this->forge->createTable('wards', true);
    }

    public function down()
    {
        $this->forge->dropTable('wards', true);
        $this->forge->dropTable('districts', true);
        $this->forge->dropTable('provinces', true);
    }
}
