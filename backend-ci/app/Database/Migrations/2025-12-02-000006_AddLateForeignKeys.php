<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bổ sung các FK còn sót sau master entities.
 *
 * @agent-migration: Late FK additions
 * @agent-pattern: Conditional FK add
 */
class AddLateForeignKeys extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            return;
        }

        $this->addCustomerOrganizationFk();
        $this->addPosOfflineTempFk();
    }

    public function down()
    {
        if ($this->isSqlite()) {
            return;
        }
        $this->dropFkIfExists('customers', 'fk_customers_organization');
        $this->dropFkIfExists('pos_offline_queue', 'fk_pos_offline_queue_temp');
    }

    private function addCustomerOrganizationFk(): void
    {
        if (! $this->db->tableExists('customers') || ! $this->db->tableExists('organizations')) {
            return;
        }
        if ($this->columnExists('customers', 'organization_id')) {
            $this->db->query('ALTER TABLE customers MODIFY organization_id BIGINT UNSIGNED NULL');
            if (! $this->foreignKeyExists('customers', 'fk_customers_organization')) {
                $this->forge->addColumn('customers', [
                    'CONSTRAINT fk_customers_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL ON UPDATE CASCADE',
                ]);
            }
        }
    }

    private function addPosOfflineTempFk(): void
    {
        if (! $this->db->tableExists('pos_offline_queue') || ! $this->db->tableExists('temp_queue')) {
            return;
        }
        if ($this->columnExists('pos_offline_queue', 'temp_id')) {
            $this->db->query('ALTER TABLE pos_offline_queue MODIFY temp_id BIGINT UNSIGNED NULL');
            if (! $this->foreignKeyExists('pos_offline_queue', 'fk_pos_offline_queue_temp')) {
                $this->forge->addColumn('pos_offline_queue', [
                    'CONSTRAINT fk_pos_offline_queue_temp FOREIGN KEY (temp_id) REFERENCES temp_queue(id) ON DELETE SET NULL ON UPDATE CASCADE',
                ]);
            }
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $sql = "SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=? AND TABLE_NAME=? AND CONSTRAINT_NAME=? LIMIT 1";
        return (bool) $this->db->query($sql, [$this->db->getDatabase(), $table, $constraint])->getRowArray();
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1";
        return (bool) $this->db->query($sql, [$this->db->getDatabase(), $table, $column])->getRowArray();
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        if ($this->foreignKeyExists($table, $constraint)) {
            $this->forge->dropForeignKey($table, $constraint);
        }
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
