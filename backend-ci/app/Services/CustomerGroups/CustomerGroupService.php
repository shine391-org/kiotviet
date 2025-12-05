<?php

namespace App\Services\CustomerGroups;

use App\Repositories\CustomerGroups\CustomerGroupRepository;
use InvalidArgumentException;

/** Customer group business logic. @agent-service: Customer groups @agent-pattern: Service layer */
class CustomerGroupService
{
    protected CustomerGroupRepository $repo;

    public function __construct(?CustomerGroupRepository $repo = null)
    {
        $this->repo = $repo ?? new CustomerGroupRepository();
    }

    /** List customer groups. */
    public function list(array $filters = []): array
    {
        $data = $this->repo->findAll($filters);
        $total = $this->repo->count($filters);
        
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => (int) ($filters['page'] ?? 1),
                'limit' => (int) ($filters['limit'] ?? 100),
            ]
        ];
    }

    /** Get single customer group. */
    public function get(int $id): array
    {
        $group = $this->repo->findById($id);
        if (!$group) {
            throw new \RuntimeException('Customer group not found');
        }
        return ['success' => true, 'data' => $group];
    }

    /** Create customer group. */
    public function create(array $data): array
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Name is required');
        }
        
        $group = $this->repo->create($data);
        return ['success' => true, 'data' => $group];
    }

    /** Update customer group. */
    public function update(int $id, array $data): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Customer group not found');
        }
        
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'discount_percent' => $data['discount_percent'] ?? null,
            'is_active' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : null,
        ], fn ($v) => $v !== null);
        
        if (empty($updateData)) {
            throw new InvalidArgumentException('No fields to update');
        }
        
        $this->repo->update($id, $updateData);
        $updated = $this->repo->findById($id);
        
        return ['success' => true, 'data' => $updated];
    }

    /** Delete customer group. */
    public function delete(int $id): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Customer group not found');
        }
        
        $this->repo->delete($id);
        return ['success' => true, 'message' => 'Customer group deleted'];
    }
}
