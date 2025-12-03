<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompanyTables extends Migration
{
    public function up()
    {
        // companies
        $this->db->query("CREATE TABLE IF NOT EXISTS companies (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(255) NOT NULL,
            is_default TINYINT(1) DEFAULT 0,
            status VARCHAR(30) DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_company_code (code),
            KEY idx_company_status (status, is_default)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // company_permissions
        $this->db->query("CREATE TABLE IF NOT EXISTS company_permissions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            role_name VARCHAR(100) NULL,
            permissions JSON NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_company_permission (company_id, user_id, role_name),
            KEY idx_company_permissions_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // document_shares
        $this->db->query("CREATE TABLE IF NOT EXISTS document_shares (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // audit_logs
        $this->db->query("CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id BIGINT UNSIGNED NOT NULL,
            entity_type VARCHAR(120) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(80) NOT NULL,
            changes JSON NULL,
            actor_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            KEY idx_audit_logs_entity (entity_type, entity_id),
            KEY idx_audit_logs_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('document_shares', true);
        $this->forge->dropTable('company_permissions', true);
        $this->forge->dropTable('companies', true);
    }
}