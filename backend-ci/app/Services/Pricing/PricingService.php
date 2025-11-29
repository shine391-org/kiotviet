<?php

namespace App\Services\Pricing;

use App\Repositories\PriceLists\CustomerPriceListRepository;
use App\Repositories\PriceLists\ProjectPriceListRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\Pricing\PriceHistoryRepository;
use App\Services\PriceLists\PriceCalculatorService;

/**
 * Pricing engine with customer/project rules.
 *
 * @agent-service: Pricing
 * @agent-pattern: Rule + list priority
 * @agent-reusable: MEDIUM
 */
class PricingService
{
    protected PriceCalculatorService $baseCalculator;
    protected PricingRuleService $rules;
    protected CustomerPriceListRepository $customerPriceLists;
    protected ProjectPriceListRepository $projectPriceLists;
    protected PriceHistoryRepository $history;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct(
        ?PriceCalculatorService $baseCalculator = null,
        ?PricingRuleService $rules = null,
        ?CustomerPriceListRepository $customerPriceLists = null,
        ?ProjectPriceListRepository $projectPriceLists = null,
        ?PriceHistoryRepository $history = null,
        ?\CodeIgniter\Database\BaseConnection $db = null
    ) {
        $conn = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->db = $conn;
        if ($baseCalculator) {
            $this->baseCalculator = $baseCalculator;
        } else {
            $this->baseCalculator = new PriceCalculatorService(
                new PriceListRepository(null, $conn),
                new PriceListItemRepository(null, $conn),
                $conn
            );
        }
        $this->rules = $rules ?? new PricingRuleService();
        $this->customerPriceLists = $customerPriceLists ?? new CustomerPriceListRepository(null, $conn);
        $this->projectPriceLists = $projectPriceLists ?? new ProjectPriceListRepository(null, $conn);
        $this->history = $history ?? new PriceHistoryRepository();
    }

    /**
     * Compute price with priority: customer price list -> project price list -> pricing rule -> base price lists/product.
     */
    public function getPrice(array $input): array
    {
        $productId = (int) ($input['product_id'] ?? 0);
        $variantId = isset($input['variant_id']) ? (int) $input['variant_id'] : null;
        $customerId = isset($input['customer_id']) ? (int) $input['customer_id'] : null;
        $projectId = isset($input['project_id']) ? (int) $input['project_id'] : null;
        $qty = (float) ($input['quantity'] ?? 1);
        $date = $input['order_date'] ?? date('Y-m-d');

        $base = $this->baseCalculator->getProductPrice($productId, $variantId, null, $qty, $date);
        $basePrice = (float) $base['base_price'];
        $finalPrice = (float) $base['final_price'];
        $reason = ['source' => 'base_price', 'price_list_id' => $base['applied_price_list_id'] ?? null];

        // Fallback if base calculator returns zero (e.g., missing price list rows)
        if ($finalPrice <= 0 && $productId > 0) {
            $fallback = $this->fallbackPrice($productId, $variantId);
            $basePrice = $fallback;
            $finalPrice = $fallback;
        }

        $customerList = $customerId ? $this->customerPriceLists->activeForCustomer($customerId, $date) : null;
        if ($customerList) {
            $pl = $this->baseCalculator->getProductPriceByListId((int) $customerList['price_list_id'], $productId, $variantId, $qty);
            $basePrice = (float) $pl['base_price'];
            $finalPrice = (float) $pl['final_price'];
            $reason = ['source' => 'customer_price_list', 'price_list_id' => (int) $customerList['price_list_id']];
        } elseif ($projectId && ($projectList = $this->projectPriceLists->activeForProject($projectId, $date))) {
            $pl = $this->baseCalculator->getProductPriceByListId((int) $projectList['price_list_id'], $productId, $variantId, $qty);
            $basePrice = (float) $pl['base_price'];
            $finalPrice = (float) $pl['final_price'];
            $reason = ['source' => 'project_price_list', 'price_list_id' => (int) $projectList['price_list_id']];
        }

        $rule = $this->rules->findApplicableRule([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'customer_id' => $customerId,
            'project_id' => $projectId,
            'quantity' => $qty,
            'date' => $date,
        ]);
        if ($rule) {
            $rulePrice = $finalPrice;
            $hasPrice = isset($rule['price']) && (float) $rule['price'] > 0;
            $hasDiscount = isset($rule['discount_percent']) && (float) $rule['discount_percent'] > 0;
            if ($hasPrice) {
                $rulePrice = (float) $rule['price'];
            } elseif ($hasDiscount) {
                $rulePrice = $finalPrice * (1 - ((float) $rule['discount_percent'] / 100));
            }
            if (abs($rulePrice - $finalPrice) > 0.0001) {
                $this->history->log([
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'source_type' => 'pricing_rule',
                    'source_id' => $rule['id'],
                    'old_price' => $finalPrice,
                    'new_price' => $rulePrice,
                    'changed_by' => $input['actor_id'] ?? null,
                ]);
            }
            $finalPrice = $rulePrice;
            $reason = ['source' => 'pricing_rule', 'rule_id' => $rule['id']];
        }

        return [
            'success' => true,
            'base_price' => round($basePrice, 4),
            'final_price' => round($finalPrice, 4),
            'line_total' => round($finalPrice * $qty, 4),
            'applied_price_list_id' => $reason['price_list_id'] ?? ($base['applied_price_list_id'] ?? null),
            'applied_price_list_name' => $base['applied_price_list_name'] ?? null,
            'reason' => $reason,
        ];
    }

    private function fallbackPrice(int $productId, ?int $variantId): float
    {
        $builder = $this->db->table('price_list_items')->where('product_id', $productId);
        if ($variantId === null) {
            $builder->where('variant_id IS NULL', null, false);
        } else {
            $builder->where('variant_id', $variantId);
        }
        $row = $builder->get()->getRowArray();
        if (! $row) {
            $productRow = $this->db->table('products')->where('id', $productId)->get()->getRowArray();
            return (float) ($productRow['selling_price'] ?? 0);
        }
        return (float) ($row['price'] ?? 0);
    }
}
