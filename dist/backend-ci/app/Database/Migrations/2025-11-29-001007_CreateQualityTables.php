<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Quality management tables for inspections and parameters.
 *
 * @agent-migration: Quality inspections
 * @agent-pattern: Schema + constraints
 */
class CreateQualityTables extends Migration
{
    public function up()
    {
        $this->createQualityParameters();
        $this->createQualityInspections();
        $this->createQualityInspectionItems();
    }

    public function down()
    {
        $this->forge->dropTable('quality_inspection_items', true);
        $this->forge->dropTable('quality_inspections', true);
        $this->forge->dropTable('quality_parameters', true);
    }

    private function createQualityParameters(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'uom' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'min_value' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'null' => true],
            'max_value' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'null' => true],
            'specification' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('is_active');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('quality_parameters', true);
    }

    private function createQualityInspections(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'reference_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'reference_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'result' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'inspected_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'inspected_at' => ['type' => 'DATETIME', 'null' => true],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'approved_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'rejected_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'rejected_at' => ['type' => 'DATETIME', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['reference_type', 'reference_id']);
        $this->forge->addKey('status');
        $this->forge->addKey('result');
        $this->forge->createTable('quality_inspections', true);
    }

    private function createQualityInspectionItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'inspection_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'parameter_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'parameter_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'uom' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'value_numeric' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'null' => true],
            'value_text' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pass_flag' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('inspection_id');
        $this->forge->addKey('parameter_id');
        $this->forge->createTable('quality_inspection_items', true);
    }
}
