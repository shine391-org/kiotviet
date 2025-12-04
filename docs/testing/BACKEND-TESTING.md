---
title: "Backend Testing Guide - Test Database Only"
id: "BACKEND-TESTING-01"
version: "4.0"
status: "Active"
module: "Testing"
type: "Guideline"
tags: ["testing", "backend", "mysql", "test-database-only", "devdatabasetrait", "no-artificial-passing", "wsl"]
purpose: "Provides comprehensive guide for backend testing using dedicated test database only, with strict separation from production data and no artificial test passing."
location: "docs/testing"
updated: "2025-12-03"
changes: "Merged content from BACKEND-TESTING-GUIDELINES.md, added comprehensive patterns, mutation testing, and WSL support"
related_to:
  - id: "TEST-CHECKLIST-01"
    description: "Mandatory checklist for all backend changes"
  - id: "FRONTEND-TESTING-01"
    description: "Frontend testing guide with real database integration"
  - id: "TESTING-RULES-01"
    description: "Comprehensive testing rules and guidelines"
  - id: "PLAYWRIGHT-WSL-01"
    description: "Playwright WSL configuration and troubleshooting guide"
---

# Backend Testing Guide - Test Database Only

> **🚨 CRITICAL RULES**:
> 1. Backend tests MUST use ONLY test database (`lanocrm_test`). **NEVER** modify dev database (`lanocrm_dev`) during testing.
> 2. **NO ARTIFICIAL TEST PASSING**: Never modify tests to pass falsely. Fix implementation instead. See [Testing Rules](TESTING-RULES.md) for details.

## 📖 Tổng Quan (Overview)

### Mục Tiêu Testing (Testing Goals)
- **Đảm bảo chất lượng code**: Tất cả business logic phải được test
- **Ngăn bugs**: Phát hiện issues sớm trong development
- **Documentation**: Test serve như documentation cho code
- **Refactoring an toàn**: Tự tin khi refactor code có test
- **Data Safety**: Production data never affected by test runs
- **Test Isolation**: Tests can modify data without side effects

### Stack Testing
- **Backend**: PHP 8.4 + CodeIgniter 4 + PHPUnit 10.5
- **Database**: MySQL 8.4 với DevDatabaseTrait
- **Mutation Testing**: Infection cho quality validation
- **Coverage**: Xdebug cho coverage reports

## Database Architecture

### Test Database Isolation
- **Test Database**: `lanocrm_test` - Dedicated for all testing
- **Dev Database**: `lanocrm_dev` - Development data (NEVER touch in tests)
- **Connection Groups**:
  - `tests` group → `lanocrm_test` (for all tests)
  - `default` group → `lanocrm_dev` (for application)

### Why Test Database Only?
1. **Data Safety**: Production data never affected by test runs
2. **Test Isolation**: Tests can modify data without side effects
3. **Parallel Testing**: Multiple test runs won't interfere
4. **Reproducible Results**: Fresh data for each test run

## 🏗️ Kiến Trúc Testing (Testing Architecture)

### Layer Architecture
```
Request → Controller → Service → Repository → Model → DB
          (5 lines)   (logic)   (queries)    (schema)
```

### Test Structure
```
backend-ci/tests/
├── Unit/                    # Unit tests cho individual classes
├── Integration/             # Integration tests cho multiple components
├── Feature/                 # Feature tests cho user flows
├── _support/               # Test utilities và helpers
│   ├── Database/           # Database traits
│   ├── Factories/          # Data factories
│   ├── Assertions/         # Custom assertions
│   └── Fakes/              # Mock objects
└── _ci/                    # CodeIgniter test utilities
```

## 🚫 No Artificial Test Passing (CRITICAL)

**Definition**: "Pass ảo" là cố tình sửa bài test nhằm gây kết quả giả, không phản ánh đúng thực tế chức năng.

### ❌ FORBIDDEN - Examples of Artificial Passing:

```php
// BAD - Removing assertions to make tests pass
public function testProductCreation() {
    $product = $this->service->create($invalidData);
    // REMOVED: $this->assertNotNull($product->id);
    $this->assertTrue(true); // Always passes!
}

// BAD - Changing test logic to match broken implementation
public function testPriceCalculation() {
    $result = $this->service->calculatePrice($product);
    // Changed expected value to match broken implementation
    $this->assertEquals(150, $result); // Should be 200
}

// BAD - Mocking to hide real bugs
public function testDatabaseConnection() {
    // Mocking database connection to hide connection issues
    $mockDb = $this->createMock(Database::class);
    $mockDb->method('connect')->willReturn(true);
    $this->assertTrue($mockDb->connect());
}
```

### ✅ REQUIRED - Correct Testing Approach:

```php
// GOOD - Write tests that reflect real requirements
public function testProductCreation() {
    $product = $this->service->create($validData);
    $this->assertNotNull($product->id);
    $this->assertEquals($validData['name'], $product->name);
    $this->assertDatabaseHas('products', ['id' => $product->id]);
}

// GOOD - Test edge cases and error conditions
public function testProductCreationWithInvalidData() {
    $this->expectException(ValidationException::class);
    $this->service->create($invalidData);
}

// GOOD - Test actual behavior, not mocked behavior
public function testDatabaseConnection() {
    $db = new Database();
    $result = $db->connect();
    $this->assertTrue($result);
    // Test with real database, not mocks
}
```

## Required Testing Pattern

### DevDatabaseTrait + Schema Traits (MANDATORY)

All backend tests MUST follow this pattern:

```php
<?php
namespace Tests\Services;

use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\YourSchemaTrait;
use CodeIgniter\Test\CIUnitTestCase;

class YourServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;      // Test database connection + transactions
    use YourSchemaTrait;       // Schema creation for your module
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();     // Connect to test database + start transaction
        $this->resetYourSchema();   // Create test tables/data
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();  // Rollback transaction - data protection!
        parent::tearDown();
    }
}
```

### Key Principles

1. **Transaction Rollback**: All test data automatically rolled back
2. **Schema Reset**: Fresh schema for each test
3. **No DROP Commands**: Never use `DROP DATABASE/TABLE/INDEX`
4. **Test Data Only**: Use seeders or fixtures for test data

## 🧪 Các Loại Test (Test Types)

### 1. Unit Tests (Services, Repositories, Validators)

**Purpose**: Test individual classes/methods trong isolation
**Speed**: Nhanh nhất
**Database**: Test database with transactions

```php
class ProductServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductSchema();
    }
    
    public function testCalculatePriceWithTax(): void
    {
        // Arrange
        $product = ProductFactory::make(['price' => 100]);
        $service = new ProductService();
        
        // Act
        $result = $service->calculatePriceWithTax($product, 10);
        
        // Assert
        $this->assertEquals(110, $result);
    }
    
    public function testCreateProduct(): void
    {
        // Arrange - Create test data in test database
        $category = $this->createTestCategory();
        
        // Act - Call service method
        $product = $this->service->create([
            'code' => 'TEST-001',
            'name' => 'Test Product',
            'category_id' => $category['id']
        ]);
        
        // Assert - Verify in test database only
        $this->assertEquals('TEST-001', $product['code']);
        $this->assertDatabaseHas('products', ['code' => 'TEST-001']);
    }
}
```

### 2. Integration Tests (API Endpoints)

**Purpose**: Test interaction giữa multiple components
**Speed**: Trung bình
**Database**: MySQL real với transactions

```php
class ProductApiTest extends FeatureTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;
    
    public function testCreateProductApi(): void
    {
        // Arrange
        $userData = $this->createAuthenticatedUser();
        $productData = ProductFactory::definition();
        
        // Act
        $response = $this->post('/api/products', $productData, $userData['token']);
        
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'name', 'price']]);
        $this->assertDatabaseHas('products', ['name' => $productData['name']]);
    }
    
    public function testCreateProductApi(): void
    {
        // Arrange - Setup test data
        $token = $this->createTestUserToken();
        
        // Act - Make real API call
        $response = $this->post('/api/products', [
            'code' => 'API-001',
            'name' => 'API Product'
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);
        
        // Assert - Check response and database
        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['code' => 'API-001']);
    }
}
```

