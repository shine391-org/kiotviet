<?php

namespace App\Repositories\PaymentMethods;

use App\Models\PaymentMethodModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Payment methods persistence layer.
 *
 * @agent-repository: Payment methods
 * @agent-pattern: Repository pattern
 * @agent-reusable: HIGH
 */
class PaymentMethodRepository
{
    protected PaymentMethodModel $model;
    protected BaseConnection $db;

    public function __construct(?PaymentMethodModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new PaymentMethodModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * List payment methods with filters + pagination.
     *
     * @agent-use: Service list
     * @agent-pattern: Query builder with search + pagination
     */
    public function findAll(array $filters): array
    {
        $builder = $this->applyFilters($filters);
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $rows = $builder
            ->orderBy('display_order', 'ASC')
            ->orderBy('code', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    /**
     * Count rows for pagination.
     */
    public function count(array $filters): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    /**
     * Get all active methods ordered for caching.
     */
    public function activeOrdered(): array
    {
        $rows = $this->model->builder()
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('display_order', 'ASC')
            ->orderBy('code', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    /**
     * Fetch one by id.
     */
    public function findById(int $id): ?array
    {
        $row = $this->model->where('deleted_at', null)->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Fetch by code.
     */
    public function findByCode(string $code): ?array
    {
        $row = $this->model->where('deleted_at', null)->where('code', $code)->first();
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Create row.
     */
    public function create(array $data): array
    {
        $payload = $this->encode($data) + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $this->hydrate($payload);
    }

    /**
     * Update row.
     */
    public function update(int $id, array $data): bool
    {
        $payload = $this->encode($data) + ['updated_at' => $this->now()];
        return (bool) $this->model->update($id, $payload);
    }

    /**
     * Soft delete row.
     */
    public function delete(int $id): bool
    {
        return (bool) $this->model->delete($id);
    }

    /**
     * Check if code exists (excluding id).
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $builder = $this->model->builder()
            ->where('code', $code)
            ->where('deleted_at', null);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    /**
     * Detect usage in orders (by code).
     */
    public function usedInOrders(string $code): bool
    {
        if (! $this->db->tableExists('orders')) {
            return false;
        }
        $fields = array_map('strtolower', $this->db->getFieldNames('orders'));
        if (! in_array('payment_method', $fields, true)) {
            return false;
        }
        $row = $this->db->table('orders')->select('id')->where('payment_method', $code)->get(1)->getRowArray();
        return ! empty($row);
    }

    private function applyFilters(array $filters)
    {
        $builder = $this->model->builder()->where('deleted_at', null);

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $builder->where('is_active', $filters['is_active'] ? 1 : 0);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('code', $search)
                ->orLike('name', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        return $builder;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['is_active'] = isset($row['is_active']) ? (bool) $row['is_active'] : false;
        $row['display_order'] = isset($row['display_order']) ? (int) $row['display_order'] : 0;

        if (isset($row['name_translations'])) {
            $row['name_translations'] = is_array($row['name_translations'])
                ? $row['name_translations']
                : (json_decode((string) $row['name_translations'], true) ?: null);
        }

        return $row;
    }

    private function encode(array $data): array
    {
        if (isset($data['is_active'])) {
            $data['is_active'] = $data['is_active'] ? 1 : 0;
        }
        if (isset($data['name_translations'])) {
            $data['name_translations'] = json_encode($data['name_translations']);
        }
        return $data;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
