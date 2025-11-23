<?php

namespace App\Services\PriceLists;

use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\ProductVariants\ProductVariantRepository;
use App\Repositories\Products\ProductRepository;
use RuntimeException;

/** Price calculation based on price lists. @agent-service: Price calculator @agent-pattern: Pricing engine @agent-reusable: HIGH */
class PriceCalculatorService
{
    protected PriceListRepository $priceLists;
    protected PriceListItemRepository $items;
    protected ProductRepository $products;
    protected ProductVariantRepository $variants;
    protected PriceFormulaService $formula;

    public function __construct(
        ?PriceListRepository $priceLists = null,
        ?PriceListItemRepository $items = null,
        ?ProductRepository $products = null,
        ?ProductVariantRepository $variants = null,
        ?PriceFormulaService $formula = null
    ) {
        $this->priceLists = $priceLists ?? new PriceListRepository();
        $this->items = $items ?? new PriceListItemRepository();
        $this->products = $products ?? new ProductRepository();
        $this->variants = $variants ?? new ProductVariantRepository();
        $this->formula = $formula ?? new PriceFormulaService();
    }

    /**
     * Get price for a product/variant under current price lists.
     *
     * @agent-use: Order pricing
     * @agent-pattern: Highest priority list wins
     */
    public function getProductPrice(int $productId, ?int $variantId, ?int $customerGroupId, int $quantity = 1, ?string $orderDate = null): array
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('quantity must be greater than 0');
        }

        $date = $orderDate ?: date('Y-m-d');
        $product = $this->products->findById($productId);
        if (! $product) { throw new RuntimeException('Product not found'); }

        $basePrice = $variantId ? $this->variantPrice($variantId, $productId) : (float) ($product['selling_price'] ?? 0);

        $applied = null; $final = $basePrice;
        $lists = $this->priceLists->applicablePriceLists($customerGroupId, $date);
        foreach ($lists as $list) {
            $item = $this->items->findItem((int) $list['id'], $productId, $variantId);
            if ($item) {
                $applied = $list;
                // If list has formula, compute from formula; else apply discount.
                if (! empty($list['formula'])) {
                    $baseForFormula = $this->resolveBaseForFormula($list, $productId, $variantId, $basePrice);
                    $final = $this->formula->calculateFromFormula($list['formula'], $baseForFormula);
                    if (! empty($list['rounding_rule']) && $list['rounding_rule'] !== 'none') {
                        $final = $this->formula->applyRounding($final, $list['rounding_rule']);
                    }
                } else {
                    $final = $this->applyPricing($basePrice, $item);
                }
                break;
            }
        }

        return [
            'success' => true,
            'base_price' => $basePrice,
            'final_price' => round($final, 2),
            'applied_price_list_id' => $applied['id'] ?? null,
            'applied_price_list_name' => $applied['name'] ?? null,
            'price_list_type' => $applied['type'] ?? null,
            'quantity' => $quantity,
            'line_total' => round($final * $quantity, 2),
        ];
    }

    private function variantPrice(int $variantId, int $productId): float
    {
        $variant = $this->variants->findById($variantId);
        if (! $variant || (int) $variant['product_id'] !== $productId) {
            throw new RuntimeException('Variant not found for product');
        }
        return (float) ($variant['price'] ?? 0);
    }

    private function applyPricing(float $base, array $item): float
    {
        $price = (isset($item['price']) && (float) $item['price'] > 0) ? (float) $item['price'] : $base;
        $price -= $price * ((float) ($item['discount_percent'] ?? 0) / 100);
        $price -= (float) ($item['discount_amount'] ?? 0);
        return max(0, $price);
    }

    private function resolveBaseForFormula(array $list, int $productId, ?int $variantId, float $fallbackBase): float
    {
        if (! empty($list['base_price_list_id'])) {
            $baseItem = $this->items->findItem((int) $list['base_price_list_id'], $productId, $variantId);
            if ($baseItem && isset($baseItem['price'])) {
                return (float) $baseItem['price'];
            }
        }
        return $fallbackBase;
    }
}
