
# Backend Testing Training - LANO CRM

## 📋 Mục Lục

1. [Giới Thiệu Testing](#giới-thiệu-testing)
2. [Setup Môi Trường](#setup-môi-trường)
3. [Writing Your First Test](#writing-your-first-test)
4. [Testing Patterns](#testing-patterns)
5. [Hands-On Exercises](#hands-on-exercises)
6. [Common Pitfalls](#common-pitfalls)
7. [Resources và References](#resources-và-references)

---

## 🚀 Giới Thiệu Testing

### Tại Sao Testing Quan Trọng?

#### 1. Chất Lượng Code
```php
// ❌ Code không có test
function calculateDiscount($price, $percentage) {
    return $price * ($percentage / 100);
}

// Có bug gì ở đây? Không ai biết!
```

```php
// ✅ Code có test
function calculateDiscount($price, $percentage) {
    return $price * ($percentage / 100);
}

// Test giúp phát hiện bugs
test('calculateDiscount returns correct amount', function() {
    expect(calculateDiscount(100, 10))->toBe(10);
    expect(calculateDiscount(100, 0))->toBe(0);
    expect(calculateDiscount(100, 100))->toBe(100);
});
```

#### 2. Refactoring An Toàn
```php
// ❌ Sợ refactor vì có thể break code
function processOrder($order) {
    // 100 lines of complex logic
    // Không dám thay đổi gì!
}
```

```php
// ✅ Tự tin refactor
function processOrder($order) {
    $result = validateOrder($order);
    if (!$result->isValid()) {
        return $result;
    }
    
    return completeOrder($order);
}

// Test đảm bảo logic vẫn đúng sau refactor
test('processOrder handles invalid order', function() {
    $invalidOrder = createInvalidOrder();
    $result = processOrder($invalidOrder);
    
    expect($result->isValid())->toBeFalse();
});
```

#### 3. Documentation Sống
```php
// Test serve như documentation
test('product service calculates tax correctly', function() {
    $product = new Product(['price' => 100, 'tax_rate' => 10]);
    
    $finalPrice = $product->calculateFinalPrice();
    
    expect($finalPrice)->toBe(110); // 100 + 10% tax
});
```

### Types của Tests

#### 1. Unit Tests
- **Mục đích**: Test individual functions/methods
- **Speed**: Nhanh nhất (< 1s per test)
- **Isolation**: Không依赖 external dependencies

```php
test('product factory creates valid product', function() {
    $product = ProductFactory::create();
    
    expect($product)->toBeInstanceOf(Product::class);
    expect($product->id)->not->toBeNull();
    expect($product->name)->not->toBeEmpty();
});
```

#### 2. Integration Tests
- **Mục đích**: Test interaction giữa components
- **Speed**: Trung bình (1-5s per test)
- **Dependencies**: Database, external services

```php
test('product repository saves to database', function() {
    $productData = ProductFactory::definition();
    $repository = new ProductRepository();
    
    $product = $repository->create($productData);
    
    expect($product->id)->not->toBeNull();
    $this->assertDatabaseHas('products', ['id' => $product->id]);
});
```

#### 3. Feature Tests
- **Mục đích**: Test complete user flows
- **Speed**: Chậm nhất (5-30s per test)
- **Scope**: End-to-end scenarios

```php
test('user can create product via API', function() {
    $user = createUserWithPermissions(['products.create']);
    
    $response = $this->actingAs($user)
        ->postJson('/api/products', [
            'name' => 'Test Product',
            'price' => 100
        ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('products', ['name' => 'Test Product']);
});
```

---

## 🛠️ Setup Môi Trường

### 1. Cài Đặt Dependencies

```bash
# Install PHP dependencies
cd backend-ci
composer install

# Install testing dependencies
composer require --dev infection/infection

# Check installation
vendor/bin/phpunit --version
vendor/bin/infection --version
```

### 2. Database Setup

```bash
# Start database
docker-compose up -d db

# Check connection
docker exec kiotviet-web-1 php spark db:info

# Run migrations
docker exec kiotviet-web-1 php spark migrate --all
```

### 3. Environment Configuration

```bash
# Copy environment file
cp env .env

# Configure test database
echo "database.tests.hostname=127.0.0.1" >> .env
echo "database.tests.database=lanocrm_test" >> .env
echo "database.tests.username=lanocrm_user" >> .env
echo "database.tests.password=KP7n4RjcDbedSE2W8GgA" >> .env
```

### 4. Verify Setup

```bash
# Run basic tests
vendor/bin/phpunit tests/Unit/

# Check coverage
vendor/bin/phpunit --coverage-text

# Run mutation testing
vendor/bin/infection --configuration=infection.json.dist --threads=4
```

---

## ✍️ Writing Your First Test

### Step 1: Create Test File

```bash
# Create unit test
touch tests/Unit/ProductServiceTest.php

# Create integration test
touch tests/Integration/ProductApiTest.php
```

### Step 2: Basic Test Structure

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
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductSchema();
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }
    
    /** @test */
    public function it_creates_product_with_valid_data(): void
    {
        // Arrange
        $productData = ProductFactory::definition();
        $service = new ProductService();
        
        // Act
        $product = $service->create($productData);
        
        // Assert
        $this->assertInstanceOf(\App\Models\Product::class, $product);
        $this->assertNotNull($product->id);
        $this->assertEquals($productData['name'], $product->name);
    }
}
```

### Step 3: Run Test

```bash
# Run specific test
vendor/bin/phpunit tests/Unit/ProductServiceTest.php

# Run specific method
vendor/bin/phpunit --filter it_creates_product_with_valid_data

# Run with coverage
vendor/bin/phpunit --coverage-text tests/Unit/ProductServiceTest.php
```

---

## 🎯 Testing Patterns

### Pattern 1: AAA (Arrange, Act, Assert)

```php
/** @test */
public function calculate_price_with_tax(): void
{
    // Arrange - Chuẩn bị data
    $product = ProductFactory::create(['price' => 100, 'tax_rate' => 10]);
    $service = new ProductService();
    
    // Act - Thực thi action
    $finalPrice = $service->calculatePriceWithTax($product);
    
    // Assert - Kiểm tra kết quả
    $this->assertEquals(110, $finalPrice);
}
```

### Pattern 2: Factory Pattern

```php
/** @test */
public function create_product_with_variants(): void
{
    // Arrange
    $product = ProductFactory::new()
        ->withVariants(3)
        ->create();
    
    // Act & Assert
    $this->assertCount(3, $product->variants);
    $this->assertEquals('active', $product->status);
}
```

### Pattern 3: Database Assertions

```php
/** @test */
public function delete_product_removes_from_database(): void
{
    // Arrange
    $product = ProductFactory::create();
    $service = new ProductService();
    
    // Act
    $result = $service->delete($product->id);
    
    // Assert
    $this->assertTrue($result);
    $this->assertDatabaseMissing('products', ['id' => $product->id]);
}
```

### Pattern 4: API Testing

```php
/** @test */
public function create_product_via_api(): void
{
    // Arrange
    $user = $this->createAuthenticatedUser(['products.create']);
    $productData = ProductFactory::definition();
    
    // Act
    $response = $this->actingAs($user)
        ->postJson('/api/products', $productData);
    
    // Assert
    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'price',
            'status'
        ]
    ]);
    
    $this->assertDatabaseHas('products', [
        'name' => $productData['name']
    ]);
}
```

---

## 🏋️ Hands-On Exercises

### Exercise 1: Basic Unit Test

**Mục tiêu**: Viết unit test cho PriceCalculator

```php
// app/Services/PriceCalculator.php
class PriceCalculator
{
    public function calculateDiscount(float $price, float $percentage): float
    {
        return $price * ($percentage / 100);
    }
    
    public function calculateTax(float $price, float $taxRate): float
    {
        return $price * ($taxRate / 100);
    }
    
    public function calculateFinalPrice(float $price, float $taxRate, float $discount = 0): float
    {
        $discountedPrice = $price - $this->calculateDiscount($price, $discount);
        return $discountedPrice + $this->calculateTax($discountedPrice, $taxRate);
    }
}
```

**Bài tập**: Tạo file `tests/Unit/PriceCalculatorTest.php` với các test cases:

1. Test calculateDiscount với các giá trị khác nhau
2. Test calculateTax với edge cases
3. Test calculateFinalPrice với combination của tax và discount

**Solution**:

```php
<?php

namespace Tests\Unit;

use App\Services\PriceCalculator;

class PriceCalculatorTest extends \CodeIgniter\Test\CIUnitTestCase
{
    private PriceCalculator $calculator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator();
    }
    
    /** @test */
    public function calculate_discount_returns_correct_amount(): void
    {
        $result = $this->calculator->calculateDiscount(100, 10);
        $this->assertEquals(10, $result);
    }
    
    /** @test */
    public function calculate_discount_with_zero_percentage(): void
    {
        $result = $this->calculator->calculateDiscount(100, 0);
        $this->assertEquals(0, $result);
    }
    
    /** @test */
    public function calculate_tax_returns_correct_amount(): void
    {
        $result = $this->calculator->calculateTax(100, 10);
        $this->assertEquals(10, $result);
    }
    
    /** @test */
    public function calculate_final_price_with_tax_and_discount(): void
    {
        $result = $this->calculator->calculateFinalPrice(100, 10, 10);
        // 100 - 10 (discount) = 90
        // 90 + 9 (tax) = 99
        $this->assertEquals(99, $result);
    }
}
```

### Exercise 2: Integration Test với Database

**Mục tiêu**: Viết integration test cho ProductRepository

```php
// app/Repositories/ProductRepository.php
class ProductRepository
{
    public function create(array $data): Product
    {
        $product = new Product($data);
        $product->save();
        return $product;
    }
    
    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }
    
    public function update(int $id, array $data): bool
    {
        $product = Product::find($id);
        if (!$product) {
            return false;
        }
        
        return $product->update($data);
    }
    
    public function delete(int $id): bool
    {
        $product = Product::find($id);
        if (!$product) {
            return false;
        }
        
        return $product->delete();
    }
}
```

**Bài tập**: Tạo file `tests/Integration/ProductRepositoryTest.php` với các test cases:

1. Test create product
2. Test find by id
3. Test update product
4. Test delete product

### Exercise 3: API Feature Test

**Mục tiêu**: Viết feature test cho Product API endpoints

**Bài tập**: Tạo file `tests/Feature/ProductApiTest.php` với các test cases:

1. Test GET /api/products (list products)
2. Test POST /api/products (create product)
3. Test PUT /api/products/{id} (update product)
4. Test DELETE /api/products/{id} (delete product)

---

## ⚠️ Common Pitfalls

### 1. Test Isolation Issues

```php
// ❌ Bad: Tests ảnh hưởng lẫn nhau
class BadTest extends CIUnitTestCase
{
    /** @test */
    public function test_1(): void
    {
        ProductFactory::create(['name' => 'Product 1']);
        // Không cleanup
    }
    
