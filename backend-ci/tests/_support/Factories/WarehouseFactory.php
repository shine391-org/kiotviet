<?php

namespace Tests\Support\Factories;

/**
 * Factory for creating warehouse test data
 * 
 * @agent-factory: Warehouse entity factory
 * @agent-pattern: Factory pattern for warehouse test data
 * @agent-reusable: HIGH
 */
class WarehouseFactory extends BaseFactory
{
    protected static string $table = 'warehouses';
    
    protected static array $defaultAttributes = [
        'code' => null, // Will be generated
        'name' => 'Test Warehouse',
        'description' => 'Test warehouse description',
        'address' => '123 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'phone' => '+1234567890',
        'email' => 'warehouse@test.com',
        'contact_person' => 'Test Contact',
        'status' => 'active',
        'is_default' => 0,
        'capacity' => 10000,
        'current_stock_value' => 0,
        'opening_hours' => null,
        'special_instructions' => null,
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
        'deleted_at' => null,
    ];
    
    /**
     * Create a warehouse with specific attributes
     * 
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function create(array $attributes = []): int
    {
        // Generate code if not provided
        if (!isset($attributes['code'])) {
            $attributes['code'] = static::generateCode('WH');
        }
        
        // Generate random capacity if not provided
        if (!isset($attributes['capacity'])) {
            $attributes['capacity'] = mt_rand(1000, 50000);
        }
        
        return parent::create($attributes);
    }
    
    /**
     * Create an active warehouse
     * 
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createActive(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'active'
        ]));
    }
    
    /**
     * Create an inactive warehouse
     * 
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createInactive(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'inactive'
        ]));
    }
    
    /**
     * Create a default warehouse
     * 
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createDefault(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'is_default' => 1
        ]));
    }
    
    /**
     * Create a warehouse with specific capacity
     * 
     * @param int $capacity Warehouse capacity
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createWithCapacity(int $capacity, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'capacity' => $capacity
        ]));
    }
    
    /**
     * Create a warehouse with address
     * 
     * @param string $address Address
     * @param string $city City
     * @param string $state State
     * @param string $country Country
     * @param string $postalCode Postal code
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createWithAddress(string $address, string $city, string $state, string $country, string $postalCode, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'postal_code' => $postalCode
        ]));
    }
    
    /**
     * Create a warehouse with contact information
     * 
     * @param string $phone Phone number
     * @param string $email Email address
     * @param string $contactPerson Contact person name
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createWithContact(string $phone, string $email, string $contactPerson, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'phone' => $phone,
            'email' => $email,
            'contact_person' => $contactPerson
        ]));
    }
    
    /**
     * Create a warehouse with specific code
     * 
     * @param string $code Warehouse code
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createWithCode(string $code, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'code' => $code
        ]));
    }
    
    /**
     * Create a warehouse with opening hours
     * 
     * @param array $openingHours Opening hours array
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createWithOpeningHours(array $openingHours, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'opening_hours' => json_encode($openingHours)
        ]));
    }
    
    /**
     * Create a warehouse with special instructions
     * 
     * @param string $specialInstructions Special instructions
     * @param array $attributes Override attributes
     * @return int Warehouse ID
     */
    public static function createWithSpecialInstructions(string $specialInstructions, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'special_instructions' => $specialInstructions
        ]));
    }
}