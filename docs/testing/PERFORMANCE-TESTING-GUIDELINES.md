---
title: "Performance Testing Guidelines"
id: "PERFORMANCE-TESTING-01"
version: "1.0"
status: "Active"
module: "Testing"
type: "Guideline"
tags: ["performance", "testing", "benchmarks", "optimization", "assertions"]
purpose: "Provides comprehensive performance testing standards, benchmarks, and guidelines for backend services"
location: "docs/testing"
updated: "2025-12-04"
changes: "Created comprehensive performance testing guidelines with PerformanceAssertions trait"
related_to:
  - id: "TESTING-RULES-01"
    description: "Main testing rules and guidelines"
  - id: "BACKEND-TESTING-01"
    description: "Backend testing guide with performance requirements"
  - id: "ASSERTION-REFERENCE-01"
    description: "Comprehensive assertion reference guide"
---

# Performance Testing Guidelines

## 🎯 Performance Testing Overview

Performance testing ensures that your application meets acceptable speed, responsiveness, and stability requirements under various load conditions. This guide provides standards, benchmarks, and implementation patterns for performance testing in the LANO CRM system.

## 📊 Performance Assertions Trait

### Location
`backend-ci/tests/_support/Assertions/PerformanceAssertions.php`

### Purpose
Provides comprehensive performance testing assertions for:
- Execution time validation
- Memory usage monitoring
- Database query count optimization
- Large dataset performance testing
- Concurrent request handling
- Pagination and search performance

### Core Methods

#### `assertExecutionTime($callback, $maxTimeMs, $message = '')`
Validates that a callback executes within specified time limit.

**Usage:**
```php
$this->assertExecutionTime(function() {
    return $this->service->list(['page' => 1, 'limit' => 50]);
}, 500); // Max 500ms
```

#### `assertMemoryUsage($callback, $limitMb, $message = '')`
Validates that memory usage stays within acceptable limits.

**Usage:**
```php
$this->assertMemoryUsage(function() {
    $result = $this->service->processLargeDataset();
    return $result;
}, 50); // Max 50MB
```

#### `assertQueryCount($callback, $maxQueries, $message = '')`
Validates database query count to prevent N+1 problems.

**Usage:**
```php
$this->assertQueryCount(function() {
    return $this->service->listWithRelations(['page' => 1, 'limit' => 50]);
}, 5); // Max 5 queries
```

#### `assertLargeDatasetPerformance($callback, $size, $maxTimeMs = 500, $message = '')`
Tests performance with large datasets.

**Usage:**
```php
$this->assertLargeDatasetPerformance(function($testData) {
    foreach ($testData as $item) {
        $this->service->create($item);
    }
    return $this->service->list(['page' => 1, 'limit' => 100]);
}, 1000, 2000); // 1000 records, max 2000ms
```

#### `assertConcurrentRequests($callback, $concurrency, $maxTimeMs, $message = '')`
Tests concurrent operation performance.

**Usage:**
```php
$this->assertConcurrentRequests(function($params) {
    return $this->service->create([
        'code' => 'CONC' . $params['iteration'],
        'name' => 'Concurrent Product ' . $params['iteration']
    ]);
}, 5, 3000); // 5 concurrent operations, max 3000ms
```

## 🎯 Performance Benchmarks

### Repository Layer Performance Standards

| Operation | Dataset Size | Max Time | Max Memory | Max Queries |
|-----------|--------------|-----------|-------------|--------------|
| `findAll()` (basic) | 100 records | 200ms | 3 |
| `findAll()` (with search) | 100 records | 300ms | 5 |
| `findAll()` (with relations) | 100 records | 400ms | 8 |
| `findById()` | Single record | 50ms | 2 |
| `create()` | Single record | 100ms | 3 |
| `update()` | Single record | 150ms | 4 |
| `delete()` (soft) | Single record | 100ms | 3 |
| Bulk operations | 1000 records | 2000ms | 15 |

### Service Layer Performance Standards

| Operation | Dataset Size | Max Time | Max Memory | Max Queries |
|-----------|--------------|-----------|-------------|--------------|
| `list()` (basic) | 100 records | 500ms | 8 |
| `list()` (with variants) | 100 records | 800ms | 12 |
| `create()` (with validation) | Single record | 300ms | 6 |
| `update()` (with validation) | Single record | 400ms | 8 |
| `delete()` (with cleanup) | Single record | 350ms | 7 |
| `search()` (complex) | 100 records | 600ms | 10 |
| `export()` (CSV) | 1000 records | 3000ms | 20 |

### API Endpoint Performance Standards

