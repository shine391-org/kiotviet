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
        $order = $this->orders->find($id);
        if (! $order) {
            return null;
        }
        $items = $this->items->where('purchase_order_id', $id)->findAll();
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
