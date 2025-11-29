<?php

namespace App\Repositories\Manufacturing;

use App\Models\BOMItemModel;
use App\Models\BOMModel;
use CodeIgniter\Database\BaseConnection;

/**
 * BOM persistence.
 *
 * @agent-repository: BOM
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class BOMRepository
{
    protected BOMModel $boms;
    protected BOMItemModel $items;
    protected BaseConnection $db;

    public function __construct(?BOMModel $boms = null, ?BOMItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->boms = $boms ?? new BOMModel($this->db);
        $this->items = $items ?? new BOMItemModel($this->db);
    }

    /** List BOMs. */
    public function list(array $filters = []): array
    {
        return $this->applyFilters($filters)->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /** Count BOMs. */
    public function count(array $filters = []): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    /** Find BOM with items. */
    public function findWithItems(int $id): ?array
    {
        $bom = $this->boms->find($id);
        if (! $bom) {
            return null;
        }
        $items = $this->items->where('bom_id', $id)->orderBy('id', 'ASC')->findAll();
        $bom['items'] = $items;
        return is_array($bom) ? $bom : (array) $bom;
    }

    /** Create BOM with items. */
    public function create(array $bom, array $items): array
    {
        $now = $this->now();
        $payload = $bom + ['created_at' => $now, 'updated_at' => $now];

        $this->db->transStart();
        $this->boms->insert($payload);
        $bomId = (int) $this->boms->getInsertID();
        if (! empty($items)) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = $item + [
                    'bom_id' => $bomId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('bom_items')->insertBatch($rows);
        }
        $this->db->transComplete();

        return $this->findWithItems($bomId) ?? [];
    }

    /** Update BOM and optionally replace items. */
    public function update(int $id, array $bom, ?array $items = null): array
    {
        $payload = $bom + ['updated_at' => $this->now()];
        $this->db->transStart();
        if (! empty($payload)) {
            $this->boms->update($id, $payload);
        }
        if ($items !== null) {
            $this->db->table('bom_items')->where('bom_id', $id)->delete();
            if (! empty($items)) {
                $rows = [];
                $now = $this->now();
                foreach ($items as $item) {
                    $rows[] = $item + [
                        'bom_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                $this->db->table('bom_items')->insertBatch($rows);
            }
        }
        $this->db->transComplete();

        return $this->findWithItems($id) ?? [];
    }

    public function existsActiveForProduct(int $productId): bool
    {
        return $this->boms->builder()->where('product_id', $productId)->where('is_active', 1)->countAllResults() > 0;
    }

    private function applyFilters(array $filters)
    {
        $b = $this->boms->builder();
        if (! empty($filters['product_id'])) {
            $b->where('product_id', $filters['product_id']);
        }
        if (array_key_exists('is_active', $filters)) {
            $b->where('is_active', $filters['is_active'] ? 1 : 0);
        }
        if (! empty($filters['version'])) {
            $b->like('version', $filters['version']);
        }
        return $b;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
