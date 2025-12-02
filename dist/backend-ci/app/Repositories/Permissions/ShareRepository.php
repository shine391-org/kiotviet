<?php

namespace App\Repositories\Permissions;

use App\Models\DocumentShareModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Repository for document share records.
 *
 * @agent-repository: Document shares
 * @agent-pattern: Repository layer for ACL
 * @agent-reusable: MEDIUM
 */
class ShareRepository
{
    protected DocumentShareModel $shares;
    protected BaseConnection $db;

    public function __construct(?DocumentShareModel $shares = null, ?BaseConnection $db = null)
    {
        $this->shares = $shares ?? new DocumentShareModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = [
            'company_id' => (int) $data['company_id'],
            'entity_type' => $data['entity_type'],
            'entity_id' => (int) $data['entity_id'],
            'shared_with_user_id' => $data['shared_with_user_id'] ?? null,
            'shared_with_role' => $data['shared_with_role'] ?? null,
            'permissions' => json_encode(array_values($data['permissions'] ?? [])),
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        $existing = $this->db->table('document_shares')
            ->where('company_id', $payload['company_id'])
            ->where('entity_type', $payload['entity_type'])
            ->where('entity_id', $payload['entity_id'])
            ->where('shared_with_user_id', $payload['shared_with_user_id'])
            ->where('shared_with_role', $payload['shared_with_role'])
            ->get()
            ->getRowArray();

        if ($existing) {
            $this->shares->update((int) $existing['id'], [
                'permissions' => $payload['permissions'],
                'expires_at' => $payload['expires_at'] ?? null,
                'updated_at' => $this->now(),
            ]);
            $payload['id'] = (int) $existing['id'];
        } else {
            $this->shares->insert($payload);
            $payload['id'] = (int) $this->shares->getInsertID();
        }

        return $this->mapShare($payload);
    }

    public function delete(int $id): void
    {
        $this->shares->delete($id);
    }

    public function find(int $id): ?array
    {
        $row = $this->shares->find($id);
        return $row ? $this->mapShare($row) : null;
    }

    public function list(array $filters = []): array
    {
        $builder = $this->shares->builder();
        foreach (['company_id','entity_id','shared_with_user_id'] as $field) {
            if (! empty($filters[$field])) {
                $builder->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['entity_type'])) {
            $builder->where('entity_type', $filters['entity_type']);
        }
        $rows = $builder->orderBy('id', 'ASC')->get()->getResultArray();
        return array_map(fn ($row) => $this->mapShare($row), $rows);
    }

    public function findForUser(int $companyId, string $entityType, int $entityId, int $userId, ?string $role = null): ?array
    {
        $builder = $this->db->table('document_shares')
            ->where('company_id', $companyId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->groupStart()
                ->where('shared_with_user_id', $userId);
        if ($role !== null) {
            $builder->orWhere('shared_with_role', $role);
        }
        $row = $builder->groupEnd()
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        return $row ? $this->mapShare($row) : null;
    }

    private function mapShare(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['company_id'] = isset($row['company_id']) ? (int) $row['company_id'] : null;
        $row['entity_id'] = isset($row['entity_id']) ? (int) $row['entity_id'] : null;
        $row['shared_with_user_id'] = isset($row['shared_with_user_id']) ? (int) $row['shared_with_user_id'] : null;
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

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
