<?php

namespace App\Repositories\Inventory;

use App\Models\PackingSlipModel;
use App\Models\PackingSlipItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Packing slip persistence.
 *
 * @agent-repository: Packing slip
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class PackingSlipRepository
{
    protected PackingSlipModel $slips;
    protected PackingSlipItemModel $items;
    protected BaseConnection $db;

    public function __construct(?PackingSlipModel $slips = null, ?PackingSlipItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->slips = $slips ?? new PackingSlipModel();
        $this->items = $items ?? new PackingSlipItemModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $slip, array $items): array
    {
        $now = $this->now();
        $payload = $slip + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->slips->insert($payload);
        $id = (int) $this->slips->getInsertID();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'packing_slip_id' => $id,
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
        $row = $this->slips->find($id);
        if (! $row) {
            return null;
        }
        $items = $this->items->where('packing_slip_id', $id)->findAll();
        $row = $this->hydrate($row);
        $row['items'] = array_map([$this, 'hydrateItem'], $items);
        return $row;
    }

    public function nextNumber(): string
    {
        $prefix = 'PACK-' . date('Ymd');
        $count = $this->slips->where('packing_slip_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['pick_list_id'] = isset($row['pick_list_id']) ? (int) $row['pick_list_id'] : null;
        $row['stock_entry_id'] = isset($row['stock_entry_id']) ? (int) $row['stock_entry_id'] : null;
        $row['source_warehouse_id'] = isset($row['source_warehouse_id']) ? (int) $row['source_warehouse_id'] : null;
        $row['target_warehouse_id'] = isset($row['target_warehouse_id']) ? (int) $row['target_warehouse_id'] : null;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['packing_slip_id'] = isset($row['packing_slip_id']) ? (int) $row['packing_slip_id'] : null;
        $row['pick_list_item_id'] = isset($row['pick_list_item_id']) ? (int) $row['pick_list_item_id'] : null;
        $row['stock_entry_item_id'] = isset($row['stock_entry_item_id']) ? (int) $row['stock_entry_item_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['qty'] = isset($row['qty']) ? (float) $row['qty'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
