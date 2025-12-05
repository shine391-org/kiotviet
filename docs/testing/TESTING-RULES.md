---
title: "Testing Rules - Comprehensive Guidelines"
id: "TESTING-RULES-01"
version: "2.0"
status: "Active"
module: "Testing"
type: "Guideline"
tags: ["testing", "rules", "guidelines", "no-artificial-passing", "wsl", "e2e", "mutation-testing", "quality-gates", "assertion-traits", "factory-patterns"]
purpose: "Provides comprehensive testing rules and guidelines for the entire project with backend test-database-only, frontend real-database integration, and quality gates"
location: "docs/testing"
updated: "2025-12-03"
changes: "Restructured with YAML frontmatter, consolidated duplicate content, added mutation testing, quality gates, assertion traits, factory patterns, and WSL guidelines"
related_to:
  - id: "BACKEND-TESTING-01"
    description: "Backend testing guide with test database only"
  - id: "FRONTEND-TESTING-01"
    description: "Frontend testing guide with real database integration"
  - id: "TEST-CHECKLIST-01"
    description: "Mandatory checklist for all testing changes"
  - id: "ASSERTION-REFERENCE-01"
    description: "Comprehensive assertion reference guide"
  - id: "PLAYWRIGHT-WSL-01"
    description: "Playwright WSL configuration and troubleshooting guide"
  - id: "MUTATION-TESTING-01"
    description: "Mutation testing requirements and guidelines"
  - id: "QUALITY-GATES-01"
    description: "CI/CD quality gates and thresholds"
---

# Testing Rules - Comprehensive Guidelines

## 🚨 Golden Rules (MUST FOLLOW)

### 1. No Artificial Test Passing (CRITICAL)

**Definition**: "Pass ảo" là cố tình sửa bài test nhằm gây kết quả giả, không phản ánh đúng thực tế chức năng.

#### ❌ FORBIDDEN - Examples of Artificial Passing:

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

// BAD - Skipping tests that fail
public function testComplexFeature() {
    $this->markTestSkipped('Temporarily disabled - feature broken');
    // Instead of fixing the actual issue
}
```

```javascript
// BAD - Removing assertions in frontend tests
test('product form validation', async () => {
  render(<ProductForm />);
  await userEvent.click(screen.getByRole('button', { name: 'Submit' }));
  // REMOVED: expect(screen.getByText('Name is required')).toBeInTheDocument();
  expect(true).toBe(true); // Always passes!
});

// BAD - Mocking API to hide backend issues
test('creates product successfully', async () => {
  // Mocking API to hide actual backend problems
  vi.mock('../api/productApi', () => ({
    createProduct: vi.fn(() => Promise.resolve({ success: true }))
  }));
  
  const result = await createProduct(invalidData);
  expect(result.success).toBe(true); // Passes despite invalid data
});

// BAD - Changing test expectations to match bugs
test('calculates total price correctly', async () => {
  const result = calculateTotal(items);
  // Changed expectation to match buggy calculation
  expect(result).toBe(150); // Should be 200, but changed to match bug
});
```

#### ✅ REQUIRED - Correct Testing Approach:

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

```javascript
// GOOD - Test real user interactions and API responses
test('product form validation', async () => {
  render(<ProductForm />);
  await userEvent.click(screen.getByRole('button', { name: 'Submit' }));
  
  // Wait for real validation error
  await waitFor(() => {
    expect(screen.getByText('Name is required')).toBeInTheDocument();
  });
});

// GOOD - Test with real API calls
test('creates product successfully', async () => {
  // Reset test database
  await resetTestDatabase();
  
  const result = await createProduct(validData);
  expect(result.success).toBe(true);
  
  // Verify in real database
  const product = await getProductFromDatabase(result.data.id);
  expect(product.name).toBe(validData.name);
});

