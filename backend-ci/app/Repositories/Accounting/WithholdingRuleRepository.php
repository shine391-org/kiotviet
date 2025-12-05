<?php

namespace App\Repositories\Accounting;

use App\Models\WithholdingRuleModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Withholding rules
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class WithholdingRuleRepository
{
    protected WithholdingRuleModel $model;
    protected BaseConnection $db;

    public function __construct(?WithholdingRuleModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->model = $model ?? new WithholdingRuleModel();
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

    public function allActive(): array
    {
        $rows = $this->model->where('status', 'active')->findAll();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['rate_percent'] = isset($row['rate_percent']) ? (float) $row['rate_percent'] : 0.0;
        $row['apply_threshold'] = isset($row['apply_threshold']) ? (float) $row['apply_threshold'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
