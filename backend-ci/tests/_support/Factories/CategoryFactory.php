<?php

namespace Tests\Support\Factories;

/**
 * @agent-factory: Product category factory for test data
 * @agent-pattern: Factory pattern for category creation
 * @agent-reusable: HIGH
 */
class CategoryFactory extends BaseFactory
{
    protected static string $table = 'product_categories';
    protected static array $defaultAttributes = [
        'name' => 'Test Category',
        'code' => null, // Will be generated
        'description' => 'Test category description',
        'status' => 'active',
        'parent_id' => null,
        'sort_order' => 0,
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
        'deleted_at' => null,
    ];
    
    /**
     * Create a category with generated code
     * @agent-use: Create standard test category
     * @agent-pattern: Factory create with auto-generated code
     */
    public static function create(array $attributes = []): int
    {
        if (!isset($attributes['code'])) {
            $attributes['code'] = static::generateCode('CAT');
        }
        
        $attributes['created_at'] = $attributes['created_at'] ?? date('Y-m-d H:i:s');
        $attributes['updated_at'] = $attributes['updated_at'] ?? date('Y-m-d H:i:s');
        
        return parent::create($attributes);
    }
    
    /**
     * Create a category with specific ID
     * @agent-use: Create category with specific ID for testing
     * @agent-pattern: Factory create with ID
     */
    public static function createWithId(int $id, array $attributes = []): int
    {
        if (!isset($attributes['code'])) {
            $attributes['code'] = static::generateCode('CAT');
        }
        
        $attributes['created_at'] = $attributes['created_at'] ?? date('Y-m-d H:i:s');
        $attributes['updated_at'] = $attributes['updated_at'] ?? date('Y-m-d H:i:s');
        
        return parent::createWithId($id, $attributes);
    }
    
    /**
     * Create an inactive category
     * @agent-use: Create inactive category for testing
     * @agent-pattern: Factory create with status
     */
    public static function createInactive(array $attributes = []): int
    {
        $attributes['status'] = 'inactive';
        return static::create($attributes);
    }
    
    /**
     * Create a deleted category (soft delete)
     * @agent-use: Create soft-deleted category for testing
     * @agent-pattern: Factory create with soft delete
     */
    public static function createDeleted(array $attributes = []): int
    {
        $attributes['deleted_at'] = date('Y-m-d H:i:s');
        return static::create($attributes);
    }
    
    /**
     * Create a child category
     * @agent-use: Create category with parent
     * @agent-pattern: Factory create with parent
     */
    public static function createChild(int $parentId, array $attributes = []): int
    {
        $attributes['parent_id'] = $parentId;
        return static::create($attributes);
    }
    
    /**
     * Create category with specific name
     * @agent-use: Create category with custom name
     * @agent-pattern: Factory create with name
     */
    public static function createWithName(string $name, array $attributes = []): int
    {
        $attributes['name'] = $name;
        return static::create($attributes);
    }
    
    /**
     * Create category with specific code
     * @agent-use: Create category with custom code
     * @agent-pattern: Factory create with code
     */
    public static function createWithCode(string $code, array $attributes = []): int
    {
        $attributes['code'] = $code;
        return static::create($attributes);
    }
    
    /**
     * Create category with sort order
     * @agent-use: Create category with custom sort order
     * @agent-pattern: Factory create with sort order
     */
    public static function createWithSortOrder(int $sortOrder, array $attributes = []): int
    {
        $attributes['sort_order'] = $sortOrder;
        return static::create($attributes);
    }
    
    /**
     * Link category to product
     * @agent-use: Create category-product link
     * @agent-pattern: Factory create relationship
     */
    public static function linkToProduct(int $categoryId, int $productId): void
    {
        $db = \Config\Database::connect('tests');
        $db->table('product_category_links')->insert([
            'category_id' => $categoryId,
            'product_id' => $productId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Create category and link to product
     * @agent-use: Create and link category in one step
     * @agent-pattern: Factory create with relationship
     */
    public static function createAndLink(int $productId, array $attributes = []): int
    {
        $categoryId = static::create($attributes);
        static::linkToProduct($categoryId, $productId);
        return $categoryId;
    }
}