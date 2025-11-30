<?php

namespace App\Repositories\Audits;

use App\Models\AuditLogModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Audit log repository for tracking document history.
 *
 * @agent-repository: Audit logs
 * @agent-pattern: Repository layer
 * @agent-reusable: MEDIUM
 */
class AuditLogRepository
{
    protected AuditLogModel $logs;
    protected BaseConnection $db;

    public function __construct(?AuditLogModel $logs = null, ?BaseConnection $db = null)
    {
        $this->logs = $logs ?? new AuditLogModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function log(array $data): array
    {
        $payload = [
            'company_id' => (int) $data['company_id'],
            'entity_type' => $data['entity_type'],
            'entity_id' => (int) $data['entity_id'],
            'action' => $data['action'],
            'changes' => json_encode($data['changes'] ?? []),
            'actor_id' => $data['actor_id'] ?? null,
            'created_at' => $data['created_at'] ?? $this->now(),
        ];
        $this->logs->insert($payload);
        $payload['id'] = (int) $this->logs->getInsertID();
        return $this->map($payload);
    }

    public function list(array $filters = []): array
    {
        $builder = $this->logs->builder();
        foreach (['company_id','entity_id'] as $field) {
            if (! empty($filters[$field])) {
                $builder->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['entity_type'])) {
            $builder->where('entity_type', $filters['entity_type']);
        }
        if (! empty($filters['action'])) {
            $builder->where('action', $filters['action']);
        }
        $builder->orderBy('id', 'DESC');
        $rows = $builder->limit(200)->get()->getResultArray();
        return array_map(fn ($row) => $this->map($row), $rows);
    }

    private function map(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['company_id'] = isset($row['company_id']) ? (int) $row['company_id'] : null;
        $row['entity_id'] = isset($row['entity_id']) ? (int) $row['entity_id'] : null;
        $row['changes'] = $this->decodeChanges($row['changes'] ?? null);
        return $row;
    }

    private function decodeChanges(?string $json): array
    {
        if (! $json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
