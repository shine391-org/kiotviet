<?php

namespace App\Repositories\Inventory;

use App\Models\PickListModel;
use App\Models\PickListItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Pick list persistence.
 *
 * @agent-repository: Pick list
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class PickListRepository
{
    protected PickListModel $pickLists;
    protected PickListItemModel $items;
    protected BaseConnection $db;

    public function __construct(?PickListModel $pickLists = null, ?PickListItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->pickLists = $pickLists ?? new PickListModel();
        $this->items = $items ?? new PickListItemModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $pickList, array $items): array
    {
        $now = $this->now();
        $payload = $pickList + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->pickLists->insert($payload);
        $id = (int) $this->pickLists->getInsertID();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'pick_list_id' => $id,
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
        $row = $this->pickLists->find($id);
        if (! $row) {
            return null;
        }
        $items = $this->items->where('pick_list_id', $id)->findAll();
        $row = $this->hydrate($row);
        $row['items'] = array_map([$this, 'hydrateItem'], $items);
        return $row;
    }

    public function nextNumber(): string
    {
        $prefix = 'PICK-' . date('Ymd');
        $count = $this->pickLists->where('pick_list_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['stock_entry_id'] = isset($row['stock_entry_id']) ? (int) $row['stock_entry_id'] : null;
        $row['source_warehouse_id'] = isset($row['source_warehouse_id']) ? (int) $row['source_warehouse_id'] : null;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['pick_list_id'] = isset($row['pick_list_id']) ? (int) $row['pick_list_id'] : null;
        $row['stock_entry_item_id'] = isset($row['stock_entry_item_id']) ? (int) $row['stock_entry_item_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['qty'] = isset($row['qty']) ? (float) $row['qty'] : 0.0;
        $row['source_warehouse_id'] = isset($row['source_warehouse_id']) ? (int) $row['source_warehouse_id'] : null;
        $row['target_warehouse_id'] = isset($row['target_warehouse_id']) ? (int) $row['target_warehouse_id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
