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
            $q = $q->like('name_vi', $filters['search']);
        }
        
        if (isset($filters['status'])) {
            $q = $q->where('status', $filters['status']);
        }
        
        $limit = $filters['limit'] ?? 100;
        $offset = (($filters['page'] ?? 1) - 1) * $limit;
        
        $result = $q->orderBy('name_vi', 'ASC')->findAll($limit, $offset);
        
        // Add 'name' alias for frontend compatibility
        return array_map(function ($row) {
            $row['name'] = $row['name_vi'] ?? $row['name_en'] ?? '';
            return $row;
        }, $result ?: []);
    }

    /** Count customer groups. Model's soft delete handles filtering. */
    public function count(array $filters = []): int
    {
        $q = $this->model;
        
        if (!empty($filters['search'])) {
            $q = $q->like('name_vi', $filters['search']);
        }
        
        if (isset($filters['status'])) {
            $q = $q->where('status', $filters['status']);
        }
        
        return $q->countAllResults();
    }

    /** Find by ID. Model's soft delete handles filtering. */
    public function findById(int $id): ?array
    {
        $row = $this->model->find($id);
        if ($row) {
            $row['name'] = $row['name_vi'] ?? $row['name_en'] ?? '';
        }
        return $row;
    }

    /** Create customer group. Model handles timestamps automatically. */
    public function create(array $data): array
    {
        $payload = [
            'code' => $data['code'] ?? 'CG-' . strtoupper(substr(md5(time()), 0, 6)),
            'name_vi' => $data['name'] ?? $data['name_vi'] ?? '',
            'name_en' => $data['name_en'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];
        
        $this->model->insert($payload);
        $payload['id'] = $this->model->getInsertID();
        $payload['name'] = $payload['name_vi'];
        
        return $payload;
    }

    /** Update customer group. Model handles timestamps automatically. */
    public function update(int $id, array $data): bool
    {
        $payload = [];
        if (isset($data['name'])) $payload['name_vi'] = $data['name'];
        if (isset($data['name_vi'])) $payload['name_vi'] = $data['name_vi'];
        if (isset($data['name_en'])) $payload['name_en'] = $data['name_en'];
        if (isset($data['status'])) $payload['status'] = $data['status'];
        
        return (bool) $this->model->update($id, $payload);
    }

    /** Soft delete. */
    public function delete(int $id): bool
    {
        return (bool) $this->model->delete($id);
    }
}

