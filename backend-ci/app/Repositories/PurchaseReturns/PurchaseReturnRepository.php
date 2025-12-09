<?php

namespace App\Repositories\PurchaseReturns;

use App\Models\PurchaseReturnModel;
use App\Models\PurchaseReturnItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Purchase returns (Trả hàng nhập)
 * @agent-pattern: Repository with items
 */
class PurchaseReturnRepository
{
    protected PurchaseReturnModel $returns;
    protected PurchaseReturnItemModel $items;
    protected BaseConnection $db;

    public function __construct(?PurchaseReturnModel $returns = null, ?PurchaseReturnItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->returns = $returns ?? new PurchaseReturnModel();
        $this->items = $items ?? new PurchaseReturnItemModel();
    }

    /**
     * List purchase returns with filters and pagination.
     */
    public function list(array $filters = []): array
    {
        $builder = $this->db->table('purchase_returns pr')
            ->select('pr.*, p.name as supplier_name, p.code as supplier_code, 
                      po.order_number as purchase_order_number,
                      uc.full_name as creator_name, ur.full_name as returner_name,
                      b.name as branch_name')
            ->join('partners p', 'p.id = pr.partner_id', 'left')
            ->join('purchase_orders po', 'po.id = pr.purchase_order_id', 'left')
            ->join('users uc', 'uc.id = pr.created_by', 'left')
            ->join('users ur', 'ur.id = pr.returned_by', 'left')
            ->join('branches b', 'b.id = pr.branch_id', 'left')
            ->where('pr.deleted_at IS NULL');

        // Apply filters
        if (!empty($filters['branch_id'])) {
            $builder->where('pr.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder->whereIn('pr.status', $filters['status']);
            } else {
                $builder->where('pr.status', $filters['status']);
            }
        }
        if (!empty($filters['supplier_id'])) {
            $builder->where('pr.partner_id', $filters['supplier_id']);
        }
        if (!empty($filters['created_by'])) {
            $builder->where('pr.created_by', $filters['created_by']);
        }
        if (!empty($filters['returned_by'])) {
            $builder->where('pr.returned_by', $filters['returned_by']);
        }
        if (!empty($filters['date_from'])) {
            $builder->where('pr.return_date >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $builder->where('pr.return_date <=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('pr.return_number', $search)
                ->orLike('p.name', $search)
                ->orLike('p.code', $search)
                ->groupEnd();
        }

        // Count total before pagination
        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        // Calculate totals
        $totalsBuilder = $this->db->table('purchase_returns pr')
            ->select('SUM(pr.total_amount) as total_amount, SUM(pr.discount) as total_discount, 
                      SUM(pr.ncc_can_tra) as total_ncc_can_tra, SUM(pr.ncc_da_tra) as total_ncc_da_tra')
            ->join('partners p', 'p.id = pr.partner_id', 'left')
            ->where('pr.deleted_at IS NULL');

        // Re-apply filters for totals
        if (!empty($filters['branch_id'])) {
            $totalsBuilder->where('pr.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $totalsBuilder->whereIn('pr.status', $filters['status']);
            } else {
                $totalsBuilder->where('pr.status', $filters['status']);
            }
        }
        if (!empty($filters['date_from'])) {
            $totalsBuilder->where('pr.return_date >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $totalsBuilder->where('pr.return_date <=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $totalsBuilder->groupStart()
                ->like('pr.return_number', $search)
                ->orLike('p.name', $search)
                ->orLike('p.code', $search)
                ->groupEnd();
        }
        $totalsResult = $totalsBuilder->get()->getRowArray();

        // Pagination
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 15)));
        $offset = ($page - 1) * $limit;

        $builder->orderBy('pr.created_at', 'DESC')
            ->limit($limit, $offset);

        $rows = $builder->get()->getResultArray();
        $data = array_map(fn($row) => $this->hydrateListRow($row), $rows);

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'total_pages' => (int)ceil($total / $limit),
            ],
            'totals' => [
                'total_amount' => (float)($totalsResult['total_amount'] ?? 0),
                'total_discount' => (float)($totalsResult['total_discount'] ?? 0),
                'total_ncc_can_tra' => (float)($totalsResult['total_ncc_can_tra'] ?? 0),
                'total_ncc_da_tra' => (float)($totalsResult['total_ncc_da_tra'] ?? 0),
            ],
        ];
    }

    private function hydrateListRow(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'return_number' => $row['return_number'] ?? '',
            'purchase_order_id' => isset($row['purchase_order_id']) ? (int)$row['purchase_order_id'] : null,
            'purchase_order_number' => $row['purchase_order_number'] ?? '',
            'return_date' => $row['return_date'] ?? $row['created_at'],
            'supplier_id' => isset($row['partner_id']) ? (int)$row['partner_id'] : null,
            'supplier_name' => $row['supplier_name'] ?? '',
            'supplier_code' => $row['supplier_code'] ?? '',
            'branch_id' => isset($row['branch_id']) ? (int)$row['branch_id'] : null,
            'branch_name' => $row['branch_name'] ?? '',
            'total_quantity' => (int)($row['total_quantity'] ?? 0),
            'total_amount' => (float)($row['total_amount'] ?? 0),
            'discount' => (float)($row['discount'] ?? 0),
            'ncc_can_tra' => (float)($row['ncc_can_tra'] ?? 0),
            'ncc_da_tra' => (float)($row['ncc_da_tra'] ?? 0),
            'status' => $row['status'] ?? 'draft',
            'created_by' => isset($row['created_by']) ? (int)$row['created_by'] : null,
            'creator_name' => $row['creator_name'] ?? '',
            'returned_by' => isset($row['returned_by']) ? (int)$row['returned_by'] : null,
            'returner_name' => $row['returner_name'] ?? '',
            'notes' => $row['notes'] ?? '',
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    public function findById(int $id): ?array
    {
        $return = $this->db->table('purchase_returns pr')
            ->select('pr.*, p.name as supplier_name, p.code as supplier_code,
                      po.order_number as purchase_order_number,
                      uc.full_name as creator_name, ur.full_name as returner_name,
                      b.name as branch_name')
            ->join('partners p', 'p.id = pr.partner_id', 'left')
            ->join('purchase_orders po', 'po.id = pr.purchase_order_id', 'left')
            ->join('users uc', 'uc.id = pr.created_by', 'left')
            ->join('users ur', 'ur.id = pr.returned_by', 'left')
            ->join('branches b', 'b.id = pr.branch_id', 'left')
            ->where('pr.id', $id)
            ->where('pr.deleted_at IS NULL')
            ->get()
            ->getRowArray();

        if (!$return) {
            return null;
        }

        // Get items with product info
        $items = $this->db->table('purchase_return_items pri')
            ->select('pri.*, pr.code as product_code, pr.name as product_name')
            ->join('products pr', 'pr.id = pri.product_id', 'left')
            ->where('pri.purchase_return_id', $id)
            ->get()
            ->getResultArray();

        $return = $this->hydrateListRow($return);
        $return['items'] = array_map(fn($i) => $this->hydrateItem($i), $items);
        return $return;
    }

    public function create(array $data, array $items): array
    {
        $now = $this->now();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['return_number'] = $data['return_number'] ?? $this->nextNumber();

        $this->db->transStart();
        $this->returns->insert($data);
        $returnId = (int)$this->returns->getInsertID();

        $itemRows = [];
        foreach ($items as $item) {
            $itemRows[] = $item + [
                'purchase_return_id' => $returnId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($itemRows) {
            $this->items->insertBatch($itemRows);
        }
        $this->db->transComplete();

        return $this->findById($returnId);
    }

    public function update(int $id, array $data, array $items = []): ?array
    {
        $now = $this->now();
        $data['updated_at'] = $now;

        $this->db->transStart();
        $this->returns->update($id, $data);

        if (!empty($items)) {
            // Delete existing items and insert new ones
            $this->items->where('purchase_return_id', $id)->delete();
            $itemRows = [];
            foreach ($items as $item) {
                $itemRows[] = $item + [
                    'purchase_return_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($itemRows) {
                $this->items->insertBatch($itemRows);
            }
        }
        $this->db->transComplete();

        return $this->findById($id);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool)$this->returns->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function delete(int $id): bool
    {
        return (bool)$this->returns->delete($id);
    }

    public function nextNumber(): string
    {
        $prefix = 'THN' . date('Ymd');
        $count = $this->returns->where('return_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrateItem(array $row): array
    {
        return [
            'id' => isset($row['id']) ? (int)$row['id'] : null,
            'purchase_return_id' => isset($row['purchase_return_id']) ? (int)$row['purchase_return_id'] : null,
            'product_id' => isset($row['product_id']) ? (int)$row['product_id'] : null,
            'variant_id' => isset($row['variant_id']) ? (int)$row['variant_id'] : null,
            'product_code' => $row['product_code'] ?? '',
            'product_name' => $row['product_name'] ?? '',
            'quantity' => (float)($row['quantity'] ?? 0),
            'import_price' => (float)($row['import_price'] ?? 0),
            'return_price' => (float)($row['return_price'] ?? 0),
            'discount_per_item' => (float)($row['discount_per_item'] ?? 0),
            'amount' => (float)($row['amount'] ?? 0),
            'notes' => $row['notes'] ?? '',
        ];
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
