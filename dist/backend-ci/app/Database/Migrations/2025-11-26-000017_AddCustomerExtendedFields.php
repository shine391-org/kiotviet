<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add extended customer fields to align with UI/FE requirements.
 *
 * @agent-migration: Customers extended v2
 * @agent-pattern: Safe additive columns
 * @agent-reusable: MEDIUM
 */
class AddCustomerExtendedFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('customers')) {
            return;
        }

        $existing = array_map('strtolower', $this->db->getFieldNames('customers'));
        $columns = $this->columnDefinitions();
        $additions = [];

        foreach ($columns as $name => $definition) {
            if (! in_array(strtolower($name), $existing, true)) {
                $additions[$name] = $definition;
            }
        }

        if (! empty($additions)) {
            $this->forge->addColumn('customers', $additions);
        }
    }

    public function down()
    {
        // Non-destructive rollback (keep data)
    }

    private function columnDefinitions(): array
    {
        return [
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'id'],
            'address' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'facebook'],
            'province' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'address'],
            'district' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'province'],
            'ward' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'district'],
            'birthday' => ['type' => 'DATE', 'null' => true, 'after' => 'customer_type'],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'updated_at'],
            'status' => ['type' => 'ENUM', 'constraint' => ['ACTIVE', 'INACTIVE'], 'null' => true, 'default' => 'ACTIVE', 'after' => 'created_by'],
            'last_transaction_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'status'],
            'current_debt' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0, 'null' => false, 'after' => 'last_transaction_at'],
            'total_sales' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0, 'null' => false, 'after' => 'current_debt'],
            'total_sales_net' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0, 'null' => false, 'after' => 'total_sales'],
        ];
    }
}