### 3. Feature Tests

**Purpose**: Test complete user flows
**Speed**: Chậm nhất
**Database**: MySQL real

```php
class ProductPurchaseFlowTest extends FeatureTestCase
{
    public function testCompletePurchaseFlow(): void
    {
        // 1. Create product
        $product = $this->createProduct();
        
        // 2. Add to cart
        $cart = $this->addToCart($product);
        
        // 3. Checkout
        $order = $this->checkout($cart);
        
        // 4. Verify order created
        $this->assertDatabaseHas('orders', [
            'id' => $order['id'],
            'status' => 'pending'
        ]);
    }
}
```

## 📝 Quy Tắc Viết Test (Test Writing Rules)

### 1. Naming Conventions
```php
// ✅ Good: Descriptive and clear
public function testCalculatePrice_WithValidTax_ReturnsCorrectAmount(): void
public function testCreateProduct_WithDuplicateCode_ThrowsException(): void

// ❌ Bad: Vague and unclear
public function testPrice(): void
public function testProduct(): void
```

### 2. Test Structure (AAA Pattern)
```php
public function testMethodName_Condition_ExpectedResult(): void
{
    // Arrange - Chuẩn bị data và dependencies
    $product = ProductFactory::create(['price' => 100]);
    $service = new ProductService();
    
    // Act - Thực thi action cần test
    $result = $service->calculatePriceWithTax($product, 10);
    
    // Assert - Kiểm tra kết quả
    $this->assertEquals(110, $result);
}
```

### 3. One Assertion Per Test (Khi có thể)
```php
// ✅ Good: Single responsibility
public function testCalculatePrice_ReturnsCorrectAmount(): void
{
    $result = $this->service->calculate(100, 10);
    $this->assertEquals(110, $result);
}

public function testCalculatePrice_ThrowsExceptionForNegativePrice(): void
{
    $this->expectException(InvalidArgumentException::class);
    $this->service->calculate(-100, 10);
}

// ❌ Bad: Multiple assertions
public function testCalculatePrice(): void
{
    $result = $this->service->calculate(100, 10);
    $this->assertEquals(110, $result);
    $this->assertIsFloat($result);
    $this->assertGreaterThan(0, $result);
}
```

### 4. Test Data Management
```php
// ✅ Good: Use factories
public function testProductCreation(): void
{
    $product = ProductFactory::create();
    $this->assertNotNull($product->id);
}

// ✅ Good: Use traits for setup
protected function setUp(): void
{
    parent::setUp();
    $this->setUpDatabase();
    $this->resetProductSchema();
}

// ❌ Bad: Hardcoded data
public function testProductCreation(): void
{
    $product = new Product();
    $product->name = 'Test Product';
    $product->price = 100;
    $product->save();
}
```

## 🔧 Patterns Phổ Biến (Common Patterns)

### 1. Database Transaction Pattern
```php
class ProductRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();     // Start transaction
        $this->resetProductSchema(); // Setup tables
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();  // Rollback transaction
        parent::tearDown();
    }
}
```

### 2. Factory Pattern
```php
// Factory definition
class ProductFactory extends BaseFactory
{
    protected array $defaultAttributes = [
        'name' => 'Test Product',
        'price' => 100.00,
        'status' => 'active',
        'created_at' => '2025-01-01 00:00:00'
    ];
    
    public function inactive(): self
    {
        return $this->state(['status' => 'inactive']);
    }
    
    public function expensive(): self
    {
        return $this->state(['price' => 1000.00]);
    }
}

// Usage in tests
$product = ProductFactory::create();
$inactiveProduct = ProductFactory::inactive()->create();
$expensiveProduct = ProductFactory::expensive()->make();
```

### 3. Mock Pattern
```php
class EmailServiceTest extends CIUnitTestCase
{
    public function testSendOrderConfirmation_CallsEmailService(): void
    {
        // Arrange
        $mockEmailService = $this->createMock(EmailService::class);
        $mockEmailService->expects($this->once())
                        ->method('sendOrderConfirmation')
                        ->with($this->equalTo($order));
        
        $orderService = new OrderService($mockEmailService);
        
        // Act
        $orderService->processOrder($order);
    }
}
```

