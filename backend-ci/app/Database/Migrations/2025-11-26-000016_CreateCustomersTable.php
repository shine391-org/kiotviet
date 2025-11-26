<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create or extend customers table with VAT/e-invoice fields.
 *
 * @agent-migration: Customers extended schema
 * @agent-pattern: Backward-safe schema upgrade
 * @agent-reusable: MEDIUM
 */
class CreateCustomersTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('customers')) {
            $this->createFreshTable();
            return;
        }

        $this->addMissingColumns();
        $this->ensureIndexes();
    }

    public function down()
    {
        $this->forge->dropTable('customers', true);
    }

    /**
     * Create customers table from scratch (used on new environments).
     */
    private function createFreshTable(): void
    {
        $this->forge->addField($this->columnDefinitions());
        $this->forge->addKey('id', true);
        $this->forge->addKey('tax_code', false, false, 'idx_customers_tax_code');
        $this->forge->addUniqueKey(['organization_id', 'tax_code'], 'unique_tax_code_per_org');
        $this->forge->createTable('customers', true);
    }

    /**
     * Add any missing columns to existing table (non-destructive).
     */
    private function addMissingColumns(): void
    {
        $existing = array_map('strtolower', $this->db->getFieldNames('customers'));
        $definitions = $this->columnDefinitions();
        $additions = [];

        foreach ($definitions as $name => $definition) {
            if (! in_array(strtolower($name), $existing, true)) {
                $additions[$name] = $definition;
            }
        }

        if (! empty($additions)) {
            $this->forge->addColumn('customers', $additions);
        }
    }

    /**
     * Ensure indexes exist for tax code lookups.
     */
    private function ensureIndexes(): void
    {
        $indexes = $this->db->query("SHOW INDEX FROM customers WHERE Key_name = 'idx_customers_tax_code'")->getResultArray();
        if (empty($indexes)) {
            $this->db->query('CREATE INDEX idx_customers_tax_code ON customers (tax_code)');
        }

        $unique = $this->db->query("SHOW INDEX FROM customers WHERE Key_name = 'unique_tax_code_per_org'")->getResultArray();
        if (empty($unique)) {
            $this->db->query('CREATE UNIQUE INDEX unique_tax_code_per_org ON customers (organization_id, tax_code)');
        }
    }

    /**
     * Column map shared by create/add flows.
     */
    private function columnDefinitions(): array
    {
        return [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'organization_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 1],
            'customer_group_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'phone2' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'gender' => ['type' => "ENUM('MALE','FEMALE','OTHER')", 'null' => true],
            'facebook' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'customer_type' => ['type' => "ENUM('INDIVIDUAL','COMPANY','HOUSEHOLD')", 'null' => false, 'default' => 'INDIVIDUAL'],
            'company_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'tax_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'buyer_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'invoice_company_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'invoice_address' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'invoice_province' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'invoice_district' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'invoice_ward' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'invoice_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'invoice_phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'cccd_cmnd' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'id_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'bank_account' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'bank_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }
}
