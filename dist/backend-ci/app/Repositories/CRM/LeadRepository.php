<?php

namespace App\Repositories\CRM;

use App\Models\LeadModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Leads
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class LeadRepository
{
    protected LeadModel $model;
    protected BaseConnection $db;

    public function __construct(?LeadModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new LeadModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $this->hydrate($payload);
    }

    public function findById(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function updateLead(int $id, array $data): bool
    {
        return (bool) $this->model->update($id, $data + ['updated_at' => $this->now()]);
    }

    public function list(array $filters = []): array
    {
        $builder = $this->model->builder();
        if (! empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        return array_map(fn ($row) => $this->hydrate($row), $builder->orderBy('id', 'DESC')->get()->getResultArray());
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