// GOOD - Test actual requirements, not current buggy behavior
test('calculates total price correctly', async () => {
  const result = calculateTotal(items);
  expect(result).toBe(200); // Correct expected value
  // If this fails, fix the implementation, not the test
});
```

### 2. Database Isolation Rules

#### Backend Testing (Test Database Only)
- **Test Database**: `lanocrm_test` ONLY - Never modify `lanocrm_dev`
- **Connection Groups**: Use `tests` group → `lanocrm_test` (for all tests)
- **Data Safety**: Production data never affected by test runs
- **Test Isolation**: Tests can modify data without side effects
- **Parallel Testing**: Multiple test runs won't interfere

#### Frontend Testing (Real Database Integration)
- **Real API Calls**: All tests must call actual backend APIs
- **Real Database**: Tests interact with `lanocrm_test` database through APIs
- **No Mocking**: Never mock API responses or use fake data
- **End-to-End Validation**: Test complete data flow from UI to database

### 3. Required Testing Patterns

#### Backend Pattern (MANDATORY)
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

#### Frontend Pattern (MANDATORY)
```javascript
// 1. Setup test database with real data
beforeEach(async () => {
  // Clean test database
  await resetTestDatabase();
  
  // Seed test data
  await seedTestData();
});

// 2. Make real API calls (no mocking)
test('creates product with real API', async () => {
  render(<ProductForm />);
  
  // Fill form
  await userEvent.type(screen.getByLabelText('Mã hàng'), 'TEST-001');
  await userEvent.type(screen.getByLabelText('Tên sản phẩm'), 'Test Product');
  
  // Submit form - REAL API CALL
  await userEvent.click(screen.getByRole('button', { name: /Thêm sản phẩm/i }));
  
  // Verify in REAL database
  await waitFor(() => {
    expect(screen.getByText('✅ Tạo mới sản phẩm thành công')).toBeInTheDocument();
  });
  
  // Verify data in test database
  const product = await getProductFromDatabase('TEST-001');
  expect(product).toBeTruthy();
  expect(product.name).toBe('Test Product');
});
```

## 🔧 Assertion Trait Usage Guidelines

### Custom Assertion Traits (MANDATORY)

#### Backend Custom Assertions
```php
// tests/_support/Assertions/CustomAssertions.php
trait CustomAssertions
{
    protected function assertProductIsActive($product): void
    {
        $this->assertTrue(
            $product->isActive(),
            'Product should be active'
        );
    }
    
    protected function assertOrderStatus($order, string $expectedStatus): void
    {
        $this->assertEquals(
            $expectedStatus,
            $order->status,
            "Order status should be {$expectedStatus}"
        );
    }
    
    protected function assertApiResponseFormat(TestResponse $response, array $expectedStructure): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure($expectedStructure);
        $response->assertJson(['success' => true]);
    }
    
    protected function assertMoneyEquals(float $expected, float $actual, string $message = ''): void
    {
        $this->assertEqualsWithDelta($expected, $actual, 0.01, $message);
    }
    
    protected function assertDatabaseStateValid(string $table, array $conditions): void
    {
        $this->assertDatabaseHas($table, $conditions);
        // Additional state validation logic
    }
}
```

#### Frontend Custom Assertions
```javascript
// tests/_support/Assertions/CustomAssertions.js
export const customAssertions = {
    assertProductInDOM(productCode) {
        expect(screen.getByText(productCode)).toBeInTheDocument();
    },
    
    assertSuccessMessage(message) {
        expect(screen.getByText(message)).toBeInTheDocument();
    },
    
    assertFormError(field, message) {
        expect(screen.getByText(message)).toBeInTheDocument();
    },
    
    async assertApiCallSucceeded(response) {
        expect(response.success).toBe(true);
        expect(response.data).toBeDefined();
    }
};
```

### Assertion Best Practices

1. **Use Specific Assertions**: Always use the most specific assertion available
2. **Include Descriptive Messages**: Add meaningful messages to assertions
3. **Use Delta for Float Comparisons**: Use `assertEqualsWithDelta` for monetary values
4. **Test One Thing Per Assertion**: Keep assertions focused and single-purpose
5. **Use Custom Assertions**: Create reusable assertion methods for repeated logic
6. **Assert State, Not Implementation**: Test behavior/results, not internal implementation

## 🏭 Factory Pattern Requirements

### Backend Factory Patterns (MANDATORY)

#### Model Factories
```php
// tests/_support/Factories/ProductFactory.php
class ProductFactory
{
    public static function create(array $overrides = []): array
    {
        return array_merge([
            'code' => 'PROD-' . uniqid(),
            'name' => 'Test Product ' . uniqid(),
            'selling_price' => 100000,
            'category_id' => 1,
            'product_type' => 'goods',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $overrides);
    }
    
    public static function createMany(int $count, array $overrides = []): array
    {
        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $products[] = static::create($overrides);
        }
        return $products;
    }
    
