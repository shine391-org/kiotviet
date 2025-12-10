<?php

namespace App\Repositories\Inventory;

use App\Models\StockTransferModel;
use App\Models\StockTransferItemModel;
use CodeIgniter\Database\BaseConnection;

class StockTransferRepository
{
    protected StockTransferModel $model;
    protected StockTransferItemModel $itemModel;
    protected BaseConnection $db;

    public function __construct(?StockTransferModel $model = null, ?StockTransferItemModel $itemModel = null)
    {
        $this->model = $model ?? new StockTransferModel();
        $this->itemModel = $itemModel ?? new StockTransferItemModel();
        $this->db = \Config\Database::connect();
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    public function findAll(array $filters = []): array
    {
        $builder = $this->model->builder();
        $builder->select('stock_transfers.*, 
            fb.name as from_branch_name, 
            tb.name as to_branch_name,
            uc.full_name as creator_name,
            ur.full_name as receiver_name')
            ->join('branches fb', 'fb.id = stock_transfers.from_branch_id', 'left')
            ->join('branches tb', 'tb.id = stock_transfers.to_branch_id', 'left')
            ->join('users uc', 'uc.id = stock_transfers.created_by', 'left')
            ->join('users ur', 'ur.id = stock_transfers.received_by', 'left');

        $this->applyFilters($builder, $filters);
        $this->applySort($builder, $filters);

        $limit = (int) ($filters['limit'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);
        $offset = ($page - 1) * $limit;

        return $builder->limit($limit, $offset)->get()->getResultArray();
    }

    public function count(array $filters = []): int
    {
        $builder = $this->model->builder();
        $this->applyFilters($builder, $filters);
        return $builder->countAllResults();
    }

    public function findById(int $id): ?array
    {
        return $this->model->find($id);
    }

    public function findByCode(string $code): ?array
    {
        $builder = $this->model->builder();
        $builder->select('stock_transfers.*, 
            fb.name as from_branch_name, 
            tb.name as to_branch_name,
            uc.full_name as creator_name,
            ur.full_name as receiver_name')
            ->join('branches fb', 'fb.id = stock_transfers.from_branch_id', 'left')
            ->join('branches tb', 'tb.id = stock_transfers.to_branch_id', 'left')
            ->join('users uc', 'uc.id = stock_transfers.created_by', 'left')
            ->join('users ur', 'ur.id = stock_transfers.received_by', 'left')
            ->where('stock_transfers.code', $code);
        
        return $builder->get()->getRowArray();
    }

    public function findItems(int $transferId): array
    {
        return $this->itemModel->where('transfer_id', $transferId)->findAll();
    }

    public function create(array $data, array $items = []): array
    {
        $this->db->transBegin();
        try {
            $this->model->insert($data);
            $transferId = $this->model->getInsertID();

            foreach ($items as $item) {
                $item['transfer_id'] = $transferId;
                $this->itemModel->insert($item);
            }

            $this->db->transCommit();
            return $this->findById($transferId);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function update(int $id, array $data): array
    {
        $this->model->update($id, $data);
        return $this->findById($id);
    }

    public function updateItems(int $transferId, array $items): void
    {
        $this->db->transBegin();
        try {
            $this->itemModel->where('transfer_id', $transferId)->delete();
            foreach ($items as $item) {
                $item['transfer_id'] = $transferId;
                $this->itemModel->insert($item);
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $this->db->transBegin();
        try {
            $this->itemModel->where('transfer_id', $id)->delete();
            $this->model->delete($id);
            $this->db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function nextCode(): string
    {
        $year = date('y');
        $prefix = "TRF{$year}";
        
        $this->db->transBegin();
        try {
            // Use FOR UPDATE lock to prevent race conditions
            $sql = "SELECT code FROM {$this->model->table} WHERE code LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE";
            $last = $this->db->query($sql, [$prefix . '%'])->getRowArray();

            if ($last) {
                $num = (int) substr($last['code'], strlen($prefix));
                $code = $prefix . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $code = $prefix . '0001';
            }
            
            $this->db->transCommit();
            return $code;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function getSummary(array $filters = []): array
    {
        $builder = $this->model->builder();
        $builder->select('
            COALESCE(SUM(quantity_sent), 0) as totalQtySent,
            COALESCE(SUM(value_sent), 0) as totalValueSent,
            COALESCE(SUM(quantity_received), 0) as totalQtyReceived,
            COALESCE(SUM(value_received), 0) as totalValueReceived,
            COALESCE(SUM(total_items), 0) as totalItems
        ');
        $this->applyFilters($builder, $filters);
        return $builder->get()->getRowArray() ?? [];
    }

    protected function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['search'])) {
            $builder->like('stock_transfers.code', $filters['search']);
        }

        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $builder->whereIn('stock_transfers.status', $filters['statuses']);
        }

        if (!empty($filters['fromBranches']) && is_array($filters['fromBranches'])) {
            $branchIds = array_map('intval', $filters['fromBranches']);
            $builder->whereIn('stock_transfers.from_branch_id', $branchIds);
        }

        if (!empty($filters['toBranches']) && is_array($filters['toBranches'])) {
            $branchIds = array_map('intval', $filters['toBranches']);
            $builder->whereIn('stock_transfers.to_branch_id', $branchIds);
        }

        if (!empty($filters['transferDateEnabled'])) {
            $this->applyDateFilter($builder, 'stock_transfers.transfer_date', $filters);
        }

        if (!empty($filters['receiveDateEnabled'])) {
            $this->applyDateFilter($builder, 'stock_transfers.receive_date', $filters);
        }
    }

    protected function applyDateFilter($builder, string $field, array $filters): void
    {
        $mode = $filters['dateMode'] ?? 'this_year';
        if ($mode === 'this_year') {
            $builder->where("YEAR({$field})", date('Y'));
        } elseif (!empty($filters['customFrom'])) {
            $builder->where("{$field} >=", $filters['customFrom']);
            if (!empty($filters['customTo'])) {
                $builder->where("{$field} <=", $filters['customTo'] . ' 23:59:59');
            }
        }
    }

    protected function applySort($builder, array $filters): void
    {
        $sort = $filters['sort'] ?? 'transfer_date,desc';
        $parts = explode(',', $sort, 2);
        $field = $parts[0] ?? 'transfer_date';
        $dir = strtolower($parts[1] ?? 'desc');
        
        // Validate direction
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'desc';
        }
        
        // Map camelCase to snake_case
        $fieldMap = [
            'transferDate' => 'transfer_date',
            'receiveDate' => 'receive_date',
            'createdAt' => 'created_at',
            'quantitySent' => 'quantity_sent',
            'valueSent' => 'value_sent',
        ];
        $field = $fieldMap[$field] ?? $field;
        
        $allowedFields = ['code', 'transfer_date', 'receive_date', 'created_at', 'status', 'quantity_sent', 'value_sent'];
        if (in_array($field, $allowedFields, true)) {
            $builder->orderBy("stock_transfers.{$field}", $dir);
        } else {
            $builder->orderBy('stock_transfers.transfer_date', 'DESC');
        }
    }
}
