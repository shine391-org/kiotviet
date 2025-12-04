<?php

namespace Tests;

use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Assertions\PerformanceAssertions;
use CodeIgniter\Test\CIUnitTestCase;
use App\Models\ProductModel;

/**
 * PerformanceAssertions Test
 * 
 * Tests the PerformanceAssertions trait to ensure all performance validation methods work correctly.
 * This test validates the trait itself, not application code.
 */
class PerformanceAssertionsTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use PerformanceAssertions;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }
    
    /**
     * Test assertPerformanceExecutionTime method
     */
    public function testAssertPerformanceExecutionTime(): void
    {
        // Test with a fast operation (should pass)
        $this->assertExecutionTime(function() {
            usleep(1000); // 1ms
            return 'fast operation';
        }, 100); // 100ms limit (more realistic)
        
        // Test with a slow operation (should fail)
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertExecutionTime(function() {
            usleep(50000); // 50ms
            return 'slow operation';
        }, 20); // 20ms limit (more realistic)
    }
    
    /**
     * Test assertPerformanceMemoryUsage method
     */
    public function testAssertPerformanceMemoryUsage(): void
    {
        // Test with low memory usage (should pass)
        $this->assertPerformanceMemoryUsage(function() {
            $data = [];
            for ($i = 0; $i < 100; $i++) {
                $data[] = str_repeat('x', 100);
            }
            return $data;
        }, 20); // 20MB limit (more realistic)
        
        // Test with high memory usage (should fail)
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertPerformanceMemoryUsage(function() {
            $data = [];
            for ($i = 0; $i < 10000; $i++) {
                $data[] = str_repeat('x', 1000);
            }
            return $data;
        }, 5); // 5MB limit (more realistic)
    }
    
    /**
     * Test assertQueryCount method
     */
    public function testAssertQueryCount(): void
    {
        // Test with simple query (should pass)
        $this->assertQueryCount(function() {
            $model = new ProductModel();
            return $model->findAll(1); // Should be 1 query
        }, 5); // Allow up to 5 queries
        
        // Test with operation that might exceed limit (should fail)
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertQueryCount(function() {
            $model = new ProductModel();
            $results = [];
            for ($i = 0; $i < 10; $i++) {
                $results[] = $model->findAll(1); // Multiple queries
            }
            return $results;
        }, 1); // Only allow 1 query
    }
    
    /**
     * Test assertPerformanceLargeDataset method
     */
    public function testAssertPerformanceLargeDataset(): void
    {
        // Test with reasonable dataset size (should pass)
        $this->assertPerformanceLargeDataset(function($data) {
            // Simple processing of the dataset
            $processed = [];
            foreach ($data as $item) {
                $processed[] = strtoupper($item['name']); // Use name field, not entire array
            }
            return $processed;
        }, 100, 500); // 100 items, 500ms limit (more realistic)
        
        // Test with slow processing (should fail)
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertPerformanceLargeDataset(function($data) {
            // Intentionally slow processing
            $processed = [];
            foreach ($data as $item) {
                usleep(3000); // 3ms per item (slower to ensure failure)
                $processed[] = strtoupper($item['name']); // Use name field, not entire array
            }
            return $processed;
        }, 100, 200); // 100 items, 200ms limit (will fail)
    }
    
    /**
     * Test assertPerformanceConcurrentOperations method
     */
    public function testAssertPerformanceConcurrentOperations(): void
    {
        // Test with reasonable concurrency (should pass)
        $this->assertConcurrentRequests(function() {
            // Simulate concurrent operation
            usleep(10000); // 10ms
            return 'concurrent result';
        }, 5, 200); // 5 concurrent operations, 200ms limit (more realistic)
        
        // Test with slow concurrent operation (should fail)
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertConcurrentRequests(function() {
            // Simulate slow concurrent operation
            usleep(100000); // 100ms (slower to ensure failure)
            return 'slow concurrent result';
        }, 5, 300); // 5 concurrent operations, 300ms limit (will fail)
    }
    
    /**
     * Test assertIndexUsage method
     */
    public function testAssertIndexUsage(): void
    {
        // This test is more complex as it requires actual database queries
        // For now, we'll test the basic functionality
        $this->assertIndexUsage(function() {
            // Simple database operation
            $model = new ProductModel();
            return $model->findAll(1);
        }, []); // No specific index requirements for this test
    }
    
    /**
     * Test assertNoFullTableScans method
     */
    public function testAssertNoFullTableScans(): void
    {
        // Test with simple query (should pass)
        $this->assertNoFullTableScans(function() {
            $model = new ProductModel();
            return $model->findAll(1);
        });
    }
    
    /**
     * Test assertQueryExecutionTime method
     */
    public function testAssertQueryExecutionTime(): void
    {
        // Test with fast query (should pass)
        $this->assertQueryExecutionTime(function() {
            $model = new ProductModel();
            return $model->findAll(1);
        }, 1000); // 1000ms limit
        
        // Note: Testing slow queries is difficult without actual slow queries
        // This would require setting up a scenario with intentionally slow queries
    }
    
    /**
     * Test generateLargeDataset helper method
     */
    public function testGenerateLargeDataset(): void
    {
        $dataset = $this->generateLargeDataset(10);
        
        $this->assertIsArray($dataset);
        $this->assertCount(10, $dataset);
        
        // Check structure of generated data
        foreach ($dataset as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('code', $item);
            $this->assertArrayHasKey('selling_price', $item);
            $this->assertArrayHasKey('status', $item);
            $this->assertArrayHasKey('product_type', $item);
        }
    }
    
    /**
     * Test that performance assertions properly validate operation success
     */
    public function testPerformanceAssertionsValidateOperationSuccess(): void
    {
        // Test that assertions fail when operation returns null
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('should return a result');
        
        $this->assertExecutionTime(function() {
            return null; // Operation returns null
        }, 100);
    }
    
    /**
     * Test that performance assertions handle exceptions properly
     */
    public function testPerformanceAssertionsHandleExceptions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test exception');
        
        $this->assertExecutionTime(function() {
            throw new \RuntimeException('Test exception');
        }, 100);
    }
}