## 🧬 Mutation Testing

### Setup Mutation Testing
```bash
# Install infection
composer require --dev infection/infection

# Run mutation testing
composer test:mutation

# Create baseline
composer test:mutation:baseline

# Custom MSI threshold
MIN_MSI=85 ./scripts/mutation-test.sh
```

### Configuration (infection.json.dist)
```json
{
    "source": {
        "directories": [
            "app/Services",
            "app/Repositories",
            "app/Validators"
        ]
    },
    "mutators": {
        "@default": true,
        "global-ignore": ["DocBlock", "PublicVisibility"]
    },
    "minMsi": 80.0,
    "minCoveredMsi": 80.0,
    "threads": 4
}
```

### Interpreting Results
- **MSI (Mutation Score Indicator)**: % mutants bị killed
- **Covered MSI**: % mutants được test cover
- **Target**: MSI ≥ 80% cho production code

### Improving Mutation Score
1. **Add specific assertions**: Thay vì generic assertions
2. **Test edge cases**: Boundary values, null values, empty arrays
3. **Test error conditions**: Exceptions, validation failures
4. **Remove dead code**: Code không được test thường là dead code

## 📊 Coverage Requirements

### Minimum Coverage Targets
- **Unit Tests**: 80% line coverage
- **Integration Tests**: 70% line coverage
- **Overall**: 75% combined coverage
- **Critical Modules**: 90% coverage (Payment, Auth, Core Business Logic)

### Coverage Reports
```bash
# Generate coverage report
vendor/bin/phpunit --coverage-html=coverage/html

# Check coverage threshold
php coverage-checker.php coverage/clover.xml 75

# Detailed coverage analysis
vendor/bin/phpunit --coverage-clover=coverage/clover.xml --coverage-text
```

### Coverage Best Practices
1. **Focus on business logic**, không cần test getters/setters
2. **Test error paths** quan trọng hơn happy paths
3. **Use data providers** cho multiple test cases
4. **Avoid test duplication** với helper methods

## Database Setup Commands

### Initial Setup (One-time)

```bash
# Start test database container
docker-compose up -d db-test

# Run migrations on test database
docker exec meomeo2-api-1 php spark migrate --all --env=testing

# Seed test database with demo data
docker exec meomeo2-api-1 php spark db:seed DemoSeeder --env=testing

# Or import pre-seeded test database
docker exec -i db-test mysql lanocrm_test < backend-ci/db-dumps/lanocrm_test_seeded_20251203.sql
```

### Before Running Tests

```bash
# Verify test database connection
docker exec meomeo2-api-1 php spark db:info tests

# Check test database status
docker exec meomeo2-api-1 php spark db:status tests
```

## Running Tests

### Unit Tests (Fast)
```bash
# Run all unit tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Run specific test file
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/ProductServiceTest.php

# Run with coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text
```

### Integration Tests (Full Stack)
```bash
# Run all integration tests
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Run specific integration test
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/Api/ProductsApiTest.php
```

## Forbidden Operations (CRITICAL)

### ❌ NEVER Do These in Tests

```php
// FORBIDDEN - Never drop database
$this->db->query('DROP DATABASE lanocrm_test');

// FORBIDDEN - Never drop tables in test database
$this->db->query('DROP TABLE products');

// FORBIDDEN - Never drop indexes in test database
$this->db->query('DROP INDEX idx_code ON products');

// FORBIDDEN - Never modify table structure in test database
$this->db->query('ALTER TABLE products ADD COLUMN new_col VARCHAR(255)');

// FORBIDDEN - Never modify foreign keys in test database
$this->db->query('ALTER TABLE products ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id)');

// FORBIDDEN - Never change schema in test database
$this->db->query('CREATE TABLE new_test_table (...)');

// FORBIDDEN - Never connect to dev database in tests
$config = new Config\Database();
$config->default = ['DBDriver' => 'MySQLi', 'database' => 'lanocrm_dev'];
```

### ✅ ALWAYS Do These Instead