    /** @test */
    public function test_2(): void
    {
        $products = Product::all();
        // Có thể có data từ test 1!
    }
}

// ✅ Good: Proper isolation
class GoodTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase(); // Transaction bắt đầu
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase(); // Rollback
        parent::tearDown();
    }
}
```

### 2. Hardcoded Test Data

```php
// ❌ Bad: Hardcoded data
public function testProductCreation(): void
{
    $product = new Product([
        'name' => 'Test Product',
        'code' => 'PROD-001',
        'price' => 100
    ]);
    $product->save();
    
    $this->assertEquals('Test Product', $product->name);
}

// ✅ Good: Factory pattern
public function testProductCreation(): void
{
    $product = ProductFactory::create();
    
    $this->assertNotNull($product->id);
    $this->assertNotNull($product->name);
}
```

### 3. Missing Edge Cases

```php
// ❌ Bad: Chỉ test happy path
public function testCalculateDiscount(): void
{
    $result = $this->calculator->calculateDiscount(100, 10);
    $this->assertEquals(10, $result);
}

// ✅ Good: Test edge cases
public function testCalculateDiscount(): void
{
    // Normal case
    $this->assertEquals(10, $this->calculator->calculateDiscount(100, 10));
    
    // Edge cases
    $this->assertEquals(0, $this->calculator->calculateDiscount(100, 0));
    $this->assertEquals(100, $this->calculator->calculateDiscount(100, 100));
    
    // Error cases
    $this->expectException(InvalidArgumentException::class);
    $this->calculator->calculateDiscount(-100, 10);
}
```

### 4. Test Implementation Details

```php
// ❌ Bad: Test implementation
public function testProduct(): void
{
    $product = ProductFactory::create();
    $this->assertEquals('active', $product->status); // Implementation detail
    $this->assertNull($product->deleted_at); // Implementation detail
}

