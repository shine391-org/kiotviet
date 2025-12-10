<?php

namespace App\Repositories\Partners;

use App\Models\PartnerModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Partner persistence layer - for suppliers/vendors.
 *
 * @agent-repository: Partners
 * @agent-pattern: Repository pattern
 */
class PartnerRepository
{
    protected PartnerModel $model;
    protected BaseConnection $db;

    public function __construct(?PartnerModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new PartnerModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * List partners with filters + pagination.
     */
    public function findAll(array $filters): array
    {
        $builder = $this->applyFilters($filters);
        $limit = $filters['limit'] ?? 15;
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
     * Calculate summary totals.
     */
    public function getSummary(array $filters): array
    {
        $builder = $this->applyFilters($filters);
        $result = $builder
            ->selectSum('debt_amount', 'total_debt')
            ->selectSum('total_purchased', 'total_purchase')
            ->get()
            ->getRowArray();

        return [
            'total_debt' => (float) ($result['total_debt'] ?? 0),
            'total_purchase' => (float) ($result['total_purchase'] ?? 0),
        ];
    }

    /**
     * Find partner by id.
     */
    public function findById(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Create partner row.
     * Uses transaction to prevent race condition when auto-generating code.
     */
    public function create(array $data): array
    {
        $payload = $this->encode($data) + [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        // Wrap code generation and insert in single transaction to prevent race condition
        $this->db->transStart();

        // Auto-generate code if not provided
        if (empty($payload['code'])) {
            $payload['code'] = $this->generateCode($payload['type'] ?? 'supplier');
        }

        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Failed to create partner');
        }

        return $this->hydrate($payload);
    }

    /**
     * Update partner row.
     */
    public function update(int $id, array $data): bool
    {
        $payload = $this->encode($data) + ['updated_at' => $this->now()];
        return (bool) $this->model->update($id, $payload);
    }

    /**
     * Delete partner row.
     */
    public function delete(int $id): bool
    {
        return (bool) $this->model->delete($id);
    }

    /**
     * Find partner by code.
     */
    public function findByCode(string $code): ?array
    {
        $row = $this->model->where('code', $code)->first();
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Generate auto-incrementing code.
     * Must be called within a transaction (from create()) to prevent race condition.
     */
    private function generateCode(string $type = 'supplier'): string
    {
        $prefix = $type === 'supplier' ? 'NCC' : 'VND';
        
        // FOR UPDATE lock ensures no concurrent reads until transaction commits
        $lastRow = $this->db->query(
            "SELECT code FROM partners WHERE type = ? ORDER BY id DESC LIMIT 1 FOR UPDATE",
            [$type]
        )->getRowArray();

        $lastNum = 0;
        if ($lastRow && preg_match('/(\d+)$/', $lastRow['code'], $matches)) {
            $lastNum = (int) $matches[1];
        }

        return $prefix . str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Apply filters to builder.
     */
    private function applyFilters(array $filters)
    {
        $builder = $this->model->builder();

        // Default to suppliers
        $type = $filters['type'] ?? 'supplier';
        $builder->where('type', $type);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('code', $search)
                ->orLike('phone', $search)
                ->orLike('email', $search)
                ->groupEnd();
        }

        if (! empty($filters['status'])) {
            $builder->where('status', strtolower($filters['status']));
        }

        if (! empty($filters['group_name'])) {
            $builder->where('group_name', $filters['group_name']);
        }

        if (isset($filters['debt_from']) && $filters['debt_from'] !== null) {
            $builder->where('debt_amount >=', $filters['debt_from']);
        }
        if (isset($filters['debt_to']) && $filters['debt_to'] !== null) {
            $builder->where('debt_amount <=', $filters['debt_to']);
        }

        if (isset($filters['total_from']) && $filters['total_from'] !== null) {
            $builder->where('total_purchased >=', $filters['total_from']);
        }
        if (isset($filters['total_to']) && $filters['total_to'] !== null) {
            $builder->where('total_purchased <=', $filters['total_to']);
        }

        if (! empty($filters['created_from'])) {
            $builder->where('created_at >=', $filters['created_from'] . ' 00:00:00');
        }
        if (! empty($filters['created_to'])) {
            $builder->where('created_at <=', $filters['created_to'] . ' 23:59:59');
        }

        return $builder;
    }

    /**
     * Cast row to typed array.
     */
    private function hydrate(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'code' => $row['code'] ?? '',
            'name' => $row['name'] ?? '',
            'type' => $row['type'] ?? 'supplier',
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'province' => $row['province'] ?? null,
            'district' => $row['district'] ?? null,
            'ward' => $row['ward'] ?? null,
            'group' => $row['group_name'] ?? null,
            'company' => $row['company_name'] ?? null,
            'tax_code' => $row['tax_code'] ?? null,
            'note' => $row['note'] ?? null,
            'contact_person' => $row['contact_person'] ?? null,
            'current_debt' => (float) ($row['debt_amount'] ?? 0),
            'total_purchase' => (float) ($row['total_purchased'] ?? 0),
            'status' => strtoupper($row['status'] ?? 'active'),
            'creator' => $row['created_by'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    /**
     * Encode values before persistence.
     */
    private function encode(array $data): array
    {
        $encoded = [];

        $map = [
            'name' => 'name',
            'code' => 'code',
            'type' => 'type',
            'phone' => 'phone',
            'email' => 'email',
            'address' => 'address',
            'city' => 'city',
            'province' => 'province',
            'district' => 'district',
            'ward' => 'ward',
            'group' => 'group_name',
            'company' => 'company_name',
            'tax_code' => 'tax_code',
            'note' => 'note',
            'contact_person' => 'contact_person',
            'status' => 'status',
            'created_by' => 'created_by',
        ];

        foreach ($map as $key => $dbField) {
            if (isset($data[$key])) {
                $encoded[$dbField] = $data[$key];
            }
        }

        // Normalize status to lowercase
        if (isset($encoded['status'])) {
            $encoded['status'] = strtolower($encoded['status']);
        }

        return $encoded;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
