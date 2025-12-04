<?php

namespace Tests\Support\Factories;

/**
 * @agent-factory: ReorderLevel factory for test data
 * @agent-pattern: Factory pattern for reorder level creation
 * @agent-reusable: HIGH
 */
class ReorderLevelFactory extends BaseFactory
{
    protected static string $table = 'reorder_levels';
    protected static array $defaultAttributes = [
        'product_id' => 1,
        'branch_id' => 1,
        'min_level' => 5,
        'max_level' => 20,
        'safety_stock' => 2,
        'reorder_point' => 7,
        'reorder_quantity' => 15,
        'is_active' => 1,
        'created_at' => '2025-01-01 00:00:00',
        'updated_at' => '2025-01-01 00:00:00',
        'deleted_at' => null,
    ];

    /**
     * Create standard reorder level
     * @agent-use: Create standard test reorder level
     * @agent-pattern: Factory create with default values
     */
    public static function create(array $attributes = []): int
    {
        $data = array_merge(static::$defaultAttributes, $attributes);
        
        $db = \Config\Database::connect('tests');
        $db->table(static::$table)->insert($data);
        return (int) $db->insertID();
    }

    /**
     * Create reorder level for specific product
     * @agent-use: Create reorder level for specific product
     * @agent-pattern: Factory create with product
     */
    public static function createForProduct(int $productId, array $attributes = []): int
    {
        return static::create(array_merge([
            'product_id' => $productId,
        ], $attributes));
    }

    /**
     * Create reorder level for specific branch
     * @agent-use: Create reorder level for specific branch
     * @agent-pattern: Factory create with branch
     */
    public static function createForBranch(int $branchId, array $attributes = []): int
    {
        return static::create(array_merge([
            'branch_id' => $branchId,
        ], $attributes));
    }

    /**
     * Create reorder level with custom levels
     * @agent-use: Create reorder level with custom min/max levels
     * @agent-pattern: Factory create with custom levels
     */
    public static function createWithLevels(int $minLevel, int $maxLevel, array $attributes = []): int
    {
        return static::create(array_merge([
            'min_level' => $minLevel,
            'max_level' => $maxLevel,
            'reorder_point' => $minLevel + 2,
        ], $attributes));
    }

    /**
     * Create reorder level with safety stock
     * @agent-use: Create reorder level with safety stock
     * @agent-pattern: Factory create with safety stock
     */
    public static function createWithSafetyStock(int $safetyStock, array $attributes = []): int
    {
        return static::create(array_merge([
            'safety_stock' => $safetyStock,
        ], $attributes));
    }

    /**
     * Create reorder level with reorder settings
     * @agent-use: Create reorder level with custom reorder settings
     * @agent-pattern: Factory create with reorder settings
     */
    public static function createWithReorderSettings(int $reorderPoint, int $reorderQuantity, array $attributes = []): int
    {
        return static::create(array_merge([
            'reorder_point' => $reorderPoint,
            'reorder_quantity' => $reorderQuantity,
        ], $attributes));
    }

    /**
     * Create inactive reorder level
     * @agent-use: Create inactive reorder level for testing
     * @agent-pattern: Factory create with inactive status
     */
    public static function createInactive(array $attributes = []): int
    {
        return static::create(array_merge([
            'is_active' => 0,
        ], $attributes));
    }

    /**
     * Create soft-deleted reorder level
     * @agent-use: Create soft-deleted reorder level for testing
     * @agent-pattern: Factory create with soft delete
     */
    public static function createSoftDeleted(array $attributes = []): int
    {
        return static::create(array_merge([
            'deleted_at' => static::now(),
        ], $attributes));
    }

    /**
     * Create reorder level with critical levels
     * @agent-use: Create reorder level with critical stock levels
     * @agent-pattern: Factory create with critical levels
     */
    public static function createCritical(array $attributes = []): int
    {
        return static::create(array_merge([
            'min_level' => 1,
            'max_level' => 5,
            'safety_stock' => 1,
            'reorder_point' => 2,
            'reorder_quantity' => 10,
        ], $attributes));
    }

    /**
     * Create reorder level with bulk settings
     * @agent-use: Create reorder level for bulk items
     * @agent-pattern: Factory create with bulk settings
     */
    public static function createBulk(array $attributes = []): int
    {
        return static::create(array_merge([
            'min_level' => 50,
            'max_level' => 500,
            'safety_stock' => 25,
            'reorder_point' => 75,
            'reorder_quantity' => 200,
        ], $attributes));
    }

    /**
     * Create reorder level with specific ID
     * @agent-use: Create reorder level with specific ID for testing
     * @agent-pattern: Factory create with ID
     */
    public static function createWithId(int $id, array $attributes = []): int
    {
        $data = array_merge(static::$defaultAttributes, $attributes, ['id' => $id]);
        
        $db = \Config\Database::connect('tests');
        $db->table(static::$table)->insert($data);
        return $id;
    }

    /**
     * Create reorder level for product and branch
     * @agent-use: Create reorder level for specific product and branch
     * @agent-pattern: Factory create with product and branch
     */
    public static function createForProductAndBranch(int $productId, int $branchId, array $attributes = []): int
    {
        return static::create(array_merge([
            'product_id' => $productId,
            'branch_id' => $branchId,
        ], $attributes));
    }

    /**
     * Create multiple reorder levels for different branches
     * @agent-use: Create reorder levels for multiple branches
     * @agent-pattern: Factory create many for branches
     */
    public static function createForBranches(int $productId, array $branchIds, array $attributes = []): array
    {
        $ids = [];
        foreach ($branchIds as $branchId) {
            $ids[] = static::createForProductAndBranch($productId, $branchId, $attributes);
        }
        return $ids;
    }
}