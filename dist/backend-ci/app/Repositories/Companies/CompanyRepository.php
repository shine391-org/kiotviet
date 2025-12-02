<?php

namespace App\Repositories\Companies;

use App\Models\CompanyModel;
use App\Models\CompanyPermissionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Company repository for company records and scoped permissions.
 *
 * @agent-repository: Companies
 * @agent-pattern: Repository split for company + permissions
 * @agent-reusable: MEDIUM
 */
class CompanyRepository
{
    protected CompanyModel $companies;
    protected CompanyPermissionModel $permissions;
    protected BaseConnection $db;

    public function __construct(
        ?CompanyModel $companies = null,
        ?CompanyPermissionModel $permissions = null,
        ?BaseConnection $db = null
    ) {
        $this->companies = $companies ?? new CompanyModel();
        $this->permissions = $permissions ?? new CompanyPermissionModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = $data + [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->companies->insert($payload);
        $payload['id'] = (int) $this->companies->getInsertID();
        if (! empty($payload['is_default'])) {
            $this->clearOtherDefaults($payload['id']);
        }
        return $this->mapCompany($payload);
    }

    public function update(int $id, array $data): array
    {
        $payload = $data + ['updated_at' => $this->now()];
        $this->companies->update($id, $payload);
        if (! empty($payload['is_default'])) {
            $this->clearOtherDefaults($id);
        }
        $row = $this->companies->find($id);
        return $row ? $this->mapCompany($row) : [];
    }

    public function find(int $id): ?array
    {
        $row = $this->companies->find($id);
        return $row ? $this->mapCompany($row) : null;
    }

    public function list(array $filters = []): array
    {
        $builder = $this->companies->builder();
        if (! empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (! empty($filters['is_default'])) {
            $builder->where('is_default', 1);
        }
        $rows = $builder->orderBy('id', 'ASC')->get()->getResultArray();
        return array_map(fn ($row) => $this->mapCompany($row), $rows);
    }

    public function assignPermission(array $data): array
    {
        $payload = [
            'company_id' => (int) $data['company_id'],
            'user_id' => $data['user_id'] ?? null,
            'role_name' => $data['role_name'] ?? null,
            'permissions' => json_encode(array_values($data['permissions'] ?? [])),
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        $existing = $this->db->table('company_permissions')
            ->where('company_id', $payload['company_id'])
            ->where('user_id', $payload['user_id'])
            ->where('role_name', $payload['role_name'])
            ->get()
            ->getRowArray();

        if ($existing) {
            $this->permissions->update((int) $existing['id'], [
                'permissions' => $payload['permissions'],
                'updated_at' => $this->now(),
            ]);
            $payload['id'] = (int) $existing['id'];
        } else {
            $this->permissions->insert($payload);
            $payload['id'] = (int) $this->permissions->getInsertID();
        }

        return $this->mapPermissionRow($payload);
    }

    public function listPermissions(int $companyId): array
    {
        $rows = $this->db->table('company_permissions')
            ->where('company_id', $companyId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        return array_map(fn ($row) => $this->mapPermissionRow($row), $rows);
    }

    public function findPermission(int $companyId, int $userId): ?array
    {
        $row = $this->db->table('company_permissions')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();
        return $row ? $this->mapPermissionRow($row) : null;
    }

    private function mapCompany(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['is_default'] = ! empty($row['is_default']);
        return $row;
    }

    private function mapPermissionRow(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['company_id'] = isset($row['company_id']) ? (int) $row['company_id'] : null;
        $row['user_id'] = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $row['permissions'] = $this->decodePermissions($row['permissions'] ?? null);
        return $row;
    }

    private function decodePermissions($json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        if (is_array($json)) {
            return $json;
        }
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function clearOtherDefaults(int $companyId): void
    {
        $this->db->table('companies')
            ->where('id !=', $companyId)
            ->set('is_default', 0)
            ->update();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
