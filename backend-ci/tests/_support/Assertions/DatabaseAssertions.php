<?php

namespace Tests\Support\Assertions;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Database assertion methods for test validation
 * 
 * @agent-assertions: Database state validation
 * @agent-pattern: Strong database assertions
 * @agent-reusable: HIGH
 */
trait DatabaseAssertions
{
    /**
     * Assert that a row exists in the database
     * 
     * @param string $table Table name
     * @param array $data Where conditions
     * @param string $message Custom error message
     */
    public function assertDatabaseHas(string $table, array $data, string $message = ''): void
    {
        $count = $this->db->table($table)->where($data)->countAllResults();
        $defaultMessage = "Failed asserting that row exists in table {$table} with conditions: " . json_encode($data);
        $this->assertTrue($count > 0, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that a row does not exist in the database
     * 
     * @param string $table Table name
     * @param array $data Where conditions
     * @param string $message Custom error message
     */
    public function assertDatabaseMissing(string $table, array $data, string $message = ''): void
    {
        $count = $this->db->table($table)->where($data)->countAllResults();
        $defaultMessage = "Failed asserting that row does not exist in table {$table} with conditions: " . json_encode($data);
        $this->assertEquals(0, $count, $message ?: $defaultMessage);
    }
    
    /**
     * Assert the exact count of rows in a table
     * 
     * @param string $table Table name
     * @param int $expected Expected count
     * @param array $where Optional where conditions
     * @param string $message Custom error message
     */
    public function assertDatabaseCount(string $table, int $expected, array $where = [], string $message = ''): void
    {
        $query = $this->db->table($table);
        if (!empty($where)) {
            $query->where($where);
        }
        $actual = $query->countAllResults();
        $defaultMessage = "Expected {$expected} rows in {$table}, found {$actual}";
        if (!empty($where)) {
            $defaultMessage .= " with conditions: " . json_encode($where);
        }
        $this->assertEquals($expected, $actual, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that a table has at least the specified number of rows
     * 
     * @param string $table Table name
     * @param int $minimum Minimum expected count
     * @param array $where Optional where conditions
     * @param string $message Custom error message
     */
    public function assertDatabaseHasAtLeast(string $table, int $minimum, array $where = [], string $message = ''): void
    {
        $query = $this->db->table($table);
        if (!empty($where)) {
            $query->where($where);
        }
        $actual = $query->countAllResults();
        $defaultMessage = "Expected at least {$minimum} rows in {$table}, found {$actual}";
        if (!empty($where)) {
            $defaultMessage .= " with conditions: " . json_encode($where);
        }
        $this->assertGreaterThanOrEqual($minimum, $actual, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that a table has at most the specified number of rows
     * 
     * @param string $table Table name
     * @param int $maximum Maximum expected count
     * @param array $where Optional where conditions
     * @param string $message Custom error message
     */
    public function assertDatabaseHasAtMost(string $table, int $maximum, array $where = [], string $message = ''): void
    {
        $query = $this->db->table($table);
        if (!empty($where)) {
            $query->where($where);
        }
        $actual = $query->countAllResults();
        $defaultMessage = "Expected at most {$maximum} rows in {$table}, found {$actual}";
        if (!empty($where)) {
            $defaultMessage .= " with conditions: " . json_encode($where);
        }
        $this->assertLessThanOrEqual($maximum, $actual, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that a specific column value exists in a table
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param mixed $value Expected value
     * @param array $additionalWhere Additional where conditions
     * @param string $message Custom error message
     */
    public function assertDatabaseHasColumnValue(string $table, string $column, $value, array $additionalWhere = [], string $message = ''): void
    {
        $where = array_merge([$column => $value], $additionalWhere);
        $this->assertDatabaseHas($table, $where, $message);
    }
    
    /**
     * Assert that a record was created within the specified time range
     * 
     * @param string $table Table name
     * @param int $recordId Record ID
     * @param int $secondsAgo Maximum seconds ago
     * @param string $message Custom error message
     */
    public function assertDatabaseRecordCreatedRecently(string $table, int $recordId, int $secondsAgo = 60, string $message = ''): void
    {
        $record = $this->db->table($table)->where('id', $recordId)->get()->getRowArray();
        $this->assertNotNull($record, "Record with ID {$recordId} not found in {$table}");
        
        if (isset($record['created_at'])) {
            $createdAt = strtotime($record['created_at']);
            $now = time();
            $diff = $now - $createdAt;
            
            $defaultMessage = "Record {$recordId} in {$table} was created {$diff} seconds ago, expected within {$secondsAgo} seconds";
            $this->assertLessThanOrEqual($secondsAgo, $diff, $message ?: $defaultMessage);
        }
    }
    
    /**
     * Assert that a record was updated within the specified time range
     * 
     * @param string $table Table name
     * @param int $recordId Record ID
     * @param int $secondsAgo Maximum seconds ago
     * @param string $message Custom error message
     */
    public function assertDatabaseRecordUpdatedRecently(string $table, int $recordId, int $secondsAgo = 60, string $message = ''): void
    {
        $record = $this->db->table($table)->where('id', $recordId)->get()->getRowArray();
        $this->assertNotNull($record, "Record with ID {$recordId} not found in {$table}");
        
        if (isset($record['updated_at'])) {
            $updatedAt = strtotime($record['updated_at']);
            $now = time();
            $diff = $now - $updatedAt;
            
            $defaultMessage = "Record {$recordId} in {$table} was updated {$diff} seconds ago, expected within {$secondsAgo} seconds";
            $this->assertLessThanOrEqual($secondsAgo, $diff, $message ?: $defaultMessage);
        }
    }
    
    /**
     * Assert that a decimal column has the expected precision
     * 
     * @param string $table Table name
     * @param int $recordId Record ID
     * @param string $column Column name
     * @param float $expected Expected value
     * @param float $delta Allowed delta
     * @param string $message Custom error message
     */
    public function assertDatabaseDecimalValue(string $table, int $recordId, string $column, float $expected, float $delta = 0.01, string $message = ''): void
    {
        $record = $this->db->table($table)->where('id', $recordId)->get()->getRowArray();
        $this->assertNotNull($record, "Record with ID {$recordId} not found in {$table}");
        $this->assertArrayHasKey($column, $record, "Column {$column} not found in record");
        
        $actual = (float) $record[$column];
        $defaultMessage = "Expected {$column} to be {$expected}, found {$actual} in {$table} record {$recordId}";
        $this->assertEqualsWithDelta($expected, $actual, $delta, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that a JSON column contains specific data
     *
     * @param string $table Table name
     * @param int $recordId Record ID
     * @param string $column Column name
     * @param array $expectedData Expected JSON data
     * @param string $message Custom error message
     */
    public function assertDatabaseJsonContains(string $table, int $recordId, string $column, array $expectedData, string $message = ''): void
    {
        $record = $this->db->table($table)->where('id', $recordId)->get()->getRowArray();
        $this->assertNotNull($record, "Record with ID {$recordId} not found in {$table}");
        $this->assertArrayHasKey($column, $record, "Column {$column} not found in record");
        
        $actualData = json_decode($record[$column], true) ?: [];
        $defaultMessage = "JSON column {$column} in {$table} record {$recordId} does not contain expected data";
        
        foreach ($expectedData as $key => $value) {
            $this->assertArrayHasKey($key, $actualData, "Key '{$key}' not found in JSON data");
            $this->assertEquals($value, $actualData[$key], "Value for key '{$key}' does not match");
        }
    }
    
    /**
     * Assert that a record has been soft deleted (deleted_at is not null)
     *
     * @param string $table Table name
     * @param int $recordId Record ID
     * @param string $message Custom error message
     */
    public function assertDatabaseSoftDeleted(string $table, int $recordId, string $message = ''): void
    {
        $record = $this->db->table($table)->where('id', $recordId)->get()->getRowArray();
        $this->assertNotNull($record, "Record with ID {$recordId} not found in {$table}");
        $this->assertArrayHasKey('deleted_at', $record, "Table {$table} does not have deleted_at column");
        
        $defaultMessage = "Record {$recordId} in {$table} should be soft deleted but deleted_at is null";
        $this->assertNotNull($record['deleted_at'], $message ?: $defaultMessage);
    }
}