<?php

namespace App\Repositories\Inventory;

use App\Models\StockEntryModel;
use App\Models\StockEntryItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Stock entry persistence.
 *
 * @agent-repository: Stock entry
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class StockEntryRepository
{
    protected StockEntryModel $entries;
    protected StockEntryItemModel $items;
    protected BaseConnection $db;

    public function __construct(?StockEntryModel $entries = null, ?StockEntryItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->entries = $entries ?? new StockEntryModel();
        $this->items = $items ?? new StockEntryItemModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    public function create(array $entry, array $items): array
    {
        $now = $this->now();
        $payload = $entry + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->entries->insert($payload);
        $id = (int) $this->entries->getInsertID();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'stock_entry_id' => $id,
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
        $row = $this->entries->find($id);
        if (! $row) {
            return null;
        }
        $items = $this->items->where('stock_entry_id', $id)->findAll();
        $row = $this->hydrateEntry($row);
        $row['items'] = array_map([$this, 'hydrateItem'], $items);
        return $row;
    }

    public function updateStatus(int $id, string $status, array $extra = []): array
    {
        $payload = $extra + ['status' => $status, 'updated_at' => $this->now()];
        $this->entries->update($id, $payload);
        return $this->findById($id) ?? [];
    }

    public function nextNumber(string $type): string
    {
        $prefix = 'SE-' . strtoupper($type) . '-' . date('Ymd');
        $count = $this->entries->where('entry_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrateEntry(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        $row['source_warehouse_id'] = isset($row['source_warehouse_id']) ? (int) $row['source_warehouse_id'] : null;
        $row['target_warehouse_id'] = isset($row['target_warehouse_id']) ? (int) $row['target_warehouse_id'] : null;
        $row['reference_id'] = isset($row['reference_id']) ? (int) $row['reference_id'] : null;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['stock_entry_id'] = isset($row['stock_entry_id']) ? (int) $row['stock_entry_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['variant_id'] = isset($row['variant_id']) ? (int) $row['variant_id'] : null;
        $row['qty'] = isset($row['qty']) ? (float) $row['qty'] : 0.0;
        $row['source_warehouse_id'] = isset($row['source_warehouse_id']) ? (int) $row['source_warehouse_id'] : null;
        $row['target_warehouse_id'] = isset($row['target_warehouse_id']) ? (int) $row['target_warehouse_id'] : null;
        $row['source_branch_id'] = isset($row['source_branch_id']) ? (int) $row['source_branch_id'] : null;
        $row['target_branch_id'] = isset($row['target_branch_id']) ? (int) $row['target_branch_id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
