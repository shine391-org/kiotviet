<?php

namespace App\Repositories\Accounting;

use App\Models\LandedCostVoucherModel;
use App\Models\LandedCostItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Landed cost vouchers
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class LandedCostRepository
{
    protected LandedCostVoucherModel $vouchers;
    protected LandedCostItemModel $items;
    protected BaseConnection $db;

    public function __construct(
        ?LandedCostVoucherModel $vouchers = null,
        ?LandedCostItemModel $items = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->vouchers = $vouchers ?? new LandedCostVoucherModel();
        $this->items = $items ?? new LandedCostItemModel();
    }

    public function create(array $voucher, array $items): array
    {
        $now = $this->now();
        $voucher['created_at'] = $now;
        $voucher['updated_at'] = $now;
        $this->db->transStart();
        $this->vouchers->insert($voucher);
        $id = (int) $this->vouchers->getInsertID();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'landed_cost_voucher_id' => $id,
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
        $voucher = $this->vouchers->find($id);
        if (! $voucher) {
            return null;
        }
        $items = $this->items->where('landed_cost_voucher_id', $id)->findAll();
        $voucher = $this->hydrate($voucher);
        $voucher['items'] = array_map(fn ($i) => $this->hydrateItem($i), $items);
        return $voucher;
    }

    public function nextNumber(): string
    {
        $prefix = 'LCV-' . date('Ymd');
        $count = $this->vouchers->where('voucher_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['goods_receipt_id'] = isset($row['goods_receipt_id']) ? (int) $row['goods_receipt_id'] : null;
        $row['total_cost'] = isset($row['total_cost']) ? (float) $row['total_cost'] : 0.0;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['landed_cost_voucher_id'] = isset($row['landed_cost_voucher_id']) ? (int) $row['landed_cost_voucher_id'] : null;
        $row['goods_receipt_item_id'] = isset($row['goods_receipt_item_id']) ? (int) $row['goods_receipt_item_id'] : null;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
