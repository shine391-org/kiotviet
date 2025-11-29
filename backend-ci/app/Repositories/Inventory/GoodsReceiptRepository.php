<?php

namespace App\Repositories\Inventory;

use App\Models\GoodsReceiptModel;
use App\Models\GoodsReceiptItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Goods receipts
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class GoodsReceiptRepository
{
    protected GoodsReceiptModel $receipts;
    protected GoodsReceiptItemModel $items;
    protected BaseConnection $db;

    public function __construct(?GoodsReceiptModel $receipts = null, ?GoodsReceiptItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->receipts = $receipts ?? new GoodsReceiptModel();
        $this->items = $items ?? new GoodsReceiptItemModel();
    }

    public function create(array $receipt, array $items): array
    {
        $now = $this->now();
        $receipt['created_at'] = $now;
        $receipt['updated_at'] = $now;
        $this->db->transStart();
        $this->receipts->insert($receipt);
        $id = (int) $this->receipts->getInsertID();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'goods_receipt_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->items->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->findById($id);
    }

    public function findById(int $id): ?array
    {
        $row = $this->receipts->find($id);
        if (! $row) {
            return null;
        }
        $items = $this->items->where('goods_receipt_id', $id)->findAll();
        $row = $this->hydrate($row);
        $row['items'] = array_map(fn ($i) => $this->hydrateItem($i), $items);
        return $row;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->receipts->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function nextNumber(): string
    {
        $prefix = 'GRN-' . date('Ymd');
        $count = $this->receipts->where('receipt_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['purchase_order_id'] = isset($row['purchase_order_id']) ? (int) $row['purchase_order_id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['goods_receipt_id'] = isset($row['goods_receipt_id']) ? (int) $row['goods_receipt_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['quantity'] = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
        $row['rate'] = isset($row['rate']) ? (float) $row['rate'] : 0.0;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
