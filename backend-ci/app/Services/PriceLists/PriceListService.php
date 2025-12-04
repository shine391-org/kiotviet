<?php

namespace App\Services\PriceLists;

use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\Products\ProductRepository;
use App\Validators\PriceListValidator;
use InvalidArgumentException;
use RuntimeException;

/** Price list business logic. @agent-service: Price lists @agent-pattern: Service orchestrator @agent-reusable: HIGH */
class PriceListService
{
    protected PriceListRepository $repo;
    protected PriceListItemRepository $items;
    protected PriceListValidator $validator;
    protected PriceFormulaService $formula;
    protected ProductRepository $products;

    public function __construct(
        ?PriceListRepository $repo = null,
        ?PriceListItemRepository $items = null,
        ?PriceListValidator $validator = null,
        ?PriceFormulaService $formula = null,
        ?ProductRepository $products = null
    ) {
        $this->repo = $repo ?? new PriceListRepository();
        $this->items = $items ?? new PriceListItemRepository();
        $this->validator = $validator ?? new PriceListValidator();
        $this->formula = $formula ?? new PriceFormulaService();
        $this->products = $products ?? new ProductRepository();
    }

    /** List price lists with computed status. */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $rows = $this->repo->findAll($validated);
        $total = $this->repo->count($validated);
        foreach ($rows as &$row) { $row['status'] = $this->status($row); }
        return [
            'success' => true,
            'data' => $rows,
            'pagination' => $this->formatPagination($validated, $total),
        ];
    }

    /** Get a single price list. */
    public function get(int $id): array
    {
        $row = $this->requirePriceList($id);
        $row['status'] = $this->status($row);
        return ['success' => true, 'data' => $row];
    }

    /** Create price list. */
    public function create(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        if ($this->repo->nameExists($validated['name'])) {
            throw new InvalidArgumentException('Price list name already exists');
        }
        $this->assertNoCircular(null, $validated['base_price_list_id'] ?? null);

        if (isset($validated['config']) && is_array($validated['config'])) {
            $validated['config'] = json_encode($validated['config']);
        }

        $row = $this->repo->create($validated);
        $row['status'] = $this->status($row);
        return ['success' => true, 'data' => $row];
    }

    /** Update price list. */
    public function update(int $id, array $data): array
    {
        $this->requirePriceList($id);
        $validated = $this->validator->validateUpdate($data);
        if (! empty($validated['name']) && $this->repo->nameExists($validated['name'], $id)) {
            throw new InvalidArgumentException('Price list name already exists');
        }
        $this->assertNoCircular($id, $validated['base_price_list_id'] ?? null);

        if (isset($validated['config']) && is_array($validated['config'])) {
            $validated['config'] = json_encode($validated['config']);
        }

        $this->repo->update($id, $validated);
        return ['success' => true];
    }

    /** Delete price list (soft). */
    public function delete(int $id): array
    {
        $this->requirePriceList($id);
        // Hard delete items to avoid orphan pricing rows
        $this->items->replaceItems($id, []);
        return ['success' => $this->repo->delete($id)];
    }

    /** List items for a price list. */
    public function items(int $priceListId): array
    {
        $this->requirePriceList($priceListId);
        return ['success' => true, 'data' => $this->items->itemsByPriceList($priceListId)];
    }

    /** Replace items in bulk. */
    public function upsertItems(int $priceListId, array $items): array
    {
        $this->requirePriceList($priceListId);
        $validated = $this->validator->validateItems($items);
        $result = $this->items->replaceItems($priceListId, $validated);
        $updated = $this->triggerAutoUpdate($priceListId);
        return [
            'success' => true,
            'inserted' => $result['inserted'],
            'dependents_updated' => count($updated),
            'updated_list_ids' => $updated,
        ];
    }

    /** Apply formula to all items in a price list. */
    public function applyFormula(int $priceListId, array $payload): array
    {
        $priceList = $this->requirePriceList($priceListId);
        
        // Payload: { base: 'cost'|'purchase'|'current'|price_list_id, operator: '+', value: 10, unit: '%'|'VND', rounding: 'thousand' }
        $base = $payload['base'] ?? 'current';
        $operator = $payload['operator'] ?? '+';
        $value = (float) ($payload['value'] ?? 0);
        $unit = $payload['unit'] ?? 'VND';
        $rounding = $payload['rounding'] ?? 'none';

        // Construct formula string for internal service if needed, or calculate manually
        // Formula format: "base + 10%"
        $formulaStr = "base {$operator} {$value}" . ($unit === '%' ? '%' : '');

        $products = $this->products->findAll(['limit' => 10000]); // Process in chunks ideally, but simple for now
        $rows = [];

        foreach ($products as $product) {
            $basePrice = 0;
            if ($base === 'cost') {
                $basePrice = (float) ($product['cost_price'] ?? 0);
            } elseif ($base === 'purchase') {
                $basePrice = (float) ($product['last_purchase_price'] ?? $product['purchase_price'] ?? 0);
            } elseif ($base === 'current') {
                // Get current price from item or product default
                $currentItem = $this->items->findItem($priceListId, $product['id']);
                $basePrice = $currentItem ? (float) $currentItem['price'] : (float) ($product['selling_price'] ?? 0);
            } elseif (is_numeric($base)) {
                // Base is another price list
                $baseItem = $this->items->findItem((int) $base, $product['id']);
                $basePrice = $baseItem ? (float) $baseItem['price'] : 0;
            }

            $newPrice = $this->formula->calculateFromFormula($formulaStr, $basePrice);
            if ($rounding !== 'none') {
                $newPrice = $this->formula->applyRounding($newPrice, $rounding);
            }

            $rows[] = [
                'product_id' => $product['id'],
                'price' => $newPrice,
            ];
        }

        $this->items->replaceItems($priceListId, $rows);
        $this->triggerAutoUpdate($priceListId);

        return ['success' => true, 'updated_count' => count($rows)];
    }

    /** Expose applicable lists to other services. */
    public function applicable(?int $groupId, string $date): array
    {
        return $this->repo->applicablePriceLists($groupId, $date);
    }

    /** Trigger auto-update for dependent price lists. */
    public function triggerAutoUpdate(int $priceListId): array
    {
        $visited = [];
        return $this->triggerAutoUpdateRecursive($priceListId, $visited);
    }

    /** Depth-first auto-update with cycle guard. */
    private function triggerAutoUpdateRecursive(int $priceListId, array &$visited): array
    {
        if (in_array($priceListId, $visited, true)) {
            return [];
        }
        $visited[] = $priceListId;

        $dependents = $this->repo->dependentLists($priceListId);
        if (empty($dependents)) { return []; }

        $updated = [];
        foreach ($dependents as $dependent) {
            $dependentId = (int) $dependent['id'];
            if (in_array($dependentId, $visited, true)) { continue; }
            if (! ($dependent['auto_update'] ?? false)) { continue; }

            $itemCount = $this->recalculateItems($dependentId);
            $updated[] = $dependentId;
            $this->logAutoUpdate($priceListId, $dependentId, $itemCount);

            $nested = $this->triggerAutoUpdateRecursive($dependentId, $visited);
            $updated = array_merge($updated, $nested);
        }

        return array_values(array_unique($updated));
    }

    /** Recalculate all items of a price list using its formula/base. */
    public function recalculateItems(int $priceListId): int
    {
        $priceList = $this->requirePriceList($priceListId);
        $items = $this->items->itemsRaw($priceListId);
        if (empty($items)) { return 0; }

        $rows = [];
        foreach ($items as $item) {
            $basePrice = $this->resolveBasePrice($priceList, $item['product_id'], $item['variant_id'] ?? null);
            $newPrice = $this->calculateFinalPrice($priceList, $basePrice, $item);
            $rows[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'price' => $newPrice,
                'discount_percent' => $item['discount_percent'] ?? 0,
                'discount_amount' => $item['discount_amount'] ?? 0,
            ];
        }
        $this->items->replaceItems($priceListId, $rows);
        return count($rows);
    }

    private function resolveBasePrice(array $priceList, int $productId, ?int $variantId): float
    {
        if (! empty($priceList['base_price_list_id'])) {
            $item = $this->items->findItem((int) $priceList['base_price_list_id'], $productId, $variantId);
            if ($item && isset($item['price'])) {
                return (float) $item['price'];
            }
        }
        // fallback to product selling price
        $product = $this->products->findById($productId);
        return (float) ($product['selling_price'] ?? 0);
    }

    private function calculateFinalPrice(array $priceList, float $basePrice, array $item): float
    {
        if (! empty($priceList['formula'])) {
            $price = $this->formula->calculateFromFormula($priceList['formula'], $basePrice);
            if (! empty($priceList['rounding_rule']) && $priceList['rounding_rule'] !== 'none') {
                $price = $this->formula->applyRounding($price, $priceList['rounding_rule']);
            }
            return $price;
        }
        return $this->applyDiscounts($basePrice, $item);
    }

    private function applyDiscounts(float $base, array $item): float
    {
        $price = (isset($item['price']) && (float) $item['price'] > 0) ? (float) $item['price'] : $base;
        $price -= $price * ((float) ($item['discount_percent'] ?? 0) / 100);
        $price -= (float) ($item['discount_amount'] ?? 0);
        return max(0, $price);
    }

    private function requirePriceList(int $id): array
    {
        $row = $this->repo->findById($id);
        if (! $row) { throw new RuntimeException('Price list not found'); }
        return $row;
    }

    private function status(array $row): string
    {
        if (! ($row['is_active'] ?? true)) { return 'inactive'; }
        $today = date('Y-m-d');
        $start = $row['start_date'] ?? null;
        $end = $row['end_date'] ?? null;
        if ($start && $start > $today) { return 'upcoming'; }
        if ($end && $end < $today) { return 'expired'; }
        return 'active';
    }

    private function formatPagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $totalPages = (int) ceil($total / ($limit ?: 1));
        return ['page' => $page, 'limit' => $limit, 'total' => $total, 'total_pages' => $totalPages];
    }

    /** Detect circular reference: current -> base -> ... -> current */
    private function assertNoCircular(?int $currentId, ?int $baseId): void
    {
        $currentId = $currentId ? (int) $currentId : 0;
        $baseId = $baseId ? (int) $baseId : 0;
        if ($currentId <= 0 || $baseId <= 0) { return; }
        $visited = [];
        $check = $baseId;
        while ($check !== null) {
            log_message('info', 'assertNoCircular current=' . $currentId . ' checking=' . $check);
            if ($check === $currentId) {
                throw new InvalidArgumentException('Circular price list reference detected');
            }
            if (in_array($check, $visited, true)) { break; }
            $visited[] = $check;
            $row = $this->repo->findById($check);
            $check = $row ? (int) ($row['base_price_list_id'] ?? 0) : null;
        }
    }

    private function logAutoUpdate(int $sourceId, int $updatedId, int $items): void
    {
        $msg = sprintf('[Auto-update] Base list #%d -> updated list #%d (%d items recalculated)', $sourceId, $updatedId, $items);
        log_message('info', $msg);
    }
}
