<?php

namespace Tests\Support\Factories;

use Config\Database;

/**
 * Base factory class for creating test data
 * 
 * @agent-factory: Base factory pattern
 * @agent-pattern: Factory pattern for test data
 * @agent-reusable: HIGH
 */
abstract class BaseFactory
{
    /**
     * Table name for the factory
     */
    protected static string $table;
    
    /**
     * Default attributes for the factory
     */
    protected static array $defaultAttributes = [];
    
    /**
     * Database connection
     */
    protected static $db;
    
    /**
     * Set database connection for factories
     *
     * @param mixed $db Database connection
     */
    public static function setDb($db): void
    {
        static::$db = $db;
    }
    
    /**
     * Get database connection
     */
    protected static function getDb()
    {
        if (!static::$db) {
            static::$db = Database::connect('tests');
        }
        return static::$db;
    }
    
    /**
     * Create a single record
     * 
     * @param array $attributes Override attributes
     * @return int Inserted ID
     */
    public static function create(array $attributes = []): int
    {
        $db = static::getDb();
        $data = array_merge(static::$defaultAttributes, $attributes);
        
        // Add timestamps if not present
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $db->table(static::$table)->insert($data);
        return (int) $db->insertID();
    }
    
    /**
     * Create multiple records
     * 
     * @param int $count Number of records to create
     * @param array $attributes Override attributes
     * @return array Array of inserted IDs
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = static::create($attributes);
        }
        return $ids;
    }
    
    /**
     * Create a record with specific attributes
     * 
     * @param array $attributes Specific attributes
     * @return array Created record data
     */
    public static function createWithAttributes(array $attributes): array
    {
        $id = static::create($attributes);
        return static::find($id);
    }
    
    /**
     * Find a record by ID
     * 
     * @param int $id Record ID
     * @return array|null Record data
     */
    public static function find(int $id): ?array
    {
        $db = static::getDb();
        $record = $db->table(static::$table)->where('id', $id)->get()->getRowArray();
        return $record ?: null;
    }
    
    /**
     * Get all records
     * 
     * @param array $where Where conditions
     * @return array Array of records
     */
    public static function all(array $where = []): array
    {
        $db = static::getDb();
        $query = $db->table(static::$table);
        
        if (!empty($where)) {
            $query->where($where);
        }
        
        return $query->get()->getResultArray();
    }
    
    /**
     * Truncate the table
     */
    public static function truncate(): void
    {
        $db = static::getDb();
        $db->table(static::$table)->truncate();
    }
    
    /**
     * Delete records by conditions
     * 
     * @param array $where Where conditions
     */
    public static function deleteWhere(array $where): void
    {
        $db = static::getDb();
        $db->table(static::$table)->where($where)->delete();
    }
    
    /**
     * Generate a unique code
     * 
     * @param string $prefix Code prefix
     * @param int $length Random part length
     * @return string Generated code
     */
    protected static function generateCode(string $prefix, int $length = 6): string
    {
        return $prefix . '-' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }
    
    /**
     * Generate a random float value
     * 
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @param int $decimalPlaces Number of decimal places
     * @return float Random float value
     */
    protected static function randomFloat(float $min = 0.0, float $max = 999.99, int $decimalPlaces = 2): float
    {
        $value = mt_rand($min * 100, $max * 100) / 100;
        return round($value, $decimalPlaces);
    }
    
    /**
     * Generate a random date
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return string Random date
     */
    protected static function randomDate(string $startDate = '-30 days', string $endDate = 'now'): string
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $random = mt_rand($start, $end);
        return date('Y-m-d H:i:s', $random);
    }
}