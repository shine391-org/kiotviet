<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Companies, permissions, document shares, and audit logs.
 *
 * @agent-migration: ERP-033 multi-company scope
 * @agent-pattern: Company + ACL schema
 */
class CreateCompanyPermissionTables extends Migration
{
    public function up()
    {
        $this->createCompanies();
        $this->createCompanyPermissions();
        $this->createDocumentShares();
        $this->createAuditLogs();
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('document_shares', true);
        $this->forge->dropTable('company_permissions', true);
        $this->forge->dropTable('companies', true);
    }

    private function createCompanies(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['status', 'is_default']);
        $this->forge->createTable('companies', true);
    }

    private function createCompanyPermissions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'role_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'permissions' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey(['company_id', 'user_id', 'role_name']);
        $this->forge->createTable('company_permissions', true);
    }

    private function createDocumentShares(): void
    {
        // Use raw SQL to control index name length (avoid MySQL identifier limit)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS document_shares (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                entity_type VARCHAR(120) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                shared_with_user_id BIGINT UNSIGNED NULL,
                shared_with_role VARCHAR(100) NULL,
                permissions JSON NULL,
                expires_at DATETIME NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY uq_doc_share_user (company_id, entity_type, entity_id, shared_with_user_id, shared_with_role),
                KEY idx_document_shares_entity (entity_type, entity_id),
                KEY idx_document_shares_company (company_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function createAuditLogs(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'entity_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'action' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'changes' => ['type' => 'JSON', 'null' => true],
            'actor_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('company_id');
        $this->forge->createTable('audit_logs', true);
    }
}
