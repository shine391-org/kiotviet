<?php

namespace App\Repositories\DeliveryPartners;

use App\Models\PartnerModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Delivery Partner Repository.
 * Uses partners table with type='vendor'
 *
 * @agent-repository: DeliveryPartners
 */
class DeliveryPartnerRepository
{
    protected PartnerModel $model;
    protected BaseConnection $db;

    public function __construct(?PartnerModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new PartnerModel();
        $this->db = $db ?? Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    /** Find all delivery partners with filters. */
    public function findAll(array $filters): array
    {
        $builder = $this->applyFilters($filters);

        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        // Whitelist sortBy columns to prevent SQL injection
        $allowedSortColumns = ['id', 'name', 'created_at', 'updated_at', 'status', 'code', 'phone', 'email'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSortColumns, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return $builder
            ->orderBy($sortBy, $sortOrder)
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /** Count delivery partners with filters. */
    public function count(array $filters): int
    {
        $builder = $this->applyFilters($filters);
        return $builder->countAllResults(false);
    }

    /** Get summary totals. */
    public function summary(array $filters): array
    {
        $builder = $this->applyFilters($filters);
        $result = $builder
            ->select('COUNT(*) as total_partners')
            ->select('COALESCE(SUM(debt_amount), 0) as total_debt')
            ->select('COALESCE(SUM(total_purchased), 0) as total_orders')
            ->get()
            ->getRowArray();

        return [
            'total_partners' => (int) ($result['total_partners'] ?? 0),
            'total_debt' => (float) ($result['total_debt'] ?? 0),
            'total_orders' => (float) ($result['total_orders'] ?? 0),
        ];
    }

    /** Find by ID. */
    public function findById(int $id): ?array
    {
        return $this->db->table('partners')
            ->where('id', $id)
            ->where('type', 'vendor')
            ->like('code', 'DT', 'after')
            ->where('deleted_at IS NULL')
            ->get()
            ->getRowArray();
    }

    /** Check if code exists. */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $builder = $this->db->table('partners')
            ->where('code', $code)
            ->where('deleted_at IS NULL');

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /** Create new delivery partner. */
    public function create(array $data): array
    {
        $now = date('Y-m-d H:i:s');

        // Generate code if not provided
        $code = $data['code'] ?? $this->nextCode();

        $insertData = [
            'code' => $code,
            'name' => $data['name'],
            'type' => 'vendor', // Use 'vendor' for delivery partners (enum constraint)
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'status' => $data['status'] ?? 'active',
            'debt_amount' => 0,
            'total_purchased' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->table('partners')->insert($insertData);
        $id = $this->db->insertID();

        return $this->findById($id);
    }

    /** Update delivery partner. */
    public function update(int $id, array $data): array
    {
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        $allowedFields = ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'city', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $this->db->table('partners')
            ->where('id', $id)
            ->update($updateData);

        return $this->findById($id);
    }

    /** Soft delete delivery partner. */
    public function delete(int $id): void
    {
        $this->db->table('partners')
            ->where('id', $id)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /** Generate next code. */
    public function nextCode(): string
    {
        $result = $this->db->table('partners')
            ->select('MAX(CAST(SUBSTRING(code, 3) AS UNSIGNED)) as max_num')
            ->where('type', 'vendor')
            ->like('code', 'DT', 'after')
            ->get()
            ->getRowArray();

        $nextNum = ((int) ($result['max_num'] ?? 0)) + 1;
        return 'DT' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }

    private function applyFilters(array $filters): \CodeIgniter\Database\BaseBuilder
    {
        // Use 'vendor' type and code starting with 'DT' for delivery partners
        $builder = $this->db->table('partners')
            ->where('type', 'vendor')
            ->like('code', 'DT', 'after')
            ->where('deleted_at IS NULL');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('code', $search)
                ->orLike('name', $search)
                ->orLike('phone', $search)
                ->groupEnd();
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $builder->where('status', $filters['status']);
        }

        if (! empty($filters['group_name']) && $filters['group_name'] !== 'all') {
            $builder->where('group_name', $filters['group_name']);
        }

        return $builder;
    }
}
