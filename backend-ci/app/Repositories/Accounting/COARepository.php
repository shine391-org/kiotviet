<?php

namespace App\Repositories\Accounting;

use App\Models\ChartOfAccountModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Chart of accounts
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class COARepository
{
    protected ChartOfAccountModel $model;
    protected BaseConnection $db;

    public function __construct(?ChartOfAccountModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->model = $model ?? new ChartOfAccountModel();
    }

    public function create(array $data): array
    {
        $now = $this->now();
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $this->hydrate($payload);
    }

    public function findById(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function findByCode(string $code): ?array
    {
        $row = $this->model->where('code', $code)->first();
        return $row ? $this->hydrate($row) : null;
    }

    public function listChildren(?int $parentId = null): array
    {
        $builder = $this->model->where('parent_id', $parentId);
        $rows = $parentId === null ? $this->model->where('parent_id', null)->findAll() : $builder->findAll();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    public function codeExists(string $code): bool
    {
        return (bool) $this->model->where('code', $code)->first();
    }

    public function markGroup(int $id): bool
    {
        return (bool) $this->model->update($id, ['is_group' => 1, 'updated_at' => $this->now()]);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['parent_id'] = array_key_exists('parent_id', $row) && $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
        $row['is_group'] = ! empty($row['is_group']);
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