```php
// ALLOWED - Use transactions (automatic with DevDatabaseTrait)
$this->db->transStart();
// ... test operations
$this->db->transComplete();

// ALLOWED - Truncate tables for data cleanup only
$this->db->table('products')->truncate();

// ALLOWED - Delete specific records for data reset only
$this->db->table('products')->where('test_data', 1)->delete();

// ALLOWED - Insert test data for testing purposes
$this->db->table('products')->insert($testData);

// ALLOWED - Update test data for testing scenarios
$this->db->table('products')->where('id', 1)->update($updateData);

// ALLOWED - Use test database connection only
$this->db = \Config\Database::connect('tests');

// ALLOWED - Reset data to clean state (not schema)
$this->resetTestData(); // Custom method to clean data only
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Database Connection Issues
```bash
# Check database connection
docker exec meomeo2-api-1 php spark db:info

# Reset database
docker exec meomeo2-api-1 php spark migrate:fresh --all
```

#### 2. Test Isolation Issues
```php
// Ensure proper cleanup
protected function tearDown(): void
{
    $this->tearDownDatabase(); // Always call this!
    parent::tearDown();
}
```

#### 3. Memory Issues
```php
// Increase memory limit in phpunit.xml
<ini name="memory_limit" value="-1"/>
```

#### 4. Slow Tests
```php
// Use SQLite for unit tests
// Use transactions for integration tests
// Mock external services
```

### Debug Commands
```bash
# Run specific test with debug
vendor/bin/phpunit --debug tests/Unit/ProductServiceTest.php

# Run with coverage for specific file
vendor/bin/phpunit --coverage-text --filter testCalculatePrice

# Check test syntax
php -l tests/Unit/ProductServiceTest.php
```

## WSL-Specific Issues

### Common Problems on WSL

1. **Database Connection Issues**:
```bash
# If tests can't connect to MySQL on WSL
sudo apt-get install mysql-client
# Check connection
mysql -h localhost -u root -p -e "SHOW DATABASES;"
```

2. **Permission Issues**:
```bash
# If you get permission denied errors
sudo chmod -R 755 backend-ci/writable
sudo chown -R www-data:www-data backend-ci/writable
```

3. **Docker Issues on WSL**:
```bash
# If Docker commands fail on WSL
sudo service docker start
sudo usermod -aG docker $USER
# Logout and login again
```

### WSL Test Commands

```bash
# On WSL, ensure proper permissions
sudo docker-compose up -d db-test

# Run tests with proper environment
docker exec -e CI=true meomeo2-api-1 vendor/bin/phpunit

# If tests hang, increase timeout
docker exec meomeo2-api-1 vendor/bin/phpunit --timeout=60
```

## Test Checklist - Copy into every PR

> **🚨 BREAKING CHANGE**: All tests now use test-database-only architecture. Never modify main database.

## Test Database Only Requirements (CRITICAL) ✓

### Environment Setup ✓
- [ ] Test database container running: `docker-compose up -d db-test`
- [ ] Test DB connection works: `docker exec meomeo2-api-1 php spark db:info tests`
- [ ] DevDatabaseTrait used properly (connects to `lanocrm_test` only)
- [ ] NEVER connects to dev database (`lanocrm_dev`) in tests

### New Pattern Required ✓
Your tests MUST follow this pattern:

```php
use DevDatabaseTrait;
use YourSchemaTrait;

protected function setUp(): void {
    parent::setUp();
    $this->setUpDatabase();     // Auto MySQL + transaction
    $this->resetYourSchema();   // Schema creation (if needed)
}

