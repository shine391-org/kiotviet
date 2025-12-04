---
title: "Testing Patterns & Assertions Reference"
id: "TESTING-PATTERNS-01"
version: "4.0"
status: "Active"
module: "Testing"
type: "Reference"
tags: ["testing", "assertions", "patterns", "phpunit", "database", "api", "custom-assertions"]
purpose: "Comprehensive reference for testing patterns, assertions, and best practices for backend and frontend testing."
location: "docs/testing"
updated: "2025-12-03"
changes: "Consolidated from ASSERTION-REFERENCE.md (FACTORY-PATTERNS.md was empty), updated with comprehensive patterns and assertions"
related_to:
  - id: "BACKEND-TESTING-01"
    description: "Backend testing guide with test database only"
  - id: "FRONTEND-TESTING-01"
    description: "Frontend testing guide with real database integration"
  - id: "TEST-CHECKLIST-01"
    description: "Mandatory checklist for all testing changes"
---

# Testing Patterns & Assertions Reference - LANO CRM

## 📋 Mục Lục

1. [PHPUnit Assertions](#phpunit-assertions)
2. [Database Assertions](#database-assertions)
3. [API Response Assertions](#api-response-assertions)
4. [Business Logic Assertions](#business-logic-assertions)
5. [Custom Assertions](#custom-assertions)
6. [Factory Patterns](#factory-patterns)
7. [Testing Patterns](#testing-patterns)
8. [Best Practices](#best-practices)

---

## 🧪 PHPUnit Assertions

### Basic Assertions

#### assertEquals()
```php
// ✅ Good: Specific comparison
$this->assertEquals(100.50, $product->price);
$this->assertEquals('active', $product->status);

// ✅ Good: With message
$this->assertEquals(100, $result, 'Price calculation should return 100');

// ❌ Bad: Loose comparison when exact needed
$this->assertEquals('100', $product->price); // Type mismatch
```

#### assertSame()
```php
// ✅ Good: Type and value comparison
$this->assertSame(100, $result);
$this->assertSame('active', $status);

// Use when type matters
$this->assertSame(true, $isActive);
$this->assertSame(null, $deletedAt);
```

#### assertTrue() / assertFalse()
```php
// ✅ Good: Boolean checks
$this->assertTrue($product->isActive());
$this->assertFalse($product->isDeleted());

// ✅ Good: With descriptive message
$this->assertTrue($validator->isValid($data), 'Validation should pass for valid data');
```

#### assertNull() / assertNotNull()
```php
// ✅ Good: Null checks
$this->assertNull($product->deleted_at);
$this->assertNotNull($product->id);

// ✅ Good: Optional relationships
$this->assertNull($product->category_id); // No category assigned
```

#### assertCount()
```php
// ✅ Good: Array/collection size
$this->assertCount(5, $products);
$this->assertCount(2, $order->items);

// ✅ Good: With message
$this->assertCount(3, $errors, 'Should have exactly 3 validation errors');
```

#### assertContains() / assertNotContains()
```php
// ✅ Good: Array contains value
$this->assertContains('active', $allowedStatuses);
$this->assertNotContains('deleted', $allowedStatuses);

// ✅ Good: Check specific error
$this->assertContains('Name is required', $validationErrors);
```

#### assertInstanceOf()
```php
// ✅ Good: Type checking
$this->assertInstanceOf(Product::class, $product);
$this->assertInstanceOf(Collection::class, $results);

// ✅ Good: Interface checking
$this->assertInstanceOf(ValidatableInterface::class, $validator);
```

### Numeric Assertions

#### assertGreaterThan() / assertLessThan()
```php
// ✅ Good: Numeric comparisons
$this->assertGreaterThan(0, $product->price);
$this->assertLessThan(100, $discountPercentage);

// ✅ Good: Date comparisons
$this->assertGreaterThan($yesterday, $createdAt);
```

#### assertGreaterThanOrEqual() / assertLessThanOrEqual()
```php
// ✅ Good: Inclusive comparisons
$this->assertGreaterThanOrEqual(0, $stockQuantity);
$this->assertLessThanOrEqual(100, $discountPercentage);
```

### String Assertions

#### assertStringContainsString()
```php
// ✅ Good: Substring check
$this->assertStringContainsString('Product', $productName);
$this->assertStringContainsString('@', $email);

// ✅ Good: Case insensitive
$this->assertStringContainsStringIgnoringCase('error', $errorMessage);
```

#### assertMatchesRegularExpression()
```php
// ✅ Good: Pattern matching
$this->assertMatchesRegularExpression('/^PROD-\d{4}$/', $productCode);
$this->assertMatchesRegularExpression('/^\d{3}-\d{2}-\d{4}$/', $ssn);
```

#### assertJson()
```php
// ✅ Good: JSON validation
$this->assertJson($response->getContent());
$this->assertJsonStringEqualsJsonString($expected, $actual);
```

---

## 🗄️ Database Assertions

### Using DevDatabaseTrait

#### assertDatabaseHas()
```php
// ✅ Good: Record exists
$this->assertDatabaseHas('products', [
    'id' => $productId,
    'name' => 'Test Product',
    'status' => 'active'
]);

// ✅ Good: Check specific field
$this->assertDatabaseHas('products', [
    'code' => 'PROD-001'
]);
```

#### assertDatabaseMissing()
```php
// ✅ Good: Record doesn't exist
$this->assertDatabaseMissing('products', [
    'id' => $deletedProductId
]);

// ✅ Good: After soft delete
$this->assertDatabaseMissing('products', [
    'id' => $productId,
    'deleted_at' => null
]);
```

#### assertDatabaseCount()
```php
// ✅ Good: Count records
$this->assertDatabaseCount('products', 5);
$this->assertDatabaseCount('order_items', 3);

// ✅ Good: With conditions
$this->assertDatabaseCount('products', 2, ['status' => 'active']);
```

#### assertSoftDeleted()
```php
// ✅ Good: Soft delete check
$this->assertSoftDeleted('products', ['id' => $productId]);

// ✅ Good: Not soft deleted
$this->assertNotSoftDeleted('products', ['id' => $activeProductId]);
```

### Custom Database Assertions

#### assertDatabaseHasAttributes()
```php
// Custom assertion for checking multiple attributes
protected function assertDatabaseHasAttributes(string $table, array $data, array $attributes): void
{
    foreach ($attributes as $key => $value) {
        $this->assertDatabaseHas($table, array_merge($data, [$key => $value]));
    }
}

// Usage
$this->assertDatabaseHasAttributes('products', ['id' => 1], [
    'name' => 'Test Product',
    'price' => 100.00,
    'status' => 'active'
]);
```

---

## 🌐 API Response Assertions

### Status Code Assertions

#### assertStatus()
```php
// ✅ Good: Success responses
$response->assertStatus(200);  // OK
$response->assertStatus(201);  // Created
$response->assertStatus(204);  // No Content

// ✅ Good: Error responses
$response->assertStatus(400);  // Bad Request
$response->assertStatus(401);  // Unauthorized
$response->assertStatus(404);  // Not Found
$response->assertStatus(422);  // Validation Error
```

### JSON Structure Assertions

#### assertJsonStructure()
```php
// ✅ Good: Basic structure
$response->assertJsonStructure([
    'data' => [
        'id',
        'name',
        'price',
        'status'
    ]
]);

// ✅ Good: Nested structure
$response->assertJsonStructure([
    'data' => [
        'id',
        'name',
        'category' => [
            'id',
            'name'
        ],
        'variants' => [
            '*' => [
                'id',
                'sku',
                'price'
            ]
        ]
    ]
]);

// ✅ Good: Optional fields
$response->assertJsonStructure([
    'data' => [
        'id',
        'name',
        'description', // Optional
        'price'
    ]
]);
```

#### assertJsonFragment()
```php
// ✅ Good: Check specific values
$response->assertJsonFragment([
    'name' => 'Test Product',
    'price' => 100.00
]);

// ✅ Good: Nested values
$response->assertJsonFragment([
    'category' => [
        'name' => 'Electronics'
    ]
]);
```

#### assertJsonMissing()
```php
// ✅ Good: Ensure field not present
$response->assertJsonMissing(['password']);
$response->assertJsonMissing(['internal_id']);

// ✅ Good: Check specific value not present
$response->assertJsonMissing(['status' => 'deleted']);
```

### Header Assertions

#### assertHeader()
```php
// ✅ Good: Check headers
$response->assertHeader('Content-Type', 'application/json');
$response->assertHeader('X-Total-Count', '25');

// ✅ Good: Check header exists
$response->assertHeader('Authorization');
```

---

## 🏢 Business Logic Assertions

### Validation Assertions

#### assertValidationErrors()
```php
// ✅ Good: Check specific validation errors
$this->assertValidationErrors(['name', 'price'], $response);

// ✅ Good: Custom validation assertion
protected function assertValidationErrors(array $fields, TestResponse $response): void
{
    $response->assertStatus(422);
    $response->assertJsonValidationErrors($fields);
}

// Usage
$response = $this->post('/api/products', []);
$this->assertValidationErrors(['name', 'price'], $response);
```

#### assertValidationPasses()
```php
// ✅ Good: Validation should pass
protected function assertValidationPasses(array $data, string $rule): void
{
    $validator = validator($data, [$rule]);
    $this->assertTrue($validator->passes(), "Validation should pass for rule: $rule");
}

// Usage
$this->assertValidationPasses(['name' => 'Valid Name'], 'required|string|max:255');
```

### Business Rule Assertions

#### assertBusinessRule()
```php
// ✅ Good: Custom business rule assertion
protected function assertBusinessRule(callable $rule, string $message = ''): void
{
    $this->assertTrue($rule(), $message ?: 'Business rule should pass');
}

// Usage
$this->assertBusinessRule(
    fn() => $product->canBeDeleted(),
    'Product should be deletable when no orders exist'
);
```

#### assertPermission()
```php
// ✅ Good: Permission check
protected function assertPermission(string $permission, $user): void
{
    $this->assertTrue(
        $user->hasPermission($permission),
        "User should have permission: $permission"
    );
}

// Usage
$this->assertPermission('products.create', $adminUser);
$this->assertPermission('products.delete', $adminUser);
```

### Financial Assertions

#### assertMoneyEquals()
```php
// ✅ Good: Money comparison
protected function assertMoneyEquals(float $expected, float $actual, string $message = ''): void
{
    $this->assertEqualsWithDelta($expected, $actual, 0.01, $message);
}

// Usage
$this->assertMoneyEquals(100.50, $order->total, 'Order total should match');
```

#### assertTaxCalculation()
```php
// ✅ Good: Tax calculation
protected function assertTaxCalculation(float $price, float $rate, float $expected): void
{
    $calculated = $price * ($rate / 100);
    $this->assertMoneyEquals($expected, $calculated, 'Tax calculation should be correct');
}

// Usage
$this->assertTaxCalculation(100.00, 10.0, 10.00);
```

---

## 🔧 Custom Assertions

### Creating Custom Assertions

#### Base Custom Assertion Class
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
}
```

#### Domain-Specific Assertions

#### E-commerce Assertions
```php
trait EcommerceAssertions
{
    protected function assertCartTotal(Cart $cart, float $expected): void
    {
        $this->assertMoneyEquals($expected, $cart->getTotal(), 'Cart total should match');
    }
    
    protected function assertInventoryAvailable(Product $product, int $quantity): void
    {
        $this->assertGreaterThanOrEqual(
            $quantity,
            $product->stock_quantity,
            'Insufficient inventory'
        );
    }
    
    protected function assertOrderCanBePlaced(Order $order): void
    {
        $this->assertTrue($order->canBePlaced(), 'Order should be placeable');
        $this->assertGreaterThan(0, $order->items->count(), 'Order should have items');
    }
}
```

#### Accounting Assertions
```php
trait AccountingAssertions
{
    protected function assertJournalBalance(JournalEntry $entry): void
    {
        $debits = $entry->items->where('type', 'debit')->sum('amount');
        $credits = $entry->items->where('type', 'credit')->sum('amount');
        
        $this->assertEquals($debits, $credits, 'Journal entry must balance');
    }
    
    protected function assertAccountBalance(Account $account, float $expected): void
    {
        $this->assertMoneyEquals($expected, $account->getBalance(), 'Account balance should match');
    }
}
```

---

## 🏭 Factory Patterns

### Basic Factory Pattern
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

### Factory with Relationships
```php
class OrderFactory extends BaseFactory
{
    protected array $defaultAttributes = [
        'status' => 'pending',
        'total' => 0.00,
        'created_at' => '2025-01-01 00:00:00'
    ];
    
    public function withItems(int $count = 3): self
    {
        return $this->has(OrderItemFactory::new()->count($count));
    }
    
    public function withCustomer(): self
    {
        return $this->for(CustomerFactory::new());
    }
}

// Usage
$order = OrderFactory::new()
    ->withItems(5)
    ->withCustomer()
    ->create();
```

---

## 🔧 Testing Patterns

### Database Transaction Pattern
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

### API Testing Pattern
```php
class ProductApiTest extends IntegrationTestCase
{
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetProductSchema();
    }
    
    public function testCreateProduct_WithValidData_CreatesProduct(): void
    {
        // Arrange
        $userData = $this->createAuthenticatedUser(['permissions' => ['products.create']]);
        $productData = ProductFactory::definition();
        
        // Act
        $response = $this->apiRequest('POST', '/api/products', $productData, $userData['token']);
        
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'code',
                'price',
                'status'
            ]
        ]);
        
        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'code' => $productData['code']
        ]);
    }
}
```

### Service Testing Pattern
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
        $this->service = new ProductService(new ProductRepository(), new ProductValidator());
    }
    
    public function testCreateProduct_WithValidData_CreatesProduct(): void
    {
        // Arrange
        $productData = ProductFactory::definition();
        
        // Act
        $product = $this->service->create($productData);
        
        // Assert
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($productData['name'], $product->name);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
```

---

## 🎯 Best Practices

### 1. Use Specific Assertions

```php
// ✅ Good: Specific assertion
$this->assertEquals(100.00, $product->price);

// ❌ Bad: Generic assertion
$this->assertTrue($product->price == 100.00);
```

### 2. Include Descriptive Messages

```php
// ✅ Good: With message
$this->assertEquals(100.00, $total, 'Order total should include tax');

// ❌ Bad: No message
$this->assertEquals(100.00, $total);
```

### 3. Use Delta for Float Comparisons

```php
// ✅ Good: Float comparison with delta
$this->assertEqualsWithDelta(100.00, $actual, 0.01);

// ❌ Bad: Direct float comparison
$this->assertEquals(100.00, $actual); // May fail due to precision
```

### 4. Test One Thing Per Assertion

```php
// ✅ Good: Single assertion per test
public function testProductPrice(): void
{
    $this->assertEquals(100.00, $this->product->price);
}

public function testProductStatus(): void
{
    $this->assertEquals('active', $this->product->status);
}

// ❌ Bad: Multiple assertions
public function testProduct(): void
{
    $this->assertEquals(100.00, $this->product->price);
    $this->assertEquals('active', $this->product->status);
    $this->assertNotNull($this->product->id);
}
```

### 5. Use Custom Assertions for Repeated Logic

```php
// ✅ Good: Custom assertion
$this->assertProductIsActive($product);

// ❌ Bad: Repeated logic
$this->assertTrue($product->status === 'active' && $product->deleted_at === null);
```

### 6. Assert State, Not Implementation

```php
// ✅ Good: Assert behavior/result
$this->assertTrue($product->isAvailable());

// ❌ Bad: Assert implementation details
$this->assertEquals('active', $product->status);
$this->assertNull($product->deleted_at);
```

---

## 📚 Quick Reference

### Common Assertion Patterns

| Pattern | When to Use | Example |
|---------|-------------|---------|
| `assertEquals()` | Exact value comparison | `assertEquals(100, $price)` |
| `assertSame()` | Type + value comparison | `assertSame(true, $isActive)` |
| `assertTrue()` | Boolean checks | `assertTrue($validator->isValid())` |
| `assertDatabaseHas()` | Database record exists | `assertDatabaseHas('products', $data)` |
| `assertStatus()` | HTTP status codes | `assertStatus(201)` |
| `assertJsonStructure()` | JSON response format | `assertJsonStructure(['data' => ['id']])` |
| `assertJsonFragment()` | JSON contains value | `assertJsonFragment(['name' => 'Test'])` |

### Assertion Chaining

```php
// ✅ Good: Chain related assertions
$response
    ->assertStatus(200)
    ->assertJsonStructure(['data' => ['id', 'name']])
    ->assertJsonFragment(['name' => 'Test Product']);
```

---

*Document last updated: 2025-12-03*
*Version: 4.0*