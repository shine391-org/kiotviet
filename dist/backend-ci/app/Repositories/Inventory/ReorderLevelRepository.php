<?php

namespace App\Repositories\Inventory;

use App\Models\ReorderLevelModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Reorder level persistence.
 *
 * @agent-repository: Reorder levels
 * @agent-pattern: Repository pattern + stock join
 * @agent-reusable: MEDIUM
 */
class ReorderLevelRepository
{
    protected ReorderLevelModel $levels;
    protected BaseConnection $db;

    public function __construct(?ReorderLevelModel $levels = null, ?BaseConnection $db = null)
    {
        $this->levels = $levels ?? new ReorderLevelModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function find(int $id): ?array
    {
        $row = $this->levels->where('id', $id)->where('deleted_at', null)->first();
        return $row ?: null;
    }

    public function findByKey(int $productId, ?int $variantId, int $branchId): ?array
    {
        $builder = $this->levels->where('product_id', $productId)->where('branch_id', $branchId)->where('deleted_at', null);
        if ($variantId === null) {
            $builder->where('variant_id', null);
        } else {
            $builder->where('variant_id', $variantId);
        }
        $row = $builder->first();
        return $row ?: null;
    }

    /**
     * List reorder levels with stock snapshot.
     * @agent-use: Reorder levels listing
     */
    public function list(array $filters = []): array
    {
        $builder = $this->baseStockBuilder($filters);
        return $builder->get()->getResultArray();
    }

    public function create(array $data): array
    {
        $payload = $data + [
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->levels->insert($payload);
        $payload['id'] = (int) $this->levels->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): array
    {
        $payload = $data + ['updated_at' => date('Y-m-d H:i:s')];
        $this->levels->update($id, $payload);
        return $this->find($id) ?? [];
    }

    public function delete(int $id): bool
    {
        return (bool) $this->levels->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Find shortages (available <= min + safety).
     * @agent-use: Generate purchase suggestion inputs
     */
    public function findShortages(array $filters = []): array
    {
        $builder = $this->baseStockBuilder($filters);
        $builder->having('(COALESCE(SUM(b.on_hand_qty),0) - COALESCE(SUM(b.reserved_qty),0)) <= (rl.min_level + rl.safety_stock)');
        return $builder->get()->getResultArray();
    }

    private function baseStockBuilder(array $filters)
    {
        $builder = $this->db->table('reorder_levels rl');
        $builder->select("rl.*, COALESCE(SUM(b.on_hand_qty),0) AS on_hand_qty, COALESCE(SUM(b.reserved_qty),0) AS reserved_qty, (COALESCE(SUM(b.on_hand_qty),0) - COALESCE(SUM(b.reserved_qty),0)) AS available_qty", false);
        $builder->join(
            'stock_bins b',
            'b.product_id = rl.product_id AND b.branch_id = rl.branch_id AND (b.variant_id = rl.variant_id OR (b.variant_id IS NULL AND rl.variant_id IS NULL))',
            'left'
        );
        $builder->where('rl.deleted_at', null);
        if (array_key_exists('is_active', $filters)) {
            $builder->where('rl.is_active', $filters['is_active'] ? 1 : 0);
        } else {
            $builder->where('rl.is_active', 1);
        }
        if (! empty($filters['branch_id'])) {
            $builder->where('rl.branch_id', (int) $filters['branch_id']);
        }
        if (! empty($filters['product_id'])) {
            $builder->where('rl.product_id', (int) $filters['product_id']);
        }
        if (array_key_exists('variant_id', $filters) && $filters['variant_id'] !== null && $filters['variant_id'] !== '') {
            $builder->where('rl.variant_id', (int) $filters['variant_id']);
        } elseif (array_key_exists('variant_id', $filters) && $filters['variant_id'] === null) {
            $builder->where('rl.variant_id', null);
        }
        $builder->groupBy('rl.id');
        $builder->orderBy('rl.branch_id', 'ASC')->orderBy('rl.product_id', 'ASC');
        return $builder;
    }
}
