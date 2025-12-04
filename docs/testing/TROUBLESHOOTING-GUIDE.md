---
title: "Testing Troubleshooting Guide"
id: "TROUBLESHOOTING-01"
version: "4.0"
status: "Active"
module: "Testing"
type: "Guide"
tags: ["testing", "troubleshooting", "debugging", "common-issues", "solutions"]
purpose: "Comprehensive troubleshooting guide for common testing issues, database problems, test execution failures, and performance optimization."
location: "docs/testing"
updated: "2025-12-03"
changes: "Updated YAML frontmatter for documentation consolidation"
related_to:
  - id: "BACKEND-TESTING-01"
    description: "Backend testing guide with test database only"
  - id: "FRONTEND-TESTING-01"
    description: "Frontend testing guide with real database integration"
  - id: "TESTING-PATTERNS-01"
    description: "Testing patterns and assertions reference"
  - id: "TEST-CHECKLIST-01"
    description: "Mandatory checklist for all testing changes"
---

# Testing Troubleshooting Guide - LANO CRM

## 📋 Mục Lục

1. [Common Issues](#common-issues)
2. [Database Problems](#database-problems)
3. [Test Execution Issues](#test-execution-issues)
4. [Coverage Issues](#coverage-issues)
5. [Mutation Testing Issues](#mutation-testing-issues)
6. [Performance Issues](#performance-issues)
7. [Debug Commands](#debug-commands)

---

## 🚨 Common Issues

### 1. Tests Fail Randomly

#### Symptoms
- Tests pass khi chạy riêng nhưng fail khi chạy cùng nhau
- Flaky tests - đôi khi pass, đôi khi fail
- Inconsistent behavior giữa các runs

#### Causes và Solutions

```php
// Problem: Test dependency
class TestA extends CIUnitTestCase
{
    public function testCreatesUser(): void
    {
        UserFactory::create(['email' => 'test@example.com']);
    }
}

class TestB extends CIUnitTestCase
{
    public function testCreatesUser(): void
    {
        UserFactory::create(['email' => 'test@example.com']);
    }
}

// Solution: Proper isolation
class TestA extends CIUnitTestCase
{
    use DevDatabaseTrait;
    
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
    
    public function testCreatesUser(): void
    {
        UserFactory::create(['email' => 'test@example.com']);
    }
}
```

#### Debug Steps
```bash
vendor/bin/phpunit --verbose tests/Unit/ProblematicTest.php
vendor/bin/phpunit --filter testMethodName
vendor/bin/phpunit --list-tests
```

### 2. Memory Issues

#### Symptoms
- Fatal error: Allowed memory size exhausted
- Tests become slow over time
- Out of memory errors

#### Solutions

```php
// Solution 1: Increase memory limit
// phpunit.xml
<php>
    <ini name="memory_limit" value="-1"/>
</php>

// Solution 2: Cleanup trong tests
class LargeTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        unset($this->largeObject);
        gc_collect_cycles();
        parent::tearDown();
    }
}

// Solution 3: Use data providers
class LargeTest extends CIUnitTestCase
{
    public function largeDataProvider(): array
    {
        return [
            ['data' => ['chunk1']],
            ['data' => ['chunk2']],
        ];
    }
}
```

### 3. Timeout Issues

#### Symptoms
- Tests take too long to complete
- CI/CD timeouts
- Browser tests hang

#### Solutions

```php
// Solution 1: Mock slow operations
class SlowTest extends CIUnitTestCase
{
    public function testSlowOperation(): void
    {
        $this->mock(ExternalService::class)
            ->shouldReceive('fetchData')
            ->andReturn(['mocked' => 'data']);
    }
}

// Solution 2: Use SQLite cho unit tests
// phpunit.xml.dist
<php>
    <env name="database.default.group" value="tests"/>
    <env name="database.tests.group" value="sqlite"/>
    <env name="database.tests.database" value=":memory:"/>
</php>
```

---

## 🗄️ Database Problems

### 1. Connection Issues

#### Symptoms
- SQLSTATE[HY000] [2002] Connection refused
- Access denied for user
- Database not found

#### Debug Commands

```bash
docker ps | grep mysql
docker exec meomeo2-api-1 php spark db:info
docker exec meomeo2-api-1 php -r "
try {
    \$db = \Config\Database::connect();
    echo 'Database connection: OK\n';
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
}
"
```

#### Solutions

```bash
docker-compose restart db
cat backend-ci/.env | grep database
docker-compose down -v
docker-compose up -d db
docker exec meomeo2-api-1 php spark migrate --all
```

### 2. Migration Issues

#### Symptoms
- Table doesn't exist
- Column not found
- Foreign key constraint fails

#### Debug Commands

```bash
docker exec meomeo2-api-1 php spark migrate:status
docker exec meomeo2-api-1 php spark migrate --all
docker exec meomeo2-api-1 php spark migrate:fresh --all
```

#### Solutions

```php
// Solution: Use schema traits
class ProductTest extends CIUnitTestCase
{
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductSchema();
    }
}
```

### 3. Transaction Issues

#### Symptoms
- Data persists giữa tests
- Rollback không hoạt động
- Deadlock errors

#### Solutions

```php
// Solution: Proper transaction handling
class TransactionTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    
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
    
    public function testTransactionRollback(): void
    {
        $product = ProductFactory::create();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
```

---

## 🧪 Test Execution Issues

### 1. PHPUnit Configuration Issues

#### Symptoms
- Class not found
- Autoloader issues
- Configuration errors

#### Debug Commands

```bash
vendor/bin/phpunit --version
vendor/bin/phpunit --configuration phpunit.xml --debug
composer dump-autoload
```

#### Solutions

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory suffix=".php">app</directory>
        </include>
        <exclude>
            <directory>app/Database/Migrations</directory>
        </exclude>
    </coverage>
    
    <php>
        <env name="CI" value="true"/>
        <ini name="memory_limit" value="-1"/>
        <ini name="error_reporting" value="E_ALL"/>
    </php>
</phpunit>
```

### 2. Test Class Issues

#### Symptoms
- Class not found in test
- Method not found
- Inheritance issues

#### Solutions

```php
<?php

namespace Tests\Unit;

use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductSchemaTrait;
use Tests\_support\Factories\ProductFactory;
use App\Services\ProductService;

class ProductServiceTest extends \CodeIgniter\Test\CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;
    
    protected ProductService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductSchema();
        $this->service = new ProductService();
    }
    
    public function testCreatesProductWithValidData(): void
    {
        $productData = ProductFactory::definition();
        $product = $this->service->create($productData);
        
        $this->assertInstanceOf(\App\Models\Product::class, $product);
        $this->assertNotNull($product->id);
    }
}
```

### 3. Assertion Issues

#### Symptoms
- Assertion fails unexpectedly
- Type mismatch errors
- Comparison issues

#### Solutions

```php
public function testPriceCalculation(): void
{
    $product = ProductFactory::create(['price' => 100.00]);
    $finalPrice = $this->service->calculateFinalPrice($product);
    
    $this->assertEqualsWithDelta(110.00, $finalPrice, 0.01);
    $this->assertSame('active', $product->status);
    
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'price' => 100.00
    ]);
}
```

---

## 📊 Coverage Issues

### 1. Low Coverage

#### Symptoms
- Coverage percentage thấp
- Lines not covered
- Missing coverage reports

#### Debug Commands

```bash
vendor/bin/phpunit --coverage-html=coverage/html
vendor/bin/phpunit --coverage-text --filter testSpecificMethod
vendor/bin/phpunit --coverage-text=coverage.txt
```

#### Solutions

```php
class UncoveredCodeTest extends CIUnitTestCase
{
    public function testErrorHandlingPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->methodThatThrows();
    }
    
    public function testEdgeCases(): void
    {
        $this->service->processValue(0);
        $this->service->processValue(100);
        $this->service->processValue(-1);
    }
}
```

### 2. Coverage Generation Issues

#### Symptoms
- No coverage report generated
- Xdebug not working
- Permission issues

#### Solutions

```bash
php -m | grep xdebug
sudo apt-get install php-xdebug
php -i | grep xdebug
vendor/bin/phpunit --coverage-clover=coverage/clover.xml --debug
```

```xml
<coverage>
    <include>
        <directory suffix=".php">app</directory>
    </include>
    <exclude>
        <directory>app/Database/Migrations</directory>
        <directory>app/Language</directory>
        <file>app/Controllers/BaseController.php</file>
    </exclude>
    <report>
        <html outputDirectory="coverage/html"/>
        <clover outputFile="coverage/clover.xml"/>
        <text outputFile="coverage/coverage.txt"/>
    </report>
</coverage>
```

---

## 🧬 Mutation Testing Issues

### 1. Infection Configuration Issues

#### Symptoms
- Infection fails to run
- Configuration errors
- No mutants generated

#### Debug Commands

```bash
vendor/bin/infection --version
vendor/bin/infection --configuration=infection.json.dist --dry-run
vendor/bin/infection --configuration=infection.json.dist --debug
```

#### Solutions

```json
{
    "$schema": "https://raw.githubusercontent.com/infection/infection/0.27.0/resources/schema.json",
    "source": {
        "directories": [
            "app/Services",
            "app/Repositories",
            "app/Validators"
        ]
    },
    "logs": {
        "text": "infection.log",
        "html": "infection.html"
    },
    "phpUnit": {
        "configDir": ".",
        "customPath": "vendor/bin/phpunit"
    },
    "mutators": {
        "@default": true,
        "global-ignore": [
            "DocBlock",
            "PublicVisibility"
        ]
    },
    "minMsi": 80.0,
    "threads": 4
}
```

### 2. Low Mutation Score

#### Symptoms
- MSI score thấp
- Many escaped mutants
- False positives

#### Solutions

```php
class MutationTest extends CIUnitTestCase
{
    public function testSpecificBehavior(): void
    {
        $result = $this->service->calculate(100, 10);
        $this->assertEquals(110, $result);
    }
    
    public function testErrorConditions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->calculate(-100, 10);
    }
}
```

---

## ⚡ Performance Issues

### 1. Slow Tests

#### Symptoms
- Tests take too long
- CI/CD timeouts
- Development feedback slow

#### Solutions

```php
// Solution 1: Use SQLite cho unit tests
class FastUnitTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect('sqlite');
    }
}