| Endpoint | Method | Dataset Size | Max Time | Max Memory |
|----------|---------|--------------|-----------|-------------|
| `/api/products` | GET (list) | 100 records | 800ms | 64MB |
| `/api/products` | POST (create) | Single record | 500ms | 32MB |
| `/api/products/{id}` | GET (single) | Single record | 300ms | 16MB |
| `/api/products/{id}` | PUT (update) | Single record | 600ms | 32MB |
| `/api/products/{id}` | DELETE | Single record | 400ms | 24MB |
| `/api/products/search` | GET (search) | 100 records | 1000ms | 48MB |

## 🔧 Implementation Patterns

### Basic Performance Test Structure

```php
/**
 * @group performance
 */
public function test_operation_performance(): void
{
    $this->assertExecutionTime(function() {
        // Arrange - Create test data
        $testData = $this->createTestData();
        
        // Act - Perform operation
        $result = $this->service->performOperation($testData);
        
        // Assert - Verify result
        $this->assertNotNull($result);
        return $result;
    }, 500); // Max 500ms
}
```

### Large Dataset Performance Test

```php
/**
 * @group performance
 */
public function test_large_dataset_performance(): void
{
    $this->assertLargeDatasetPerformance(function($testData) {
        // Process large dataset
        foreach ($testData as $item) {
            $this->service->create($item);
        }
        
        // Test performance on large dataset
        return $this->service->list(['page' => 1, 'limit' => 100]);
    }, 1000, 2000); // 1000 records, max 2000ms
}
```

### Memory Usage Test

```php
/**
 * @group performance
 */
public function test_memory_usage(): void
{
    $this->assertMemoryUsage(function() {
        // Create memory-intensive scenario
        $largeDataset = $this->createLargeDataset(500);
        $result = $this->service->processLargeDataset($largeDataset);
        
        return $result;
    }, 50); // Max 50MB
}
```

### Query Optimization Test

```php
/**
 * @group performance
 */
public function test_query_optimization(): void
{
    $this->assertQueryCount(function() {
        // Create test data with relationships
        $this->createTestDataWithRelations(50);
        
        // Test that queries are optimized
        $result = $this->service->listWithRelations(['page' => 1, 'limit' => 50]);
        
        return $result;
    }, 8); // Max 8 queries
}
```

### Concurrent Operations Test

```php
/**
 * @group performance
 */
public function test_concurrent_operations(): void
{
    $this->assertConcurrentRequests(function($params) {
        return $this->service->create([
            'code' => 'CONC' . $params['iteration'],
            'name' => 'Concurrent Product ' . $params['iteration'],
            'selling_price' => 100 + $params['iteration']
        ]);
    }, 5, 3000); // 5 concurrent operations, max 3000ms
}
```

## 📋 Performance Test Categories

### 1. Execution Time Tests
- **Purpose**: Validate response time requirements
- **Tools**: `assertExecutionTime()`
- **Focus**: Critical paths and user-facing operations

### 2. Memory Usage Tests
- **Purpose**: Prevent memory leaks and excessive consumption
- **Tools**: `assertMemoryUsage()`
- **Focus**: Large dataset processing and batch operations

### 3. Query Optimization Tests
- **Purpose**: Prevent N+1 query problems and inefficient queries
- **Tools**: `assertQueryCount()`
- **Focus**: Database operations with relationships

### 4. Large Dataset Tests
- **Purpose**: Ensure scalability with growing data
- **Tools**: `assertLargeDatasetPerformance()`
- **Focus**: Operations that should scale linearly

### 5. Concurrency Tests
- **Purpose**: Validate performance under concurrent load
- **Tools**: `assertConcurrentRequests()`
- **Focus**: Operations that might be accessed simultaneously

## 🚨 Performance Testing Rules

### Mandatory Requirements

1. **All critical operations must have performance tests**
   - API endpoints
   - Service methods
   - Repository queries

2. **Performance tests must use realistic data sizes**
   - Test with actual expected data volumes
   - Include edge cases with maximum expected sizes

3. **Performance thresholds must be documented**
   - Include expected performance in test documentation
   - Justify performance requirements

4. **Performance tests must be isolated**
   - Clean up test data after each test
   - Avoid interference between tests

### Performance Test Naming

```php
// Good naming convention
public function test_product_list_performance_with_1000_records(): void
public function test_product_creation_execution_time_under_300ms(): void
public function test_product_search_memory_usage_under_64mb(): void

// Avoid generic names
public function test_performance(): void  // Too generic
public function test_speed(): void        // Not descriptive
```

### Test Groups

Always tag performance tests with appropriate groups:

```php
/**
 * @group performance
 * @group slow  // For tests that take longer to run
 */
public function test_large_dataset_performance(): void
{
    // Test implementation
}
```

## 🔍 Performance Analysis

