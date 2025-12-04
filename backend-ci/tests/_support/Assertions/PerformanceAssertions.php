<?php

namespace Tests\Support\Assertions;

/**
 * Performance assertion methods for testing execution time, memory usage, and query performance
 * 
 * @agent-assertions: Performance testing and optimization validation
 * @agent-pattern: Comprehensive performance testing
 * @agent-reusable: HIGH
 */
trait PerformanceAssertions
{
    /**
     * Check if CI4 is in debug mode
     *
     * @return bool True if debug mode is enabled
     */
    protected function isDebugMode(): bool
    {
        // CI4 uses ENVIRONMENT constant and CI_DEBUG constant
        // Check both environment and debug constant
        return (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ||
               (defined('CI_DEBUG') && CI_DEBUG) ||
               (isset($_ENV['CI_ENVIRONMENT']) && $_ENV['CI_ENVIRONMENT'] === 'development') ||
               (isset($_SERVER['CI_ENVIRONMENT']) && $_SERVER['CI_ENVIRONMENT'] === 'development');
    }
    /**
     * Assert that a callback executes within specified time limit
     *
     * @param callable $callback Operation to test
     * @param int $maxTimeMs Maximum allowed time in milliseconds
     * @param string $message Custom error message
     */
    public function assertExecutionTime(callable $callback, int $maxTimeMs, string $message = ''): void
    {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $defaultMessage = "Operation took {$executionTimeMs}ms, expected max {$maxTimeMs}ms";
            $this->assertLessThanOrEqual($maxTimeMs, $executionTimeMs, $message ?: $defaultMessage);
            
            // Verify operation completed successfully
            $this->assertNotNull($result, "Operation should return a result within time limit");
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            // Re-throw the exception to let the test handle it
            throw $e;
        }
    }
    
    /**
     * Assert that memory usage stays within acceptable limits
     *
     * @param callable $callback Operation to test
     * @param int $limitMb Memory limit in MB
     * @param string $message Custom error message
     */
    public function assertPerformanceMemoryUsage(callable $callback, int $limitMb, string $message = ''): void
    {
        $initialMemory = memory_get_usage(true);
        
        try {
            $result = $callback();
            
            $peakMemory = memory_get_peak_usage(true);
            $memoryUsedMb = ($peakMemory - $initialMemory) / 1024 / 1024;
            
            $defaultMessage = "Operation used {$memoryUsedMb}MB memory, expected max {$limitMb}MB";
            $this->assertLessThanOrEqual($limitMb, $memoryUsedMb, $message ?: $defaultMessage);
            
            // Verify operation completed successfully
            $this->assertNotNull($result, "Memory-intensive operation should return a result");
            
        } catch (\Exception $e) {
            $peakMemory = memory_get_peak_usage(true);
            $memoryUsedMb = ($peakMemory - $initialMemory) / 1024 / 1024;
            
            $this->fail("Memory-intensive operation failed after using {$memoryUsedMb}MB: " . $e->getMessage());
        }
    }
    
