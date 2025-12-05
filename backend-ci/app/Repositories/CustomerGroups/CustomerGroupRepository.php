<?php

namespace App\Repositories\CustomerGroups;

use App\Models\CustomerGroupModel;
use CodeIgniter\Database\BaseConnection;

/** Customer group persistence. @agent-repository: Customer groups @agent-pattern: Repository pattern */
class CustomerGroupRepository
{
    protected CustomerGroupModel $model;
    protected BaseConnection $db;

    public function __construct(?CustomerGroupModel $model = null, ?BaseConnection $db = null)
    {
        $group = ENVIRONMENT === 'testing' ? 'tests' : null;
        $this->db = $db ?? \Config\Database::connect($group);
        $this->model = $model ?? new CustomerGroupModel($this->db);
    }

    /** List all customer groups. Model's soft delete handles filtering. */
    public function findAll(array $filters = []): array
    {
        $q = $this->model;
        
        if (!empty($filters['search'])) {
            $q = $q->like('name', $filters['search']);
        }
        
        if (isset($filters['is_active'])) {
            $q = $q->where('is_active', $filters['is_active'] ? 1 : 0);
        }
        
        $limit = $filters['limit'] ?? 100;
        $offset = (($filters['page'] ?? 1) - 1) * $limit;
        
        $result = $q->orderBy('name', 'ASC')->findAll($limit, $offset);
            
        return $result ?: [];
    }

    /** Count customer groups. Model's soft delete handles filtering. */
    public function count(array $filters = []): int
    {
        $q = $this->model;
        
        if (!empty($filters['search'])) {
            $q = $q->like('name', $filters['search']);
        }
        
        if (isset($filters['is_active'])) {
            $q = $q->where('is_active', $filters['is_active'] ? 1 : 0);
        }
        
        return $q->countAllResults();
    }

    /** Find by ID. Model's soft delete handles filtering. */
    public function findById(int $id): ?array
    {
        return $this->model->find($id);
    }

    /** Create customer group. Model handles timestamps automatically. */
    public function create(array $data): array
    {
        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_percent' => $data['discount_percent'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
        ];
        
        $this->model->insert($payload);
        $payload['id'] = $this->model->getInsertID();
        
        return $payload;
    }

    /** Update customer group. Model handles timestamps automatically. */
    public function update(int $id, array $data): bool
    {
        return (bool) $this->model->update($id, $data);
    }

    /** Soft delete. */
    public function delete(int $id): bool
    {
        return (bool) $this->model->delete($id);
    }
}