### Identifying Performance Issues

1. **Execution Time Issues**
   - Check for inefficient algorithms
   - Look for missing database indexes
   - Identify unnecessary loops or computations

2. **Memory Usage Issues**
   - Check for memory leaks
   - Look for large object retention
   - Identify inefficient data structures

3. **Query Count Issues**
   - Check for N+1 query problems
   - Look for missing eager loading
   - Identify redundant queries

4. **Scalability Issues**
   - Test with increasing data sizes
   - Monitor performance degradation
   - Identify bottlenecks

### Performance Optimization Checklist

- [ ] Database indexes are properly configured
- [ ] Queries use appropriate JOINs instead of multiple queries
- [ ] Caching is implemented where appropriate
- [ ] Pagination is used for large datasets
- [ ] Unnecessary data is not loaded
- [ ] Efficient algorithms are used
- [ ] Memory is properly managed and cleaned up

## 📈 Performance Monitoring

### Continuous Performance Testing

1. **Automated Performance Tests**
   - Run performance tests in CI/CD pipeline
   - Fail builds if performance thresholds are exceeded
   - Track performance trends over time

2. **Performance Regression Detection**
   - Compare current performance against baseline
   - Alert on performance degradation
   - Investigate and fix regressions

3. **Performance Reporting**
   - Generate performance reports
   - Track key metrics over time
   - Identify performance trends

### Performance Metrics to Track

1. **Response Time**
   - Average response time
   - 95th percentile response time
   - Maximum response time

2. **Throughput**
   - Requests per second
   - Records processed per second
   - Concurrent users supported

3. **Resource Usage**
   - CPU usage
   - Memory usage
   - Database query count

4. **Scalability**
   - Performance vs. dataset size
   - Performance vs. concurrent users
   - Resource efficiency

## 🛠️ Performance Testing Tools

### Built-in Tools

1. **PerformanceAssertions Trait**
   - Comprehensive performance testing assertions
   - Memory and execution time monitoring
   - Query count validation

2. **PHPUnit Configuration**
   - Timeout settings for different test sizes
   - Memory limits for test execution
   - Performance test groups

### External Tools (Optional)

1. **Blackfire.io**
   - Advanced performance profiling
   - Detailed call graph analysis
   - Performance timeline visualization

2. **Xdebug Profiler**
   - Function-level profiling
   - Memory usage tracking
   - Execution time analysis

3. **MySQL Query Log**
   - Query execution analysis
   - Index usage optimization
   - Query performance tuning

## 📚 Best Practices

### Test Design

1. **Use realistic data sizes**
   - Test with expected production data volumes
   - Include edge cases with maximum sizes
   - Avoid artificially small test datasets

2. **Test critical paths**
   - Focus on user-facing operations
   - Test frequently used functionality
   - Include performance-critical features

3. **Measure consistently**
   - Use the same measurement approach
   - Run tests multiple times for accuracy
   - Account for system variations

### Performance Optimization

1. **Database Optimization**
   - Add appropriate indexes
   - Use efficient query patterns
   - Implement query caching

2. **Code Optimization**
   - Use efficient algorithms
   - Minimize memory usage
   - Implement caching strategies

3. **Architecture Optimization**
   - Use appropriate design patterns
   - Implement lazy loading
   - Consider asynchronous processing

### Continuous Improvement

1. **Regular Performance Reviews**
   - Review performance test results
   - Identify optimization opportunities
   - Plan performance improvements

2. **Performance Budgeting**
   - Set performance budgets for features
   - Track performance against budgets
   - Enforce performance standards

3. **Performance Culture**
   - Make performance a team responsibility
   - Include performance in code reviews
   - Celebrate performance improvements

---

## 🚀 Getting Started

### 1. Add PerformanceAssertions to Your Test

```php
use Tests\Support\Assertions\PerformanceAssertions;

class YourServiceTest extends CIUnitTestCase
{
    use PerformanceAssertions;
    
    // Your test methods
}
```

### 2. Write Your First Performance Test

```php
/**
 * @group performance
 */
public function test_critical_operation_performance(): void
{
    $this->assertExecutionTime(function() {
        return $this->service->criticalOperation();
    }, 500); // Max 500ms
}
```

### 3. Run Performance Tests

```bash
# Run all performance tests
docker exec meomeo2-api-1 vendor/bin/phpunit --group performance

# Run specific performance test
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/YourServiceTest.php --filter test_critical_operation_performance
```

### 4. Analyze Results

- Check test output for performance violations
- Review execution times and memory usage
- Identify optimization opportunities

---

**Remember**: Performance testing is not optional - it's essential for maintaining a responsive, scalable application. Always include performance tests for critical operations and monitor performance trends over time.