    public static function createInactive(array $overrides = []): array
    {
        return static::create(array_merge(['status' => 'inactive'], $overrides));
    }
    
    public static function createWithVariants(int $variantCount = 2): array
    {
        $product = static::create();
        $variants = [];
        
        for ($i = 0; $i < $variantCount; $i++) {
            $variants[] = VariantFactory::create([
                'product_id' => $product['id']
            ]);
        }
        
        $product['variants'] = $variants;
        return $product;
    }
}
```

#### Usage in Tests
```php
public function testProductListing(): void
{
    // Arrange - Create test data using factory
    $products = ProductFactory::createMany(5);
    $inactiveProduct = ProductFactory::createInactive();
    
    // Act - Call service method
    $result = $this->service->list(['status' => 'active']);
    
    // Assert - Verify results
    $this->assertCount(5, $result['data']);
    $this->assertNotContains($inactiveProduct['code'], array_column($result['data'], 'code'));
}
```

### Frontend Factory Patterns (MANDATORY)

#### Test Data Factories
```javascript
// tests/_support/Factories/ProductFactory.js
export class ProductFactory {
    static create(overrides = {}) {
        return {
            code: `PROD-${Date.now()}`,
            name: 'Test Product ' + Math.random().toString(36).substr(2, 9),
            selling_price: 100000,
            category_id: 1,
            product_type: 'goods',
            status: 'active',
            ...overrides
        };
    }
    
    static createMany(count, overrides = {}) {
        return Array.from({ length: count }, () => this.create(overrides));
    }
    
    static createInactive(overrides = {}) {
        return this.create({ status: 'inactive', ...overrides });
    }
    
    static createWithVariants(variantCount = 2) {
        const product = this.create();
        const variants = VariantFactory.createMany(variantCount, {
            product_id: product.id
        });
        
        return { ...product, variants };
    }
}
```

## 🗄️ Database State Validation Rules

### State Validation Requirements (MANDATORY)

#### Backend Database State Validation
```php
trait DatabaseStateValidation
{
    protected function assertDatabaseStateConsistent(): void
    {
        // Verify foreign key constraints
        $this->assertDatabaseHas('products', ['category_id' => 1]);
        $this->assertDatabaseHas('categories', ['id' => 1]);
        
        // Verify data integrity
        $products = $this->db->table('products')->get()->getResultArray();
        foreach ($products as $product) {
            $this->assertNotNull($product['code']);
            $this->assertNotNull($product['name']);
            $this->assertGreaterThanOrEqual(0, $product['selling_price']);
        }
    }
    
    protected function assertTransactionRollback(): void
    {
        // Verify that test data was rolled back
        $this->assertDatabaseMissing('products', ['code' => 'TEST-ROLLBACK']);
    }
    
