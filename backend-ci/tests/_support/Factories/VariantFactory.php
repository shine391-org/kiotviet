<?php

namespace Tests\Support\Factories;

/**
 * Factory for creating product variant test data
 * 
 * @agent-factory: Product variant entity factory
 * @agent-pattern: Factory pattern for variant test data
 * @agent-reusable: HIGH
 */
class VariantFactory extends BaseFactory
{
    protected static string $table = 'product_variants_v2';
    
    protected static array $defaultAttributes = [
        'product_id' => null,
        'sku' => null, // Will be generated
        'name' => 'Test Variant',
        'price' => 100.00,
        'cost_price' => 80.00,
        'weight' => 1.0,
        'dimensions' => null,
        'barcode' => null,
        'track_inventory' => 1,
        'allow_negative_stock' => 0,
        'minimum_stock' => 0,
        'maximum_stock' => 9999,
        'reorder_point' => 10,
        'reorder_quantity' => 50,
        'status' => 'active',
        'sort_order' => 0,
        'attributes' => null,
        'images' => null,
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
        'deleted_at' => null,
    ];
    
    /**
     * Create a variant with specific attributes
     * 
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function create(array $attributes = []): int
    {
        // Generate SKU if not provided
        if (!isset($attributes['sku'])) {
            $attributes['sku'] = static::generateCode('VAR');
        }
        
        // Generate random prices if not provided
        if (!isset($attributes['price'])) {
            $attributes['price'] = static::randomFloat(10.0, 500.0);
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
     * Create a variant for a product
     * 
     * @param int $productId Product ID
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createForProduct(int $productId, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'product_id' => $productId
        ]));
    }
    
    /**
     * Create a variant with specific SKU
     * 
     * @param string $sku SKU value
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createWithSku(string $sku, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'sku' => $sku
        ]));
    }
    
    /**
     * Create a variant with specific price
     * 
     * @param float $price Price value
     * @param float $costPrice Cost price
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createWithPrice(float $price, float $costPrice = null, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'price' => $price,
            'cost_price' => $costPrice ?: ($price * 0.8)
        ]));
    }
    
    /**
     * Create a variant with attributes
     * 
     * @param array $variantAttributes Variant attributes (e.g., color, size)
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createWithAttributes(array $attributes): array
    {
        $id = static::create($attributes);
        return static::find($id);
    }
    
    /**
     * Create a variant with specific attributes
     *
     * @param array $variantAttributes Variant attributes (e.g., color, size)
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createWithVariantAttributes(array $variantAttributes, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'attributes' => json_encode($variantAttributes)
        ]));
    }
    
    /**
     * Create a variant with specific weight and dimensions
     * 
     * @param float $weight Variant weight
     * @param array $dimensions Variant dimensions [length, width, height]
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createWithShipping(float $weight, array $dimensions = null, array $attributes = []): int
    {
        $shippingData = [
            'weight' => $weight
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
    
    /**
     * Create a variant with inventory tracking
     * 
     * @param float $minimumStock Minimum stock level
     * @param float $reorderPoint Reorder point
     * @param array $attributes Override attributes
     * @return int Variant ID
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
     * Create an inactive variant
     * 
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createInactive(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'inactive'
        ]));
    }
    
    /**
     * Create a variant with barcode
     * 
     * @param string $barcode Barcode value
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createWithBarcode(string $barcode, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'barcode' => $barcode
        ]));
    }
    
    /**
     * Create a variant with sort order
     * 
     * @param int $sortOrder Sort order value
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createWithSortOrder(int $sortOrder, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'sort_order' => $sortOrder
        ]));
    }
    
    /**
     * Create a variant with images
     * 
     * @param array $images Array of image URLs or paths
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createWithImages(array $images, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'images' => json_encode($images)
        ]));
    }
    
    /**
     * Create a size variant (e.g., S, M, L)
     * 
     * @param string $size Size value
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createSizeVariant(string $size, array $attributes = []): int
    {
        return static::createWithAttributes(['size' => $size], array_merge($attributes, [
            'name' => "Size {$size}"
        ]));
    }
    
    /**
     * Create a color variant (e.g., Red, Blue, Green)
     * 
     * @param string $color Color value
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createColorVariant(string $color, array $attributes = []): int
    {
        return static::createWithAttributes(['color' => $color], array_merge($attributes, [
            'name' => "Color {$color}"
        ]));
    }
    
    /**
     * Create a variant with multiple attributes
     * 
     * @param string $color Color value
     * @param string $size Size value
     * @param array $additionalAttributes Additional attributes
     * @param array $attributes Override attributes
     * @return int Variant ID
     */
    public static function createMultiAttributeVariant(string $color, string $size, array $additionalAttributes = [], array $attributes = []): int
    {
        $variantAttributes = array_merge([
            'color' => $color,
            'size' => $size
        ], $additionalAttributes);
        
        return static::createWithAttributes($variantAttributes, array_merge($attributes, [
            'name' => "{$color} / {$size}"
        ]));
    }
}