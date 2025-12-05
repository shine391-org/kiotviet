<?php

namespace App\Repositories\Manufacturing;

use App\Models\WorkOrderModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Work order persistence.
 *
 * @agent-repository: Work orders
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class WorkOrderRepository
{
    protected WorkOrderModel $workOrders;
    protected BaseConnection $db;

    public function __construct(?WorkOrderModel $workOrders = null, ?BOMRepository $bomRepo = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->workOrders = $workOrders ?? new WorkOrderModel($this->db);
    }

    public function list(array $filters = []): array
    {
        return $this->applyFilters($filters)->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    public function count(array $filters = []): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    public function find(int $id): ?array
    {
        $row = $this->workOrders->find($id);
        return $row ? (is_array($row) ? $row : (array) $row) : null;
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->workOrders->insert($payload);
        $payload['id'] = (int) $this->workOrders->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): array
    {
        $payload = $data + ['updated_at' => $this->now()];
        $this->workOrders->update($id, $payload);
        return $this->find($id) ?? [];
    }

    private function applyFilters(array $filters)
    {
        $b = $this->workOrders->builder();
        if (! empty($filters['product_id'])) { $b->where('product_id', $filters['product_id']); }
        if (! empty($filters['status'])) { $b->where('status', $filters['status']); }
        if (! empty($filters['bom_id'])) { $b->where('bom_id', $filters['bom_id']); }
        return $b;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
