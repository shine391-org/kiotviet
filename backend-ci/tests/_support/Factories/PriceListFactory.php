<?php

namespace Tests\Support\Factories;

/**
 * Factory for creating price list test data
 * 
 * @agent-factory: Price list entity factory
 * @agent-pattern: Factory pattern for price list test data
 * @agent-reusable: HIGH
 */
class PriceListFactory extends BaseFactory
{
    protected static string $table = 'price_lists';
    
    protected static array $defaultAttributes = [
        'name' => 'Test Price List',
        'description' => 'Test price list description',
        'type' => 'custom',
        'priority' => 0,
        'is_active' => 1,
        'start_date' => null, // Will be set automatically
        'end_date' => null,
        'formula' => null,
        'base_price_list_id' => null,
        'auto_update' => 0,
        'rounding_rule' => 'none',
        'apply_to_groups' => null,
        'apply_to_customers' => null,
        'apply_to_products' => null,
        'apply_to_categories' => null,
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
        'deleted_at' => null,
    ];
    
    /**
     * Create a price list with specific attributes
     * 
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function create(array $attributes = []): int
    {
        // Generate name if not provided
        if (!isset($attributes['name'])) {
            $attributes['name'] = 'Price List ' . static::generateCode('PL', 4);
        }
        
        // Set default start date if not provided
        if (!isset($attributes['start_date'])) {
            $attributes['start_date'] = date('Y-m-d', strtotime('-1 day'));
        }
        
        // Generate random priority if not provided
        if (!isset($attributes['priority'])) {
            $attributes['priority'] = mt_rand(1, 100);
        }
        
        return parent::create($attributes);
    }
    
    /**
     * Create an active price list
     * 
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createActive(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => null
        ]));
    }
    
    /**
     * Create an inactive price list
     * 
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createInactive(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'is_active' => 0
        ]));
    }
    
    /**
     * Create a price list with specific priority
     * 
     * @param int $priority Priority level
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createWithPriority(int $priority, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'priority' => $priority
        ]));
    }
    
    /**
     * Create a price list with formula
     * 
     * @param string $formula Formula expression
     * @param int $baseListId Base price list ID
     * @param bool $autoUpdate Auto update flag
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createWithFormula(string $formula, int $baseListId = null, bool $autoUpdate = false, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'formula' => $formula,
            'base_price_list_id' => $baseListId,
            'auto_update' => $autoUpdate ? 1 : 0
        ]));
    }
    
    /**
     * Create a price list with date range
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format, optional)
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createWithDateRange(string $startDate, string $endDate = null, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));
    }
    
    /**
     * Create a price list for specific customer groups
     * 
     * @param array $groupIds Array of customer group IDs
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createForCustomerGroups(array $groupIds, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'apply_to_groups' => json_encode($groupIds)
        ]));
    }
    
    /**
     * Create a price list for specific customers
     * 
     * @param array $customerIds Array of customer IDs
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createForCustomers(array $customerIds, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'apply_to_customers' => json_encode($customerIds)
        ]));
    }
    
    /**
     * Create a price list for specific products
     * 
     * @param array $productIds Array of product IDs
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createForProducts(array $productIds, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'apply_to_products' => json_encode($productIds)
        ]));
    }
    
    /**
     * Create a price list for specific categories
     * 
     * @param array $categoryIds Array of category IDs
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createForCategories(array $categoryIds, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'apply_to_categories' => json_encode($categoryIds)
        ]));
    }
    
    /**
     * Create a price list with rounding rule
     * 
     * @param string $roundingRule Rounding rule (none, up, down, nearest)
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createWithRounding(string $roundingRule, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'rounding_rule' => $roundingRule
        ]));
    }
    
    /**
     * Create a percentage discount price list
     * 
     * @param float $discountPercentage Discount percentage (e.g., 20 for 20%)
     * @param int $baseListId Base price list ID
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createPercentageDiscount(float $discountPercentage, int $baseListId = null, array $attributes = []): int
    {
        $formula = "base * " . ((100 - $discountPercentage) / 100);
        return static::createWithFormula($formula, $baseListId, true, $attributes);
    }
    
    /**
     * Create a fixed amount discount price list
     * 
     * @param float $discountAmount Fixed discount amount
     * @param int $baseListId Base price list ID
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createFixedDiscount(float $discountAmount, int $baseListId = null, array $attributes = []): int
    {
        $formula = "base - {$discountAmount}";
        return static::createWithFormula($formula, $baseListId, true, $attributes);
    }
    
    /**
     * Create a markup price list
     * 
     * @param float $markupPercentage Markup percentage (e.g., 20 for 20%)
     * @param int $baseListId Base price list ID
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createMarkup(float $markupPercentage, int $baseListId = null, array $attributes = []): int
    {
        $formula = "base * " . (1 + $markupPercentage / 100);
        return static::createWithFormula($formula, $baseListId, true, $attributes);
    }
    
    /**
     * Create a price list with items
     * 
     * @param array $items Array of price list items
     * @param array $attributes Override attributes
     * @return int Price list ID
     */
    public static function createWithItems(array $items, array $attributes = []): int
    {
        $priceListId = static::create($attributes);
        
        foreach ($items as $item) {
            PriceListItemFactory::create(array_merge($item, [
                'price_list_id' => $priceListId
            ]));
        }
        
        return $priceListId;
    }
}