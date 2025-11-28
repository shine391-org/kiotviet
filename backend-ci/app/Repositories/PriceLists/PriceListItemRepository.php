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
}
