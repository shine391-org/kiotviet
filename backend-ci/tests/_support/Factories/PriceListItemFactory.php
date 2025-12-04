<?php

namespace Tests\Support\Factories;

/**
 * Factory for creating price list item test data
 * 
 * @agent-factory: Price list item entity factory
 * @agent-pattern: Factory pattern for price list item test data
 * @agent-reusable: HIGH
 */
class PriceListItemFactory extends BaseFactory
{
    protected static string $table = 'price_list_items';
    
    protected static array $defaultAttributes = [
        'price_list_id' => null,
        'product_id' => null,
        'variant_id' => null,
        'price' => 100.00,
        'discount_percent' => 0.0,
        'discount_amount' => 0.0,
        'min_quantity' => 1,
        'max_quantity' => 9999,
        'start_date' => null, // Will be set automatically
        'end_date' => null,
        'is_active' => 1,
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
    ];
    
    /**
     * Create a price list item with specific attributes
     * 
     * @param array $attributes Override attributes
     * @return int Price list item ID
     */
    public static function create(array $attributes = []): int
    {
        // Generate random price if not provided
        if (!isset($attributes['price'])) {
            $attributes['price'] = static::randomFloat(10.0, 500.0);
        }
        
        // Set default start date if not provided
        if (!isset($attributes['start_date'])) {
            $attributes['start_date'] = date('Y-m-d', strtotime('-1 day'));
        }
        
        return parent::create($attributes);
    }
    
    /**
     * Create a price list item for a product
     * 
     * @param int $priceListId Price list ID
     * @param int $productId Product ID
     * @param float $price Price value
     * @param array $attributes Override attributes
     * @return int Price list item ID
     */
    public static function createForProduct(int $priceListId, int $productId, float $price, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'variant_id' => null,
            'price' => $price
        ]));
    }
    
    /**
     * Create a price list item for a variant
     * 
     * @param int $priceListId Price list ID
     * @param int $productId Product ID
     * @param int $variantId Variant ID
     * @param float $price Price value
     * @param array $attributes Override attributes
     * @return int Price list item ID
     */
    public static function createForVariant(int $priceListId, int $productId, int $variantId, float $price, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'price' => $price
        ]));
    }
    
    /**
     * Create a price list item with discount
     * 
     * @param int $priceListId Price list ID
     * @param int $productId Product ID
     * @param float $originalPrice Original price
     * @param float $discountPercent Discount percentage
     * @param array $attributes Override attributes
     * @return int Price list item ID
     */
    public static function createWithDiscount(int $priceListId, int $productId, float $originalPrice, float $discountPercent, array $attributes = []): int
    {
        $finalPrice = $originalPrice * (1 - $discountPercent / 100);
        
        return static::create(array_merge($attributes, [
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'price' => $finalPrice,
            'discount_percent' => $discountPercent
        ]));
    }
    
    /**
     * Create a price list item with quantity range
     * 
     * @param int $priceListId Price list ID
     * @param int $productId Product ID
     * @param float $price Price value
     * @param int $minQuantity Minimum quantity
     * @param int $maxQuantity Maximum quantity
     * @param array $attributes Override attributes
     * @return int Price list item ID
     */
    public static function createWithQuantityRange(int $priceListId, int $productId, float $price, int $minQuantity, int $maxQuantity, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'price' => $price,
            'min_quantity' => $minQuantity,
            'max_quantity' => $maxQuantity
        ]));
    }
    
    /**
     * Create a price list item with date range
     * 
     * @param int $priceListId Price list ID
     * @param int $productId Product ID
     * @param float $price Price value
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format, optional)
     * @param array $attributes Override attributes
     * @return int Price list item ID
     */
    public static function createWithDateRange(int $priceListId, int $productId, float $price, string $startDate, string $endDate = null, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'price' => $price,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));
    }
    
    /**
     * Create an inactive price list item
     * 
     * @param int $priceListId Price list ID
     * @param int $productId Product ID
     * @param float $price Price value
     * @param array $attributes Override attributes
     * @return int Price list item ID
     */
    public static function createInactive(int $priceListId, int $productId, float $price, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'price' => $price,
            'is_active' => 0
        ]));
    }
}