protected function tearDown(): void {
    $this->tearDownDatabase();  // Transactions rolled back
    parent::tearDown();
}
```

## Unit Tests ✓
- [ ] Service tests written and passing (DevDatabaseTrait + transactions)
- [ ] Repository tests written and passing (test database queries only)
- [ ] Validator tests written with test database validation
- [ ] Edge cases covered (null, empty, invalid test data)
- [ ] Exception handling tested with real test database constraints

## Integration Tests ✓
- [ ] API endpoints tested with test database only
- [ ] Authentication/authorization works with test database
- [ ] Database transactions rollback correctly in test database
- [ ] File upload works with test database storage (if applicable)
- [ ] Cross-module interactions work with test database
- [ ] Foreign key constraints tested in test database (ON DELETE/UPDATE)
- [ ] JSON/JSONB columns tested in test database (not TEXT)

## Manual Tests ✓
- [ ] Login flow works with test database auth
- [ ] CRUD operations work via Postman/curl against test database
- [ ] Error messages display correctly with test database constraints
- [ ] Validation messages are user-friendly
- [ ] Decimal precision handling works (DECIMAL vs REAL issues)

## Code Quality ✓
- [ ] Coverage >= 70% (check: `docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text`)
- [ ] No PHPUnit warnings/errors
- [ ] Single Responsibility followed (controllers thin, services have logic)
- [ ] Inline docs complete (@agent-* tags, test-database-specific annotations)
- [ ] No main database access patterns in tests

## Safety Checks ✓
- [ ] TUYỆT ĐỐI không dùng `DROP DATABASE`/`DROP TABLE`/`DROP INDEX` trong tests, schema traits hay scripts cleanup; chỉ dùng transaction rollback + `TRUNCATE`/`DELETE` trong `resetYourSchema()`.
- [ ] TUYỆT ĐỐI không sửa cấu trúc bảng (ALTER TABLE), thay đổi FK, hay schema trong test database; chỉ được reset dữ liệu.
- [ ] TUYỆT ĐỐI không kết nối tới dev database (`lanocrm_dev`) trong tests.
- [ ] Run: `bash .ai/pre-commit-checks.sh` - PASS
- [ ] Auth endpoints still work (test login with test database)
- [ ] Existing features not broken by test database migration
- [ ] Only modified files within task scope
- [ ] No hard-coded database configs (use Database config with 'tests' group)

## Test Database Specific Validation ✓
- [ ] DECIMAL columns handle precision correctly in test database
- [ ] JSON/JSONB columns validate properly in test database (MySQL 8+)
- [ ] TIMESTAMP vs DATETIME handled correctly in test database
- [ ] ENGINE=InnoDB used correctly in test database (not MyISAM)
- [ ] Foreign key constraints defined properly in test database
- [ ] Index patterns optimal in test database (PRIMARY, INDEX, UNIQUE)

## Performance (High Priority) ✓
- [ ] Query N+1 problems checked (especially with relations in test database)
- [ ] Large dataset tested (>1000 records in test database)
- [ ] Response time < 500ms with test database
- [ ] Index usage verified with EXPLAIN in test database
- [ ] Transaction isolation levels appropriate for test database

## Test Data Management ✓
- [ ] Schema traits use TRUNCATE (never DROP) for cleanup
- [ ] Test data created in test database only
- [ ] No production data used in tests
- [ ] Test isolation maintained between test runs
- [ ] Proper seed data used for test database

## Migration Cleanup ✓
- [ ] Remove old SQLite fallback code:
  ```php
  // REMOVE these ❌
  if (extension_loaded('sqlite3')) { ... }
  $config->tests = ['DBDriver' => 'SQLite3', ...];
  ```
- [ ] Ensure all schema traits use MySQL syntax
- [ ] Validate all `VARCHAR` instead of `TEXT` where appropriate
- [ ] Test JSON operations work with MySQL functions

---

**CRITICAL ITEMS that MUST pass:**

1. **Test database container must be running** or all tests will fail
2. **DevDatabaseTrait + SchemaTrait pattern required** - no exceptions
3. **NEVER connect to dev database** - tests must use `lanocrm_test` only
4. **Coverage >= 70%** - automatically enforced in CI
5. **No DROP/ALTER commands** - use TRUNCATE/DELETE for data only, never modify schema
6. **Schema protection** - never change table structure, foreign keys, or indexes in test database
7. **Test data isolation** - never affect dev data

**If ANY item FAILS → FIX before merging!**

### Test Database Quick Commands:

```bash
# Start test environment
docker-compose up -d db-test

# Run unit tests (test database only)
docker exec meomeo2-api-1 vendor/bin/phpunit

