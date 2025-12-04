<?php

namespace Tests\Support\Factories;

/**
 * Factory for creating product test data
 * 
 * @agent-factory: Product entity factory
 * @agent-pattern: Factory pattern for product test data
 * @agent-reusable: HIGH
 */
class ProductFactory extends BaseFactory
{
    protected static string $table = 'products';
    
    protected static array $defaultAttributes = [
        'product_type' => 'standard',
        'code' => null, // Will be generated
        'name' => 'Test Product',
        'description' => 'Test product description',
        'status' => 'active',
        'selling_price' => 100.00,
        'cost_price' => 80.00,
        'weight' => 1.0,
        'dimensions' => null,
        'category_id' => null,
        'brand_id' => null,
        'tax_rate' => 0.0,
        'track_inventory' => 1,
        'allow_negative_stock' => 0,
        'minimum_stock' => 0,
        'maximum_stock' => 9999,
        'reorder_point' => 10,
        'reorder_quantity' => 50,
        'is_digital' => 0,
        'requires_shipping' => 1,
        'meta_title' => null,
        'meta_description' => null,
        'meta_keywords' => null,
        'tags' => null,
        'images' => null,
        'attributes' => null,
        'variants' => null,
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
        'deleted_at' => null,
    ];
    
    /**
     * Create a product with specific attributes
     * 
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function create(array $attributes = []): int
    {
        // Generate code if not provided
        if (!isset($attributes['code'])) {
            $attributes['code'] = static::generateCode('PROD');
        }
        
        // Generate random prices if not provided
        if (!isset($attributes['selling_price'])) {
            $attributes['selling_price'] = static::randomFloat(10.0, 500.0);
        }
        
        if (!isset($attributes['cost_price'])) {
            $attributes['cost_price'] = static::randomFloat(5.0, 400.0);
        }
        
        // Generate random weight if not provided
        if (!isset($attributes['weight'])) {
            $attributes['weight'] = static::randomFloat(0.1, 10.0, 1);
        }
        
        return parent::create($attributes);
    }
    
    /**
     * Create a product with variants
     * 
     * @param array $variants Array of variant data
     * @param array $attributes Product attributes
     * @return int Product ID
     */
    public static function createWithVariants(array $variants, array $attributes = []): int
    {
        $productId = static::create($attributes);
        
        foreach ($variants as $variant) {
            VariantFactory::create(array_merge($variant, ['product_id' => $productId]));
        }
        
        return $productId;
    }
    
    /**
     * Create a simple product (no variants)
     * 
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function createSimple(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'product_type' => 'simple',
            'variants' => null
        ]));
    }
    
    /**
     * Create a configurable product (with variants)
     * 
     * @param array $variants Array of variant data
     * @param array $attributes Product attributes
     * @return int Product ID
     */
    public static function createConfigurable(array $variants, array $attributes = []): int
    {
        return static::createWithVariants($variants, array_merge($attributes, [
            'product_type' => 'configurable'
        ]));
    }
    
    /**
     * Create a digital product
     * 
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function createDigital(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'is_digital' => 1,
            'requires_shipping' => 0,
            'track_inventory' => 0
        ]));
    }
    
    /**
     * Create an inactive product
     * 
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function createInactive(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'inactive'
        ]));
    }
    
    /**
     * Create a product with specific price
     * 
     * @param float $sellingPrice Selling price
     * @param float $costPrice Cost price
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function createWithPrice(float $sellingPrice, float $costPrice = null, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'selling_price' => $sellingPrice,
            'cost_price' => $costPrice ?: ($sellingPrice * 0.8)
        ]));
    }
    
    /**
     * Create a product with inventory tracking
     * 
     * @param float $minimumStock Minimum stock level
     * @param float $reorderPoint Reorder point
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function createWithInventory(float $minimumStock = 0, float $reorderPoint = 10, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'track_inventory' => 1,
            'minimum_stock' => $minimumStock,
            'reorder_point' => $reorderPoint
        ]));
    }
    
    /**
     * Create a product with category
     * 
     * @param int $categoryId Category ID
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function createWithCategory(int $categoryId, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'category_id' => $categoryId
        ]));
    }
    
    /**
     * Create a product with brand
     * 
     * @param int $brandId Brand ID
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function createWithBrand(int $brandId, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'brand_id' => $brandId
        ]));
    }
    
    /**
     * Create a product with specific weight and dimensions
     * 
     * @param float $weight Product weight
     * @param array $dimensions Product dimensions [length, width, height]
     * @param array $attributes Override attributes
     * @return int Product ID
     */
    public static function createWithShipping(float $weight, array $dimensions = null, array $attributes = []): int
    {
        $shippingData = [
            'weight' => $weight,
            'requires_shipping' => 1
        ];
        
        if ($dimensions !== null) {
            $shippingData['dimensions'] = json_encode([
                'length' => $dimensions[0] ?? 10,
                'width' => $dimensions[1] ?? 10,
                'height' => $dimensions[2] ?? 10
            ]);
        }
        
        return static::create(array_merge($attributes, $shippingData));
    }
}