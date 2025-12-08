<?php

namespace App\Repositories\Inventory;

use App\Models\StockDisposalModel;
use App\Models\StockDisposalItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Stock Disposal Repository.
 * 
 * @agent-repository: stock_disposals
 * @agent-pattern: Active Record with joins
 */
class StockDisposalRepository
{
    protected StockDisposalModel $model;
    protected StockDisposalItemModel $itemModel;
    protected BaseConnection $db;

    public function __construct(?StockDisposalModel $model = null, ?StockDisposalItemModel $itemModel = null)
    {
        $this->model = $model ?? new StockDisposalModel();
        $this->itemModel = $itemModel ?? new StockDisposalItemModel();
        $this->db = \Config\Database::connect();
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    /**
     * List disposals with pagination and filtering.
     */
    public function findAll(array $filters = []): array
    {
        $builder = $this->model->builder();
        $builder->select('stock_disposals.*, 
            b.name as branch_name,
            uc.full_name as creator_name,
            ue.full_name as executor_name')
            ->join('branches b', 'b.id = stock_disposals.branch_id', 'left')
            ->join('users uc', 'uc.id = stock_disposals.created_by', 'left')
            ->join('users ue', 'ue.id = stock_disposals.executor_id', 'left');

        $this->applyFilters($builder, $filters);
        $this->applySort($builder, $filters);

        $limit = (int) ($filters['limit'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);
        $offset = ($page - 1) * $limit;

        return $builder->limit($limit, $offset)->get()->getResultArray();
    }

    /**
     * Count disposals matching filters.
     */
    public function count(array $filters = []): int
    {
        $builder = $this->model->builder();
        $this->applyFilters($builder, $filters);
        return $builder->countAllResults();
    }

    /**
     * Find disposal by ID.
     */
    public function findById(int $id): ?array
    {
        $builder = $this->model->builder();
        $builder->select('stock_disposals.*, 
            b.name as branch_name,
            uc.full_name as creator_name,
            ue.full_name as executor_name')
            ->join('branches b', 'b.id = stock_disposals.branch_id', 'left')
            ->join('users uc', 'uc.id = stock_disposals.created_by', 'left')
            ->join('users ue', 'ue.id = stock_disposals.executor_id', 'left')
            ->where('stock_disposals.id', $id);
        
        return $builder->get()->getRowArray();
    }

    /**
     * Find disposal by code.
     */
    public function findByCode(string $code): ?array
    {
        $builder = $this->model->builder();
        $builder->select('stock_disposals.*, 
            b.name as branch_name,
            uc.full_name as creator_name,
            ue.full_name as executor_name')
            ->join('branches b', 'b.id = stock_disposals.branch_id', 'left')
            ->join('users uc', 'uc.id = stock_disposals.created_by', 'left')
            ->join('users ue', 'ue.id = stock_disposals.executor_id', 'left')
            ->where('stock_disposals.code', $code);
        
        return $builder->get()->getRowArray();
    }

    /**
     * Get items for a disposal.
     */
    public function findItems(int $disposalId): array
    {
        return $this->itemModel->where('disposal_id', $disposalId)->findAll();
    }

    /**
     * Create disposal with items.
     */
    public function create(array $data, array $items = []): array
    {
        $this->db->transBegin();
        try {
            $this->model->insert($data);
            $disposalId = $this->model->getInsertID();

            $totalQuantity = 0;
            $totalValue = 0;

            foreach ($items as $item) {
                $item['disposal_id'] = $disposalId;
                $item['disposal_value'] = ($item['quantity'] ?? 0) * ($item['cost_price'] ?? 0);
                $this->itemModel->insert($item);
                
                $totalQuantity += (float) ($item['quantity'] ?? 0);
                $totalValue += (float) $item['disposal_value'];
            }

            // Update totals
            $this->model->update($disposalId, [
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
            ]);

            $this->db->transCommit();
            return $this->findById($disposalId);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Update disposal.
     */
    public function update(int $id, array $data): array
    {
        $this->model->update($id, $data);
        return $this->findById($id);
    }

    /**
     * Update disposal items.
     */
    public function updateItems(int $disposalId, array $items): void
    {
        $this->db->transBegin();
        try {
            $this->itemModel->where('disposal_id', $disposalId)->delete();
            
            $totalQuantity = 0;
            $totalValue = 0;

            foreach ($items as $item) {
                $item['disposal_id'] = $disposalId;
                $item['disposal_value'] = ($item['quantity'] ?? 0) * ($item['cost_price'] ?? 0);
                $this->itemModel->insert($item);
                
                $totalQuantity += (float) ($item['quantity'] ?? 0);
                $totalValue += (float) $item['disposal_value'];
            }

            $this->model->update($disposalId, [
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
            ]);

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Delete disposal and items.
     */
    public function delete(int $id): bool
    {
        $this->db->transBegin();
        try {
            $this->itemModel->where('disposal_id', $id)->delete();
            $this->model->delete($id);
            $this->db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Generate next disposal code.
     */
    public function nextCode(): string
    {
        $year = date('y');
        $prefix = "XH{$year}";
        
        $last = $this->model->builder()
            ->like('code', $prefix, 'after')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($last) {
            $num = (int) substr($last['code'], strlen($prefix) + 1); // +1 for the dash
            return $prefix . '-' . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '-0001';
    }

    /**
     * Get summary totals.
     */
    public function getSummary(array $filters = []): array
    {
        $builder = $this->model->builder();
        $builder->select('
            COALESCE(SUM(total_quantity), 0) as total_quantity,
            COALESCE(SUM(total_value), 0) as total_value,
            COUNT(*) as count
        ');
        $this->applyFilters($builder, $filters);
        return $builder->get()->getRowArray() ?? [];
    }

    /**
     * Apply filters to query builder.
     */
    protected function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['search'])) {
            $builder->like('stock_disposals.code', $filters['search']);
        }

        if (!empty($filters['branch_id'])) {
            $builder->where('stock_disposals.branch_id', (int) $filters['branch_id']);
        }

        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $builder->whereIn('stock_disposals.status', $filters['statuses']);
        }

        if (!empty($filters['status']) && is_string($filters['status'])) {
            $builder->where('stock_disposals.status', $filters['status']);
        }

        if (!empty($filters['created_by'])) {
            $builder->where('stock_disposals.created_by', (int) $filters['created_by']);
        }

        if (!empty($filters['executor_id'])) {
            $builder->where('stock_disposals.executor_id', (int) $filters['executor_id']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('stock_disposals.disposed_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $dateTo = $filters['date_to'];
            if (strlen($dateTo) === 10) {
                $dateTo .= ' 23:59:59';
            }
            $builder->where('stock_disposals.disposed_at <=', $dateTo);
        }
    }

    /**
     * Apply sorting to query builder.
     */
    protected function applySort($builder, array $filters): void
    {
        $sort = $filters['sort'] ?? 'disposed_at,desc';
        [$field, $dir] = explode(',', $sort) + ['disposed_at', 'desc'];
        
        $allowedFields = ['code', 'disposed_at', 'created_at', 'status', 'total_quantity', 'total_value'];
        if (in_array($field, $allowedFields)) {
            $builder->orderBy("stock_disposals.{$field}", $dir);
        } else {
            $builder->orderBy('stock_disposals.disposed_at', 'DESC');
        }
    }
}
