<?php

namespace App\Repositories\Quality;

use App\Models\QualityParameterModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Quality parameter persistence.
 *
 * @agent-repository: Quality parameters
 * @agent-pattern: Repository pattern
 * @agent-reusable: HIGH
 */
class QualityParameterRepository
{
    protected QualityParameterModel $model;
    protected BaseConnection $db;

    public function __construct(?QualityParameterModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->model = $model ?? new QualityParameterModel($this->db);
    }

    /** List parameters with simple filters. @agent-use: Parameter listing @agent-pattern: Filter + order */
    public function list(array $filters = []): array
    {
        return $this->applyFilters($filters)->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /** Count parameters with filters. */
    public function count(array $filters = []): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    /** Find parameter by id. */
    public function find(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ? (is_array($row) ? $row : (array) $row) : null;
    }

    /** Map parameters by id. @return array<int,array> */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $rows = $this->model->whereIn('id', $ids)->findAll();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = is_array($row) ? $row : (array) $row;
        }
        return $map;
    }

    /** Create new parameter. */
    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $payload;
    }

    /** Update parameter and return fresh row. */
    public function update(int $id, array $data): array
    {
        $payload = $data + ['updated_at' => $this->now()];
        $this->model->update($id, $payload);
        return $this->find($id) ?? [];
    }

    /** Hard delete parameter. */
    public function delete(int $id): void
    {
        $this->model->delete($id);
    }

    /** Check duplicate name. */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $b = $this->model->builder()->where('name', $name);
        if ($excludeId) {
            $b->where('id !=', $excludeId);
        }
        return $b->countAllResults() > 0;
    }

    /** Is parameter linked to any inspection items. */
    public function isUsed(int $parameterId): bool
    {
        return $this->db->table('quality_inspection_items')
            ->where('parameter_id', $parameterId)
            ->countAllResults() > 0;
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    private function applyFilters(array $filters)
    {
        $b = $this->model->builder();
        if (array_key_exists('is_active', $filters)) {
            $b->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ? 1 : 0);
        }
        if (! empty($filters['search'])) {
            $b->like('name', $filters['search']);
        }
        return $b;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
