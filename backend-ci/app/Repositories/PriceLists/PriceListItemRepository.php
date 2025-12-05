<?php

namespace App\Repositories\PriceLists;

use App\Models\PriceListItemModel;
use CodeIgniter\Database\BaseConnection;

/** Price list item persistence. @agent-repository: Price list items @agent-pattern: Repository pattern @agent-reusable: MEDIUM */
class PriceListItemRepository
{
    protected PriceListItemModel $items;
    protected BaseConnection $db;

    public function __construct(?PriceListItemModel $items = null, ?BaseConnection $db = null)
    {
        $group = ENVIRONMENT === 'testing' ? 'tests' : null;
        $this->db = $db ?? \Config\Database::connect($group);
        $this->items = $items ?? new PriceListItemModel($this->db);
    }

    /** Items for one price list. */
    public function itemsByPriceList(int $priceListId): array
    {
        return $this->items->where('price_list_id', $priceListId)->orderBy('product_id', 'ASC')->findAll();
    }

    /** Raw items (builder) used for recalculation. */
    public function itemsRaw(int $priceListId): array
    {
        return $this->db->table('price_list_items')->where('price_list_id', $priceListId)->get()->getResultArray();
    }

    /** Replace all items for a price list. */
    public function replaceItems(int $priceListId, array $items): array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        $this->db->table('price_list_items')->where('price_list_id', $priceListId)->delete();
        if ($items) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = $item + [
                    'price_list_id' => $priceListId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('price_list_items')->insertBatch($rows);
        }
        $this->db->transComplete();
        return ['deleted' => true, 'inserted' => count($items)];
    }

    /** Add items (Append). Overwrites existing prices for same product/variant. */
    public function addItems(int $priceListId, array $items): array
    {
        $now = date('Y-m-d H:i:s');
        if (empty($items)) { return ['inserted' => 0]; }

        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'price_list_id' => $priceListId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Use upsertBatch or manual loop with ON DUPLICATE KEY UPDATE logic
        // CI4 upsertBatch matches on primary/unique keys. 
        // Assuming (price_list_id, product_id, variant_id) is unique.
        // If not defined unique, simple insertBatch might duplicate or fail.
        // For now, let's assuming simple insertBatch due to Time constraints, or handle dupes in Service.
        // Better: Delete these specific items first, then insert.
        $this->db->transStart();
        
        // Clean up incoming items (to ensure overwrite)
        foreach ($items as $item) {
             $b = $this->db->table('price_list_items')
                ->where('price_list_id', $priceListId)
                ->where('product_id', $item['product_id']);
             
             if (array_key_exists('variant_id', $item)) {
                 $b->where('variant_id', $item['variant_id']);
             } else {
                 $b->where('variant_id', null);
             }
             $b->delete();
        }

        $this->db->table('price_list_items')->insertBatch($rows);
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
             log_message('error', 'Add items transaction failed');
             return ['inserted' => 0, 'error' => 'Transaction failed'];
        }

        return ['inserted' => count($rows)];
    }

    /** Get matching item for a product/variant. Prioritises variant-specific rows. */
    public function findItem(int $priceListId, int $productId, ?int $variantId): ?array
    {
        if ($variantId !== null) {
            $row = $this->items->where('price_list_id', $priceListId)->where('product_id', $productId)
                ->where('variant_id', $variantId)->first();
            if ($row) { return $row; }
        }

        $row = $this->items->where('price_list_id', $priceListId)->where('product_id', $productId)
            ->where('variant_id', null)->first();
        return $row ?: null;
    }

    /** Remove a product from a price list. When variantId is null, only removes the product-level item. */
    public function removeItem(int $priceListId, int $productId, ?int $variantId = null): bool
    {
        $builder = $this->db->table('price_list_items')
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId);
        
        if ($variantId !== null) {
            $builder->where('variant_id', $variantId);
        } else {
            // Only remove product-level item, not variant items
            $builder->where('variant_id', null);
        }
        
        $builder->delete();
        return $this->db->affectedRows() > 0;
    }
}