// Solution 2: Mock external services
class FastTest extends CIUnitTestCase
{
    public function testExternalApi(): void
    {
        $this->mock(ExternalApi::class)
            ->shouldReceive('getData')
            ->andReturn(['mocked' => 'data']);
    }
}

// Solution 3: Optimize data setup
class OptimizedTest extends CIUnitTestCase
{
    public function testWithMinimalData(): void
    {
        $product = ProductFactory::make(['name' => 'Test']);
        $result = $this->service->processProduct($product);
    }
}
```

### 2. Memory Leaks

#### Symptoms
- Memory usage increases over time
- Out of memory errors
- Performance degradation

#### Solutions

```php
class MemoryLeakTest extends CIUnitTestCase
{
    private array $largeObjects = [];
    
    protected function tearDown(): void
    {
        foreach ($this->largeObjects as $object) {
            unset($object);
        }
        $this->largeObjects = [];
        gc_collect_cycles();
        parent::tearDown();
    }
    
    public function testLargeDataProcessing(): void
    {
        $chunks = array_chunk($this->largeDataset, 100);
        
        foreach ($chunks as $chunk) {
            $result = $this->service->processChunk($chunk);
            unset($result);
        }
    }
}
```

---

## 🔧 Debug Commands

### General Debugging

```bash
vendor/bin/phpunit --verbose tests/Unit/ProblematicTest.php
vendor/bin/phpunit --debug tests/Unit/ProblematicTest.php
vendor/bin/phpunit --filter testMethodName
vendor/bin/phpunit --stop-on-failure tests/Unit/ProblematicTest.php
php -l tests/Unit/ProblematicTest.php
vendor/bin/phpunit --list-tests
```

### Database Debugging

```bash
docker exec meomeo2-api-1 php spark db:info
docker exec meomeo2-api-1 php spark migrate:status
docker exec meomeo2-api-1 php spark migrate:fresh --all
docker exec meomeo2-db-1 mysql -u root -proot -e "DESCRIBE lanocrm_test.products;"
```

### Coverage Debugging

```bash
vendor/bin/phpunit --coverage-html=coverage/html
vendor/bin/phpunit --coverage-text --filter testSpecificMethod
vendor/bin/phpunit --coverage-text=coverage.txt
php -m | grep xdebug
php -i | grep xdebug
```

### Mutation Testing Debugging

```bash
vendor/bin/infection --configuration=infection.json.dist --debug
vendor/bin/infection --configuration=infection.json.dist --dry-run
vendor/bin/infection --mutators=Boolean,Arithmetic
vendor/bin/infection --configuration=infection.json.dist --html
```

---

## 📋 Quick Reference

### Common Error Messages và Solutions

| Error | Cause | Solution |
|--------|-------|----------|
| `Class 'Tests\Support\Database\DevDatabaseTrait' not found` | Autoloader issue | Run `composer dump-autoload` |
| `SQLSTATE[HY000] [2002] Connection refused` | Database not running | `docker-compose up -d db` |
| `Allowed memory size exhausted` | Memory limit | Increase memory limit or cleanup objects |
| `Table doesn't exist` | Migration issue | Run migrations or use schema traits |
| `Test failed but no output` | Assertion issue | Use verbose mode `--verbose` |
| `Coverage not generated` | Xdebug missing | Install Xdebug extension |
| `Infection: No mutants generated` | Source config issue | Check source directories in config |

### Performance Tips

1. **Use SQLite** cho unit tests
2. **Mock external services** khi có thể
3. **Optimize data setup** - chỉ tạo cần thiết
4. **Use transactions** cho fast cleanup
5. **Parallel execution** với `--parallel` flag

### Best Practices

1. **Isolate tests** - không dependency lẫn nhau
2. **Use descriptive names** cho test methods
3. **Test one thing** per test method
4. **Clean up properly** trong tearDown
5. **Use factories** cho test data generation

---

*Document last updated: 2025-12-03*
*Version: 1.0*