<?php

namespace App\Repositories\Customers;

use App\Models\CustomerModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Customer persistence layer.
 *
 * @agent-repository: Customers
 * @agent-pattern: Repository pattern
 * @agent-reusable: HIGH
 */
class CustomerRepository
{
    protected CustomerModel $model;
    protected BaseConnection $db;

    public function __construct(?CustomerModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new CustomerModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * List customers with filters + pagination.
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
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
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
     * Find customer by id.
     */
    public function findById(int $id): ?array
    {
        $row = $this->model->where('deleted_at', null)->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Create customer row.
     */
    public function create(array $data): array
    {
        $payload = $this->encode($data) + [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $this->hydrate($payload);
    }

    /**
     * Update customer row.
     */
    public function update(int $id, array $data): bool
    {
        $payload = $this->encode($data) + ['updated_at' => $this->now()];
        return (bool) $this->model->update($id, $payload);
    }

    /**
     * Check if tax code exists within an organization (exclude optional id).
     */
    public function taxCodeExists(string $taxCode, ?int $excludeId = null, ?int $organizationId = null): bool
    {
        $builder = $this->model->builder()
            ->where('tax_code', $taxCode)
            ->where('deleted_at', null);

        if ($organizationId !== null) {
            $builder->where('organization_id', $organizationId);
        }

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Apply filters to builder.
     */
    private function applyFilters(array $filters)
    {
        $builder = $this->model->builder()->where('deleted_at', null);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('phone', $search)
                ->orLike('phone2', $search)
                ->orLike('email', $search)
                ->orLike('tax_code', $search)
                ->orLike('company_name', $search)
                ->orLike('invoice_company_name', $search)
                ->groupEnd();
        }

        if (! empty($filters['customer_type'])) {
            $builder->where('customer_type', $filters['customer_type']);
        }

        if (! empty($filters['gender'])) {
            $builder->where('gender', $filters['gender']);
        }

        if (! empty($filters['organization_id'])) {
            $builder->where('organization_id', $filters['organization_id']);
        }

        return $builder;
    }

    /**
     * Cast row to typed array.
     */
    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['organization_id'] = isset($row['organization_id']) ? (int) $row['organization_id'] : 1;
        $row['customer_group_id'] = isset($row['customer_group_id']) ? (int) $row['customer_group_id'] : null;
        return $row;
    }

    /**
     * Encode values before persistence.
     */
    private function encode(array $data): array
    {
        if (isset($data['customer_group_id']) && $data['customer_group_id'] === null) {
            unset($data['customer_group_id']);
        }
        return $data;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