# Run integration tests (test database only)
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Check coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Check test database connection
docker exec meomeo2-api-1 php spark db:info tests

# Run lint checks
docker exec meomeo2-api-1 vendor/bin/phpcs tests/

# Verify test database isolation
docker exec db-test mysql -u root -p -e "SELECT DATABASE();"
```

## 📚 Resources Tham Khảo (Reference Resources)

### Internal Documentation
- `TEST-CHECKLIST.md` - Mandatory checklist
- `ASSERTION-REFERENCE.md` - Assertion patterns
- `INTEGRATION-TESTING-GUIDE.md` - Integration testing patterns

### External Resources
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Infection Mutation Testing](https://infection.github.io/)
- [CodeIgniter 4 Testing](https://codeigniter4.com/userguide/testing/index.html)

## 🎯 Best Practices Summary

### ✅ Do's
1. **Write tests first** (TDD khi có thể)
2. **Use descriptive test names**
3. **Test one thing per test**
4. **Use factories for test data**
5. **Clean up after each test**
6. **Mock external dependencies**
7. **Aim for high mutation score**
8. **Use test database only**
9. **Follow DevDatabaseTrait pattern**
10. **Never modify production data**

### ❌ Don'ts
1. **Don't test framework code**
2. **Don't ignore failing tests**
3. **Don't write complex test logic**
4. **Don't use production database**
5. **Don't skip edge cases**
6. **Don't commit without tests**
7. **Don't use DROP/ALTER commands**
8. **Don't connect to dev database**
9. **Don't create artificial passing**
10. **Don't modify tests to hide bugs**

## 📈 Metrics và KPIs

### Quality Metrics
- **Test Coverage**: Target ≥ 75%
- **Mutation Score**: Target ≥ 80%
- **Test Execution Time**: < 5 minutes cho full suite
- **Test Failure Rate**: < 5%

### Monitoring
- Weekly coverage reports
- Monthly mutation score analysis
- Quarterly test quality review
- Continuous integration monitoring
## 🔍 CodeIgniter 4 Debug Mode for Performance Testing

### Quick Reference

CI4 doesn't have `getDebugMode()` or `setDebugMode()` methods. Use environment-based checks:

```php
// Check debug mode
$isDebug = environment('CI_ENVIRONMENT') === 'development' || 
           (defined('CI_DEBUG') && CI_DEBUG);

// Safe query logging
if (method_exists($db, 'getQueryLog')) {
    $queries = $db->getQueryLog();
}
```

### Performance Testing Pattern

```php
public function assertQueryCount(callable $callback, int $maxQueries, string $message = ''): void
{
    $db = \Config\Database::connect();
    
    // Check debug mode
    $isDebugMode = environment('CI_ENVIRONMENT') === 'development' || 
                  (defined('CI_DEBUG') && CI_DEBUG);
    
    try {
        // Enable query logging if available
        $queryLoggingAvailable = false;
        if (method_exists($db, 'resetQueryLog')) {
            $db->resetQueryLog();
            $queryLoggingAvailable = true;
        }
        
        $result = $callback();
        
        // Get query count or use fallback
        $queryCount = 0;
        if ($queryLoggingAvailable && method_exists($db, 'getQueryLog')) {
            $queries = $db->getQueryLog();
            $queryCount = count($queries);
        } else {
            $queryCount = $this->estimateQueryCount($callback);
        }
        
        $this->assertLessThanOrEqual($maxQueries, $queryCount, $message);
        
    } catch (\Exception $e) {
        $this->fail("Query-intensive operation failed: " . $e->getMessage());
    }
}
```

### Environment Setup

```env
# .env file
CI_ENVIRONMENT = development  # or testing/production
```

**See**: `docs/testing/BACKEND-TESTING-CI4-DEBUG-MODE.md` for complete guide.


---

**CRITICAL**: This is the test-database-only era. All tests must use `lanocrm_test` and never touch `lanocrm_dev`.
**NO ARTIFICIAL PASSING**: Always fix implementation, never modify tests to pass falsely.
**These patterns are mandatory** for all new tests and refactoring.

*Document last updated: 2025-12-03*
*Version: 4.0*