    protected function assertSchemaIntegrity(): void
    {
        // Verify table structure
        $columns = $this->db->getFieldData('products');
        $columnNames = array_map(fn($col) => $col->name, $columns);
        
        $this->assertContains('id', $columnNames);
        $this->assertContains('code', $columnNames);
        $this->assertContains('name', $columnNames);
        $this->assertContains('selling_price', $columnNames);
    }
}
```

#### Frontend Database State Validation
```javascript
// tests/_support/Validation/DatabaseStateValidation.js
export const databaseStateValidation = {
    async assertProductCreated(productCode) {
        const product = await getProductFromDatabase(productCode);
        expect(product).toBeTruthy();
        expect(product.code).toBe(productCode);
        expect(product.name).toBeTruthy();
        expect(product.selling_price).toBeGreaterThan(0);
    },
    
    async assertProductDeleted(productCode) {
        const product = await getProductFromDatabase(productCode);
        expect(product).toBeFalsy();
    },
    
    async assertDatabaseConsistent() {
        // Verify data consistency across related tables
        const products = await getAllProducts();
        for (const product of products) {
            if (product.category_id) {
                const category = await getCategory(product.category_id);
                expect(category).toBeTruthy();
            }
        }
    }
};
```

## 🧪 Edge Case Testing Requirements

### Mandatory Edge Cases (CRITICAL)

#### Backend Edge Cases
```php
public function testProductEdgeCases(): void
{
    // Test null values
    $this->expectException(ValidationException::class);
    $this->service->create(['code' => null]);
    
    // Test empty strings
    $this->expectException(ValidationException::class);
    $this->service->create(['code' => '']);
    
    // Test extremely long values
    $this->expectException(ValidationException::class);
    $this->service->create(['code' => str_repeat('A', 300)]);
    
    // Test special characters
    $product = $this->service->create(['code' => 'PROD-@#$%']);
    $this->assertEquals('PROD-@#$%', $product['code']);
    
    // Test boundary values
    $this->expectException(ValidationException::class);
    $this->service->create(['selling_price' => -1]);
    
    $product = $this->service->create(['selling_price' => 0]);
    $this->assertEquals(0, $product['selling_price']);
    
    // Test maximum values
    $product = $this->service->create(['selling_price' => 999999999]);
    $this->assertEquals(999999999, $product['selling_price']);
}
```

#### Frontend Edge Cases
```javascript
describe('Product Form Edge Cases', () => {
    test('handles null values', async () => {
        render(<ProductForm />);
        
        // Submit with null values
        await userEvent.click(screen.getByRole('button', { name: /Thêm sản phẩm/i }));
        
        // Should show validation errors
        await waitFor(() => {
            expect(screen.getByText('Mã hàng là bắt buộc')).toBeInTheDocument();
            expect(screen.getByText('Tên sản phẩm là bắt buộc')).toBeInTheDocument();
        });
    });
    
    test('handles extremely long input', async () => {
        render(<ProductForm />);
        
        const longText = 'A'.repeat(300);
        await userEvent.type(screen.getByLabelText('Tên sản phẩm'), longText);
        
        await userEvent.click(screen.getByRole('button', { name: /Thêm sản phẩm/i }));
        
        await waitFor(() => {
            expect(screen.getByText('Tên sản phẩm không được vượt quá 255 ký tự')).toBeInTheDocument();
        });
    });
    
    test('handles special characters', async () => {
        render(<ProductForm />);
        
        await userEvent.type(screen.getByLabelText('Mã hàng'), 'PROD-@#$%');
        await userEvent.type(screen.getByLabelText('Tên sản phẩm'), 'Product @#$%');
        
        await userEvent.click(screen.getByRole('button', { name: /Thêm sản phẩm/i }));
        
        await waitFor(() => {
            expect(screen.getByText('✅ Tạo mới sản phẩm thành công')).toBeInTheDocument();
        });
    });
});
```

## 🚨 Error Message Testing Standards

### Error Message Validation (MANDATORY)

#### Backend Error Message Testing
```php
public function testValidationMessages(): void
{
    // Test required field validation
    try {
        $this->service->create([]);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        $this->assertArrayHasKey('code', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('Mã hàng là bắt buộc', $errors['code']);
        $this->assertEquals('Tên sản phẩm là bắt buộc', $errors['name']);
    }
    
    // Test duplicate validation
    $product1 = ProductFactory::create(['code' => 'DUPLICATE']);
    $product2 = ProductFactory::create(['code' => 'DUPLICATE']);
    
    try {
        $this->service->create($product2);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        $this->assertArrayHasKey('code', $errors);
        $this->assertEquals('Mã hàng đã tồn tại', $errors['code']);
    }
}
```

#### Frontend Error Message Testing
```javascript
test('displays proper error messages', async () => {
    render(<ProductForm />);
    
    // Test required field errors
    await userEvent.click(screen.getByRole('button', { name: /Thêm sản phẩm/i }));
    
    await waitFor(() => {
        expect(screen.getByText('Mã hàng là bắt buộc')).toBeInTheDocument();
        expect(screen.getByText('Tên sản phẩm là bắt buộc')).toBeInTheDocument();
        expect(screen.getByText('Giá bán là bắt buộc')).toBeInTheDocument();
    });
    
    // Test duplicate error
    await userEvent.type(screen.getByLabelText('Mã hàng'), 'EXISTING');
    await userEvent.type(screen.getByLabelText('Tên sản phẩm'), 'Test Product');
    await userEvent.type(screen.getByLabelText('Giá bán'), '100000');
    
    await userEvent.click(screen.getByRole('button', { name: /Thêm sản phẩm/i }));
    
    await waitFor(() => {
        expect(screen.getByText('Mã hàng đã tồn tại')).toBeInTheDocument();
    });
});
```

## 🧬 Mutation Testing Requirements

### Mutation Testing Standards (MANDATORY)

#### Configuration Requirements
```json
// backend-ci/infection.json.dist
{
    "source": {
        "directories": [
            "app"
        ]
    },
    "logs": {
        "text": "infection.log",
        "summary": "summary.log",
        "debug": "infection-debug.log"
    },
    "mutators": {
        "@default": true,
        "CastArray": false,
        "CastBoolean": false,
        "CastInteger": false,
        "CastString": false
    },
    "testFramework": "phpunit",
    "bootstrap": "vendor/autoload.php",
    "minMsi": 80,
    "minCoveredMsi": 80
}
```

#### Running Mutation Testing
```bash
# Run mutation testing with minimum MSI score
./scripts/mutation-test.sh --min-msi 80

# Create baseline for future comparisons
./scripts/mutation-test.sh --baseline

# Compare against baseline
./scripts/mutation-test.sh --compare

# Custom MSI score
./scripts/mutation-test.sh --min-msi 85
```

#### Mutation Testing Best Practices
1. **Minimum MSI Score**: 80% for all code
2. **Baseline Tracking**: Create baselines for regression detection
3. **Escaped Mutant Review**: Manually review escaped mutants for false positives
4. **Test Improvement**: Add tests to kill escaped mutants
5. **Regular Execution**: Run mutation testing before major releases

## 🚪 CI/CD Quality Gates

### Quality Gate Requirements (MANDATORY)

#### Coverage Thresholds
- **Backend Coverage**: ≥ 70% statements, branches, functions, lines
- **Frontend Coverage**: ≥ 70% statements, branches, functions, lines
- **Critical Components**: ≥ 90% coverage
- **Mutation Score**: ≥ 80% MSI (Mutation Score Indicator)

#### Quality Gate Script
```bash
# Run all quality checks
./scripts/test-quality-gate.sh

# Custom thresholds
./scripts/test-quality-gate.sh --min-coverage 80 --min-msi 85

# Skip mutation testing (for quick checks)
./scripts/test-quality-gate.sh --skip-mutation
```

#### CI/CD Integration
```yaml
# .github/workflows/quality-gate.yml
name: Quality Gate

on:
  pull_request:
    branches: [dev, staging]

jobs:
  quality-gate:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Run Quality Gate
        run: ./scripts/test-quality-gate.sh
        env:
          MIN_COVERAGE: 70
          MIN_MSI: 80
```

### Quality Gate Failure Resolution
1. **Coverage Issues**: Add more tests to reach minimum thresholds
2. **Mutation Issues**: Improve test assertions to kill more mutants
3. **Test Failures**: Fix failing tests before proceeding
4. **Performance Issues**: Optimize slow tests and queries

## ⚡ Performance Testing Standards

### Performance Requirements (MANDATORY)

#### Backend Performance Testing
```php
public function testPerformanceWithLargeDataset(): void
{
    // Create large dataset
    ProductFactory::createMany(1000);
    
    $startTime = microtime(true);
    
    // Test performance
    $result = $this->service->list([]);
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    // Assert performance requirements
    $this->assertLessThan(0.5, $executionTime, 'Query should execute in less than 500ms');
    $this->assertCount(1000, $result['data']);
}

public function testQueryOptimization(): void
{
    // Test N+1 query problems
    $products = ProductFactory::createMany(10);
    
    // Enable query logging
    $this->db->enableQueryLog();
    
    $result = $this->service->listWithRelations([]);
    
    $queries = $this->db->getQueryLog();
    
    // Should not have N+1 queries
    $this->assertLessThan(15, count($queries), 'Should not have N+1 query problems');
}
```

#### Frontend Performance Testing
```javascript
test('renders large product list efficiently', async () => {
    // Create large dataset
    const products = ProductFactory.createMany(1000);
    await seedProducts(products);
    
    const startTime = performance.now();
    
    render(<ProductList />);
    
    await waitFor(() => {
        expect(screen.getAllByTestId('product-item')).toHaveLength(1000);
    });
    
    const endTime = performance.now();
    const renderTime = endTime - startTime;
    
    // Should render in less than 2 seconds
    expect(renderTime).toBeLessThan(2000);
});

test('handles API response time', async () => {
    render(<ProductList />);
    
    const startTime = performance.now();
    
    await waitFor(() => {
        expect(screen.getByTestId('product-list')).toBeInTheDocument();
    });
    
    const endTime = performance.now();
    const responseTime = endTime - startTime;
    
    // API should respond in less than 2 seconds
    expect(responseTime).toBeLessThan(2000);
});
```

## 🔧 WSL-Specific Testing Guidelines

### WSL Environment Setup (MANDATORY)

#### Backend Testing on WSL
```bash
# Install MySQL client on WSL
sudo apt-get update
sudo apt-get install mysql-client

# Check database connection
mysql -h localhost -u root -p -e "SHOW DATABASES;"

# Fix permission issues
sudo chmod -R 755 backend-ci/writable
sudo chown -R www-data:www-data backend-ci/writable

# Start Docker with proper permissions
sudo service docker start
sudo usermod -aG docker $USER
# Logout and login again
```

#### Frontend Testing on WSL
```bash
# Install Playwright browsers with admin rights
sudo npx playwright install --with-deps chromium

# Fix permission issues
sudo chmod -R 755 ~/.cache/ms-playwright

# Install X11 forwarding for display issues
sudo apt-get install x11-apps
export DISPLAY=:0

# Run E2E tests with proper environment
cd ~/projects/kiotviet/lanocrm
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts
```

### WSL Troubleshooting

#### Common Issues and Solutions
1. **Browser Installation Issues**:
```bash
# If browsers fail to install on WSL
npx playwright install --with-deps chromium

# If permission issues occur
sudo npx playwright install --with-deps chromium

# If network issues occur
sudo apt-get update
sudo apt-get install -y wget ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release xdg-utils
```

2. **Network Connection Issues**:
```bash
# If tests can't connect to backend
ping localhost  # Test local connectivity
ping 127.0.0.1  # Test loopback
# If ping fails, contact admin to install network packages
```

3. **Docker Issues on WSL**:
```bash
# If Docker commands fail on WSL
sudo service docker start
sudo usermod -aG docker $USER
# Logout and login again
```

## 📋 Test Review Process

### Before Committing:
1. **All tests pass** without artificial modifications
2. **Coverage meets requirements** (≥ 70%)
3. **Mutation score meets requirements** (≥ 80% MSI)
4. **No mocking patterns** in frontend tests
5. **Test database isolation** verified
6. **E2E tests run** with real backend
7. **Performance tests pass** within time limits
8. **WSL compatibility** verified

### Code Review Checklist:
- [ ] Tests validate real requirements
- [ ] No artificial passing patterns detected
- [ ] Test data is realistic and isolated
- [ ] Error conditions are properly tested
- [ ] Edge cases are covered
- [ ] Database state validation is implemented
- [ ] Custom assertions are used appropriately
- [ ] Factory patterns are implemented correctly
- [ ] Mutation testing requirements are met
- [ ] Performance requirements are met
- [ ] Documentation is clear and complete

## 🚫 Forbidden Testing Practices

### Never Do These:
1. **Remove assertions** to make tests pass
2. **Change expected values** to match broken implementations
3. **Mock APIs** to hide backend issues
4. **Skip failing tests** instead of fixing them
5. **Use fake data** instead of real API responses
6. **Modify dev database** during testing
7. **Mock database connections** to hide connection issues
8. **Use DROP/ALTER commands** in test database
9. **Ignore performance requirements**
10. **Skip mutation testing** without justification

### Always Do These:
1. **Fix the implementation** when tests fail
2. **Write meaningful assertions** that validate real behavior
3. **Test error conditions** and edge cases
4. **Use real database** for integration testing
5. **Clean up test data** after each test
6. **Document test scenarios** clearly
7. **Review test failures** before making changes
8. **Use factory patterns** for test data creation
9. **Implement custom assertions** for repeated validation
10. **Run mutation testing** to ensure test quality

---

**Remember**: Tests are our safety net. Artificial passing creates false confidence and leads to production issues. Always fix the implementation, never the test.

**Quality is not optional**: All tests must meet coverage, mutation, and performance requirements before merging.

**WSL compatibility**: Ensure all tests work properly on WSL environments with proper permissions and dependencies.