<?php

namespace App\Repositories\PurchaseOrders;

use App\Models\PurchaseOrderModel;
use App\Models\PurchaseOrderItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Purchase orders
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class PurchaseOrderRepository
{
    protected PurchaseOrderModel $orders;
    protected PurchaseOrderItemModel $items;
    protected BaseConnection $db;

    public function __construct(?PurchaseOrderModel $orders = null, ?PurchaseOrderItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->orders = $orders ?? new PurchaseOrderModel();
        $this->items = $items ?? new PurchaseOrderItemModel();
    }

    /**
     * List purchase orders with filters and pagination.
     */
    public function list(array $filters = []): array
    {
        $builder = $this->db->table('purchase_orders po')
            ->select('po.*, p.name as supplier_name, p.code as supplier_code, u.full_name as creator_name, b.name as branch_name')
            ->join('partners p', 'p.id = po.partner_id', 'left')
            ->join('users u', 'u.id = po.created_by', 'left')
            ->join('branches b', 'b.id = po.branch_id', 'left');

        // Apply filters
        if (!empty($filters['branch_id'])) {
            $builder->where('po.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder->whereIn('po.status', $filters['status']);
            } else {
                $builder->where('po.status', $filters['status']);
            }
        }
        if (!empty($filters['supplier_id'])) {
            $builder->where('po.partner_id', $filters['supplier_id']);
        }
        if (!empty($filters['created_by'])) {
            $builder->where('po.created_by', $filters['created_by']);
        }
        if (!empty($filters['receiver_id'])) {
            $builder->where('po.user_id', $filters['receiver_id']);
        }
        if (!empty($filters['date_from'])) {
            $builder->where('po.order_date >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $builder->where('po.order_date <=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('po.order_number', $search)
                ->orLike('p.name', $search)
                ->orLike('p.code', $search)
                ->groupEnd();
        }

        // Count total before pagination
        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        // Calculate totals - reset select to avoid mixed columns
        $totalsBuilder = $this->db->table('purchase_orders po')
            ->select('SUM(po.total) as total_amount, SUM(po.paid_amount) as total_paid')
            ->join('partners p', 'p.id = po.partner_id', 'left')
            ->join('users u', 'u.id = po.created_by', 'left')
            ->join('branches b', 'b.id = po.branch_id', 'left');
        
        // Re-apply filters for totals
        if (!empty($filters['branch_id'])) {
            $totalsBuilder->where('po.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $totalsBuilder->whereIn('po.status', $filters['status']);
            } else {
                $totalsBuilder->where('po.status', $filters['status']);
            }
        }
        if (!empty($filters['supplier_id'])) {
            $totalsBuilder->where('po.partner_id', $filters['supplier_id']);
        }
        if (!empty($filters['date_from'])) {
            $totalsBuilder->where('po.order_date >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $totalsBuilder->where('po.order_date <=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $totalsBuilder->groupStart()
                ->like('po.order_number', $search)
                ->orLike('p.name', $search)
                ->orLike('p.code', $search)
                ->groupEnd();
        }
        $totalsResult = $totalsBuilder->get()->getRowArray();

        // Pagination
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 15)));
        $offset = ($page - 1) * $limit;

        $builder->orderBy('po.created_at', 'DESC')
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
                'total_paid' => (float)($totalsResult['total_paid'] ?? 0),
                'total_debt' => (float)(($totalsResult['total_amount'] ?? 0) - ($totalsResult['total_paid'] ?? 0)),
            ],
        ];
    }

    private function hydrateListRow(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'order_number' => $row['order_number'] ?? $row['po_number'] ?? '',
            'order_date' => $row['order_date'] ?? $row['created_at'],
            'supplier_id' => isset($row['partner_id']) ? (int)$row['partner_id'] : null,
            'supplier_name' => $row['supplier_name'] ?? '',
            'supplier_code' => $row['supplier_code'] ?? '',
            'branch_id' => isset($row['branch_id']) ? (int)$row['branch_id'] : null,
            'branch_name' => $row['branch_name'] ?? '',
            'total' => (float)($row['total'] ?? $row['total_amount'] ?? 0),
            'paid_amount' => (float)($row['paid_amount'] ?? 0),
            'debt' => (float)(($row['total'] ?? $row['total_amount'] ?? 0) - ($row['paid_amount'] ?? 0)),
            'status' => $row['status'] ?? 'draft',
            'payment_status' => $row['payment_status'] ?? 'unpaid',
            'created_by' => isset($row['created_by']) ? (int)$row['created_by'] : null,
            'creator_name' => $row['creator_name'] ?? '',
            'notes' => $row['notes'] ?? '',
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    public function create(array $order, array $items): array
    {
        $now = $this->now();
        $order['created_at'] = $now;
        $order['updated_at'] = $now;
        $this->db->transStart();
        $this->orders->insert($order);
        $orderId = (int) $this->orders->getInsertID();
        $itemRows = [];
        foreach ($items as $item) {
            $itemRows[] = $item + [
                'purchase_order_id' => $orderId,
                'received_quantity' => $item['received_quantity'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($itemRows) {
            $this->items->insertBatch($itemRows);
        }
        $this->db->transComplete();
        return $this->findById($orderId);
    }

    public function findById(int $id): ?array
    {
        $order = $this->db->table('purchase_orders po')
            ->select('po.*, p.name as supplier_name, p.code as supplier_code, 
                      u.full_name as creator_name, b.name as branch_name')
            ->join('partners p', 'p.id = po.partner_id', 'left')
            ->join('users u', 'u.id = po.created_by', 'left')
            ->join('branches b', 'b.id = po.branch_id', 'left')
            ->where('po.id', $id)
            ->get()
            ->getRowArray();
        
        if (! $order) {
            return null;
        }
        
        // Get items with product info
        $items = $this->db->table('purchase_order_items poi')
            ->select('poi.*, pr.code as product_code, pr.name as product_name, 
                      pr.selling_price as import_price')
            ->join('products pr', 'pr.id = poi.product_id', 'left')
            ->where('poi.purchase_order_id', $id)
            ->get()
            ->getResultArray();
        
        $order = $this->hydrate($order);
        $order['items'] = array_map(fn ($i) => $this->hydrateItem($i), $items);
        return $order;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->orders->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function updateReceivedQty(int $orderId, array $receivedMap): void
    {
        foreach ($receivedMap as $itemId => $qty) {
            $this->items->update($itemId, ['received_quantity' => $qty, 'updated_at' => $this->now()]);
        }
    }

    public function nextNumber(): string
    {
        $prefix = 'PO-' . date('Ymd');
        $count = $this->orders->where('po_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        $row['total'] = isset($row['total']) ? (float) $row['total'] : 0.0;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['purchase_order_id'] = isset($row['purchase_order_id']) ? (int) $row['purchase_order_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['quantity'] = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
        $row['received_quantity'] = isset($row['received_quantity']) ? (float) $row['received_quantity'] : 0.0;
        $row['rate'] = isset($row['rate']) ? (float) $row['rate'] : 0.0;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