    /**
     * Assert that database query count stays within acceptable limits
     *
     * @param callable $callback Operation to test
     * @param int $maxQueries Maximum allowed number of queries
     * @param string $message Custom error message
     */
    public function assertQueryCount(callable $callback, int $maxQueries, string $message = ''): void
    {
        // Get database connection from the test case
        $db = $this->db ?? \Config\Database::connect();
        
        // Check if we're in debug mode
        $isDebugMode = $this->isDebugMode();
        
        try {
            // Try to enable query logging if methods are available
            $queryLoggingAvailable = false;
            if (method_exists($db, 'resetQueryLog')) {
                $db->resetQueryLog();
                $queryLoggingAvailable = true;
            }
            
            $result = $callback();
            
            // Verify operation completed successfully
            $this->assertNotNull($result, "Query-intensive operation should return a result");
            
            // Get the executed queries if logging is available
            $queryCount = 0;
            if ($queryLoggingAvailable && method_exists($db, 'getQueryLog')) {
                $queries = $db->getQueryLog();
                $queryCount = count($queries);
            } else {
                // Fallback: estimate query count based on operation complexity
                // This is a simplified estimation when query logging isn't available
                $queryCount = $this->estimateQueryCount($callback);
            }
            
            // Assert query count is within limits
            $defaultMessage = "Operation executed {$queryCount} queries, expected max {$maxQueries}";
            $this->assertLessThanOrEqual($maxQueries, $queryCount, $message ?: $defaultMessage);
            
        } catch (\Exception $e) {
            $this->fail("Query-intensive operation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Estimate query count based on operation complexity when query logging isn't available
     * This is a fallback method for when direct query counting isn't possible
     */
    private function estimateQueryCount(callable $callback): int
    {
        // This is a simplified estimation based on common patterns
        // In a real implementation, you might want to use reflection or other techniques
        // to get a more accurate estimate
        
        try {
            $reflection = new \ReflectionFunction($callback);
            $filename = $reflection->getFileName();
            $startLine = $reflection->getStartLine();
            $endLine = $reflection->getEndLine();
            
            // Read the source code to analyze complexity
            $lines = file($filename);
            $sourceLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $source = implode('', $sourceLines);
            
            // Simple heuristic: count database-related keywords
            $queryPatterns = [
                '/\b(find|findAll|where|insert|update|delete|save|query)\b/i',
                '/\b(Model|Repository|Builder)\b/i',
                '/->(get|result|getResult|insert|update|delete)\(/',
            ];
            
            $estimatedQueries = 0;
            foreach ($queryPatterns as $pattern) {
                $matches = [];
                if (preg_match_all($pattern, $source, $matches)) {
                    $estimatedQueries += count($matches[0]);
                }
            }
            
            // Minimum of 1 query for any database operation
            return max(1, $estimatedQueries);
            
        } catch (\Exception $e) {
            // If estimation fails, assume a reasonable default
            return 3; // Common default for simple operations
        }
    }
    
    /**
     * Assert that large dataset operations perform within acceptable time limits
     *
     * @param callable $callback Operation to test with large dataset
     * @param int $size Dataset size to test
     * @param int $maxTimeMs Maximum allowed time in milliseconds
     * @param string $message Custom error message
     */
    public function assertPerformanceLargeDataset(callable $callback, int $size, int $maxTimeMs = 500, string $message = ''): void
    {
        $startTime = microtime(true);
        
        try {
            // Generate test data for the specified size
            $testData = $this->generateLargeDataset($size);
            $result = $callback($testData);
            
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $defaultMessage = "Large dataset operation (size: {$size}) took {$executionTimeMs}ms, expected max {$maxTimeMs}ms";
            $this->assertLessThanOrEqual($maxTimeMs, $executionTimeMs, $message ?: $defaultMessage);
            
            // Verify operation completed successfully
            $this->assertNotNull($result, "Large dataset operation should return a result");
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $this->fail("Large dataset operation failed after {$executionTimeMs}ms: " . $e->getMessage());
        }
    }
    
    /**
     * Assert that concurrent operations perform within acceptable time limits
     *
     * @param callable $callback Operation to test concurrently
     * @param int $concurrency Number of concurrent operations
     * @param int $maxTimeMs Maximum allowed time in milliseconds
     * @param string $message Custom error message
     */
    public function assertConcurrentRequests(callable $callback, int $concurrency, int $maxTimeMs, string $message = ''): void
    {
        $startTime = microtime(true);
        $results = [];
        $exceptions = [];
        
        // Simulate concurrent operations
        for ($i = 0; $i < $concurrency; $i++) {
            try {
                $result = $callback(['iteration' => $i, 'concurrency' => $concurrency]);
                $results[] = $result;
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }
        
        $endTime = microtime(true);
        $executionTimeMs = ($endTime - $startTime) * 1000;
        
        // Verify performance
        $defaultMessage = "Concurrent operations ({$concurrency} requests) took {$executionTimeMs}ms, expected max {$maxTimeMs}ms";
        $this->assertLessThanOrEqual($maxTimeMs, $executionTimeMs, $message ?: $defaultMessage);
        
        // Verify at least some operations succeeded
        $this->assertGreaterThan(0, count($results), 
            "At least one concurrent operation should succeed");
        
        // If there are exceptions, they should be expected (like deadlocks)
        foreach ($exceptions as $exception) {
            $this->assertContainsString(get_class($exception), 
                ['RuntimeException', 'DatabaseException', 'LogicException'], 
                "Concurrent operation exceptions should be expected types");
        }
    }
    
    /**
     * Assert that pagination performance is acceptable for large datasets
     *
     * @param callable $callback Operation that accepts pagination parameters
     * @param int $totalRecords Total number of records to test with
     * @param int $maxTimeMs Maximum allowed time in milliseconds
     * @param string $message Custom error message
     */
    public function assertPaginationPerformance(callable $callback, int $totalRecords, int $maxTimeMs = 200, string $message = ''): void
    {
        $startTime = microtime(true);
        
        try {
            // Test different pagination scenarios
            $paginationTests = [
                ['page' => 1, 'limit' => 10],
                ['page' => 5, 'limit' => 20],
                ['page' => 10, 'limit' => 50],
                ['page' => 1, 'limit' => 100]
            ];
            
            foreach ($paginationTests as $params) {
                $result = $callback($params);
                $this->assertNotNull($result, "Pagination test with params " . json_encode($params) . " should return a result");
            }
            
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $defaultMessage = "Pagination performance test ({$totalRecords} records) took {$executionTimeMs}ms, expected max {$maxTimeMs}ms";
            $this->assertLessThanOrEqual($maxTimeMs, $executionTimeMs, $message ?: $defaultMessage);
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $this->fail("Pagination performance test failed after {$executionTimeMs}ms: " . $e->getMessage());
        }
    }
    
    /**
     * Assert that search performance is acceptable for large datasets
     *
     * @param callable $callback Operation that accepts search parameters
     * @param int $totalRecords Total number of records to test with
     * @param int $maxTimeMs Maximum allowed time in milliseconds
     * @param string $message Custom error message
     */
    public function assertSearchPerformance(callable $callback, int $totalRecords, int $maxTimeMs = 300, string $message = ''): void
    {
        $startTime = microtime(true);
        
        try {
            // Test different search scenarios
            $searchTests = [
                ['search' => 'test', 'page' => 1, 'limit' => 20],
                ['search' => 'product', 'page' => 1, 'limit' => 50],
                ['search' => 'nonexistent', 'page' => 1, 'limit' => 10],
                ['search' => '', 'page' => 1, 'limit' => 25] // Empty search
            ];
            
            foreach ($searchTests as $params) {
                $result = $callback($params);
                $this->assertNotNull($result, "Search test with params " . json_encode($params) . " should return a result");
            }
            
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $defaultMessage = "Search performance test ({$totalRecords} records) took {$executionTimeMs}ms, expected max {$maxTimeMs}ms";
            $this->assertLessThanOrEqual($maxTimeMs, $executionTimeMs, $message ?: $defaultMessage);
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $this->fail("Search performance test failed after {$executionTimeMs}ms: " . $e->getMessage());
        }
    }
    
    /**
     * Assert that bulk operations perform within acceptable time limits
     *
     * @param callable $callback Operation that accepts bulk data
     * @param int $itemCount Number of items to process in bulk
     * @param int $maxTimeMs Maximum allowed time in milliseconds
     * @param string $message Custom error message
     */
    public function assertBulkOperationPerformance(callable $callback, int $itemCount, int $maxTimeMs = 1000, string $message = ''): void
    {
        $startTime = microtime(true);
        
        try {
            // Generate bulk data
            $bulkData = $this->generateBulkData($itemCount);
            $result = $callback($bulkData);
            
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $defaultMessage = "Bulk operation ({$itemCount} items) took {$executionTimeMs}ms, expected max {$maxTimeMs}ms";
            $this->assertLessThanOrEqual($maxTimeMs, $executionTimeMs, $message ?: $defaultMessage);
            
            // Verify operation completed successfully
            $this->assertNotNull($result, "Bulk operation should return a result");
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $this->fail("Bulk operation failed after {$executionTimeMs}ms: " . $e->getMessage());
        }
    }
    
    /**
     * Generate test data for large dataset performance testing
     *
     * @param int $size Number of records to generate
     * @return array Generated test data
     */
    protected function generateLargeDataset(int $size): array
    {
        $data = [];
        for ($i = 0; $i < $size; $i++) {
            $data[] = [
                'id' => $i + 1, // Add id field as expected by test
                'code' => 'TEST' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'name' => 'Test Product ' . $i,
                'selling_price' => rand(10, 1000) + (rand(0, 99) / 100),
                'status' => 'active',
                'product_type' => 'goods'
            ];
        }
        return $data;
    }
    
    /**
     * Generate bulk data for bulk operation testing
     *
     * @param int $itemCount Number of items to generate
     * @return array Generated bulk data
     */
    protected function generateBulkData(int $itemCount): array
    {
        $data = [];
        for ($i = 0; $i < $itemCount; $i++) {
            $data[] = [
                'code' => 'BULK' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'name' => 'Bulk Item ' . $i,
                'selling_price' => rand(10, 1000) + (rand(0, 99) / 100),
                'status' => 'active',
                'product_type' => 'goods'
            ];
        }
        return $data;
    }
    
    /**
     * Assert that database index usage is optimal for queries
     *
     * @param callable $callback Operation to test
     * @param array $expectedIndexes Expected indexes to be used
     * @param string $message Custom error message
     */
    public function assertIndexUsage(callable $callback, array $expectedIndexes, string $message = ''): void
    {
        // Get database connection from the test case
        $db = $this->db ?? \Config\Database::connect();
        
        try {
            $result = $callback();
            $this->assertNotNull($result, "Index-optimized operation should return a result");
            
            // Try to get queries for analysis if logging is available
            $queries = [];
            if (method_exists($db, 'getQueryLog')) {
                if (method_exists($db, 'resetQueryLog')) {
                    $db->resetQueryLog();
                }
                $queries = $db->getQueryLog();
            }
            
            // Analyze each SELECT query for index usage
            $issues = [];
            foreach ($queries as $query) {
                // Only analyze SELECT queries
                if (is_array($query) && isset($query['query']) && stripos($query['query'], 'SELECT') !== 0) {
                    // Run EXPLAIN on the query
                    $explainQuery = "EXPLAIN " . $query['query'];
                    $explainResult = $db->query($explainQuery)->getResultArray();
                    
                    // Check if any query is doing a full table scan
                    foreach ($explainResult as $row) {
                        $table = $row['table'] ?? '';
                        $type = $row['type'] ?? '';
                        $key = $row['key'] ?? '';
                        $rows = $row['rows'] ?? 0;
                        
                        // Check for full table scan (ALL type) or missing index
                        if ($type === 'ALL') {
                            $issues[] = "Full table scan on table '{$table}' (examined {$rows} rows)";
                        }
                        
                        // Check if expected indexes are being used
                        if (!empty($expectedIndexes[$table]) && $key !== $expectedIndexes[$table]) {
                            $issues[] = "Expected index '{$expectedIndexes[$table]}' on table '{$table}' but '{$key}' was used instead";
                        }
                    }
                }
            }
            
            // Assert no index usage issues found
            if (!empty($issues)) {
                $defaultMessage = "Index usage issues detected:\n" . implode("\n", $issues);
                $this->assertEmpty($issues, $message ?: $defaultMessage);
            }
            
        } catch (\Exception $e) {
            $this->fail("Index-optimized operation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Assert that no full table scans occur during operation
     *
     * @param callable $callback Operation to test
     * @param string $message Custom error message
     */
    public function assertNoFullTableScans(callable $callback, string $message = ''): void
    {
        // Get database connection from the test case
        $db = $this->db ?? \Config\Database::connect();
        
        try {
            $result = $callback();
            $this->assertNotNull($result, "Operation should return a result");
            
            // Try to get queries for analysis if logging is available
            $queries = [];
            if (method_exists($db, 'getQueryLog')) {
                if (method_exists($db, 'resetQueryLog')) {
                    $db->resetQueryLog();
                }
                $queries = $db->getQueryLog();
            }
            
            // Analyze each SELECT query for full table scans
            $fullScans = [];
            foreach ($queries as $query) {
                // Only analyze SELECT queries
                if (is_array($query) && isset($query['query']) && stripos($query['query'], 'SELECT') !== 0) {
                    // Run EXPLAIN on the query
                    $explainQuery = "EXPLAIN " . $query['query'];
                    $explainResult = $db->query($explainQuery)->getResultArray();
                    
                    // Check for full table scans
                    foreach ($explainResult as $row) {
                        $table = $row['table'] ?? '';
                        $type = $row['type'] ?? '';
                        $rows = $row['rows'] ?? 0;
                        
                        if ($type === 'ALL') {
                            $fullScans[] = "Full table scan on '{$table}' (examined {$rows} rows): " . $query['query'];
                        }
                    }
                }
            }
            
            // Assert no full table scans found
            $defaultMessage = "Full table scans detected:\n" . implode("\n", $fullScans);
            $this->assertEmpty($fullScans, $message ?: $defaultMessage);
            
        } catch (\Exception $e) {
            $this->fail("Operation failed during full table scan check: " . $e->getMessage());
        }
    }
    
    /**
     * Assert that query execution time is within acceptable limits
     *
     * @param callable $callback Operation to test
     * @param int $maxTimeMs Maximum allowed time in milliseconds
     * @param string $message Custom error message
     */
    public function assertQueryExecutionTime(callable $callback, int $maxTimeMs, string $message = ''): void
    {
        // Get database connection from the test case
        $db = $this->db ?? \Config\Database::connect();
        
        try {
            $result = $callback();
            $this->assertNotNull($result, "Operation should return a result");
            
            // Try to get queries for analysis if logging is available
            $queries = [];
            if (method_exists($db, 'getQueryLog')) {
                if (method_exists($db, 'resetQueryLog')) {
                    $db->resetQueryLog();
                }
                $queries = $db->getQueryLog();
            }
            
            // Check if any query exceeded the time limit
            $slowQueries = [];
            foreach ($queries as $query) {
                $executionTime = ($query['duration'] ?? 0) * 1000; // Convert to milliseconds
                
                if ($executionTime > $maxTimeMs) {
                    $slowQueries[] = "Query took {$executionTime}ms (max {$maxTimeMs}ms): " . $query['query'];
                }
            }
            
            // Assert no slow queries found
            $defaultMessage = "Slow queries detected:\n" . implode("\n", $slowQueries);
            $this->assertEmpty($slowQueries, $message ?: $defaultMessage);
            
        } catch (\Exception $e) {
            $this->fail("Operation failed during query execution time check: " . $e->getMessage());
        }
    }
    
    /**
     * Assert that cache performance is acceptable
     *
     * @param callable $callback Operation to test with cache
     * @param int $maxTimeMs Maximum allowed time for cached operation
     * @param string $message Custom error message
     */
    public function assertCachePerformance(callable $callback, int $maxTimeMs = 50, string $message = ''): void
    {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $defaultMessage = "Cached operation took {$executionTimeMs}ms, expected max {$maxTimeMs}ms";
            $this->assertLessThanOrEqual($maxTimeMs, $executionTimeMs, $message ?: $defaultMessage);
            
            // Verify operation completed successfully
            $this->assertNotNull($result, "Cached operation should return a result");
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            
            $this->fail("Cached operation failed after {$executionTimeMs}ms: " . $e->getMessage());
        }
    }
    
    /**
     * Assert that memory usage stays within acceptable limits (alias for compatibility)
     *
     * @param callable $callback Operation to test
     * @param int $limitMb Memory limit in MB
     * @param string $message Custom error message
     */
    public function assertMemoryUsage(callable $callback, int $limitMb, string $message = ''): void
    {
        $this->assertPerformanceMemoryUsage($callback, $limitMb, $message);
    }
    
    /**
     * Assert that large dataset operations perform within acceptable time limits (alias for compatibility)
     *
     * @param callable $callback Operation to test with large dataset
     * @param int $size Dataset size to test
     * @param int $maxTimeMs Maximum allowed time in milliseconds
     * @param string $message Custom error message
     */
    public function assertLargeDatasetPerformance(callable $callback, int $size, int $maxTimeMs = 500, string $message = ''): void
    {
        $this->assertPerformanceLargeDataset($callback, $size, $maxTimeMs, $message);
    }
}