// ✅ Good: Test behavior
public function testProductIsActive(): void
{
    $product = ProductFactory::create();
    $this->assertTrue($product->isActive()); // Behavior
}
```

---

## 📚 Resources và References

### Internal Documentation
- [`BACKEND-TESTING.md`](../testing/BACKEND-TESTING.md) - Consolidated backend testing guidelines
- [`ASSERTION-REFERENCE.md`](../testing/ASSERTION-REFERENCE.md) - Testing patterns and assertions
- [`INTEGRATION-TESTING-GUIDE.md`](../testing/INTEGRATION-TESTING-GUIDE.md) - Integration testing

### External Resources
- [PHPUnit Documentation](https://phpunit.de/documentation.html) - Official PHPUnit docs
- [CodeIgniter 4 Testing](https://codeigniter4.com/userguide/testing/) - CI4 testing guide
- [Infection Mutation Testing](https://infection.github.io/) - Mutation testing guide

### Quick Commands

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/ProductServiceTest.php

# Run with coverage
vendor/bin/phpunit --coverage-text

# Run mutation testing
vendor/bin/infection --configuration=infection.json.dist

# Check test syntax
php -l tests/Unit/ProductServiceTest.php
```

### Test Checklist

Trước khi commit code, đảm bảo:

- [ ] Tests pass locally
- [ ] Coverage ≥ 75%
- [ ] Mutation score ≥ 80%
- [ ] Test names descriptive
- [ ] One assertion per test