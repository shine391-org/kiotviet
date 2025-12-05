<?php

namespace Tests\Support\Database;

/**
 * Reset RBAC/ACL related tables for tests.
 *
 * @agent-test-support: RBAC schema reset
 * @agent-pattern: Truncate with FK disable
 * @agent-reusable: MEDIUM
 */
trait RbacSchemaTrait
{
    protected function resetRbacSchema(): void
    {
        $tables = [
            'document_shares',
            'company_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'companies',
            'users',
        ];

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $existing = array_flip($this->db->listTables());
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                $this->db->table($table)->truncate();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
