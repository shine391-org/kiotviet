---
title: "Testing Patterns - LANO CRM"
id: "TESTING-PATTERNS-01"
version: "2.0"
status: "Active"
module: "Testing"
type: "Code Patterns"
tags: ["testing", "patterns", "code-examples", "unit-tests", "integration-tests", "mysql-only", "devdatabasetrait"]
purpose: "Provides standard, copy-pasteable code patterns for MySQL-only testing in LANO CRM (SQLite removed)."
location: "docs/testing"
updated: "2025-11-25"
changes: "Migrated from SQLite to MySQL-only. All patterns now use DevDatabaseTrait + MySQL."
---

# Testing Patterns

## COPY THESE PATTERNS TO START FAST

> **CRITICAL: All tests now use MySQL-only architecture with DevDatabaseTrait. SQLite removed.**

## Pattern 1: Service Test (Unit - MySQL)
**Use for**: Business logic, calculations, data transformation.
**Location**: `tests/Services/`

```php
<?php
namespace Tests\Services;

use App\Services\Products\ProductService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductSchemaTrait;

/**
 * @agent-test: ProductService unified MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait + SchemaTrait (MySQL-only)
 */
class ProductServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;              // MySQL connection + transactions
    use ProductSchemaTrait;            // Schema creation for products

    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();          // Connect to MySQL + start transaction
        
        // Create product schema
        $this->resetProductSchema();     // Creates db_products table in MySQL
        
        $this->service = new ProductService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();       // Rollback transaction + close connection
        parent::tearDown();
    }

    /** @test */
    public function it_lists_products_with_pagination()
    {
        // Arrange
        $productId1 = $this->seedProduct(['code' => 'P001', 'name' => 'Product 1']);
        $productId2 = $this->seedProduct(['code' => 'P002', 'name' => 'Product 2']);

        // Act
        $result = $this->service->list(['page' => 1, 'limit' => 10]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['pagination']['total']);
    }

    /** @test */
    public function it_creates_product_with_valid_data()
    {
        // Act
        $result = $this->service->create([
            'code' => 'NEW001',
            'name' => 'New Product'
        ]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['id']);
        
        // Verify in MySQL database
        $row = $this->db->table('db_products')
            ->where('code', 'NEW001')
            ->get()->getRowArray();
        $this->assertNotNull($row);
    }

    /** @test */
    public function it_throws_on_duplicate_code()
    {
        // Arrange
        $this->seedProduct(['code' => 'DUP', 'name' => 'First']);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        
        // Act
        $this->service->create(['code' => 'DUP', 'name' => 'Second']);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create([]); // Missing required fields
    }

    // Helper methods - Use MySQL syntax
    private function seedProduct(array $data): int
    {
        $payload = array_merge([
            'code' => 'P' . random_int(1000, 9999),
            'name' => 'Sample Product',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('db_products')->insert($payload);
        return (int) $this->db->insertID();
    }
}
```

## Pattern 2: DevDatabaseTrait Integration Test (API + MySQL)
**Use for**: API Endpoints, Database constraints, Auth flow.
**Location**: `tests/Integration/Api/`

### Setup Environment (One-time)

**1. Ensure MySQL test container is running:**
```bash
docker-compose up -d db-test
```

**2. DevDatabaseTrait handles MySQL connection automatically:**

```php
<?php
namespace Tests\Integration\Api;

use CodeIgniter\Test\FeatureTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\YourSchemaTrait;

/**
 * @agent-test: Products API integration tests - MySQL-only
 * @agent-pattern: Integration test with DevDatabaseTrait + SchemaTrait
 * @agent-use: API endpoint testing with real MySQL database
 */
class ProductsApiTest extends FeatureTestCase
{
    use DevDatabaseTrait;               // MySQL connection + transactions
    use YourSchemaTrait;                // Schema creation

    protected function setUp(): void
    {
        parent::setUp();
        
        // DevDatabaseTrait handles MySQL connection automatically
        $this->setUpDatabase();          // Connect to MySQL + start transaction
        
        // Optional: Create specific schema
        $this->resetYourSchema();
        
        // Get auth token if needed
        $this->token = $this->getAuthToken();
        
        // Clean any existing test data
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        // DevDatabaseTrait handles cleanup
        $this->tearDownDatabase();       // Rollback transaction + close connection
        parent::tearDown();
    }

    /** @test */
    public function it_creates_product_via_api()
    {
        // Act - Use Bearer token authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json'
        ])->post('/api/products', [
            'code' => 'TEST001',
            'name' => 'Test Product',
            'status' => 'active'
        ]);

        // Assert HTTP
        $response->assertStatus(CREATED);
        $response->assertJSONFragment([
            'success' => true
        ]);

        // Assert MySQL Database (real database writes)
        $this->seeInDatabase('your_products', [
            'code' => 'TEST001',
            'name' => 'Test Product'
        ]);
    }

    /** @test */
    public function it_lists_products_with_pagination()
    {
        // Arrange - Create test data in MySQL
        $this->createTestProduct(['code' => 'LIST001']);
        $this->createTestProduct(['code' => 'LIST002']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->get('/api/products?page=1&limit=10');

        // Assert
        $response->assertStatus(200);
        $data = json_decode($response->getBody(), true);
        
        $this->assertTrue($data['success']);
        $this->assertGreaterThanOrEqual(2, count($data['data']));
        $this->assertArrayHasKey('pagination', $data);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        // Act - Send invalid data
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json'
        ])->post('/api/products', [
            // Missing required fields
        ]);

        // Assert MySQL validation
        $response->assertStatus(400);
        $data = json_decode($response->getBody(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Act - No token
        $response = $this->post('/api/products', [
            'code' => 'AUTH001',
            'name' => 'Should Fail'
        ]);

        // Assert
        $response->assertStatus(401);
        $this->assertFalse($response->isOK());
    }

    // Helper methods for MySQL
    private function getAuthToken(): string
    {
        $response = $this->post('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'P@ssw0rd'
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['token'] ?? '';
    }

    private function createTestProduct(array $data): int
    {
        $payload = array_merge([
            'code' => 'TEST' . random_int(1000, 9999),
            'name' => 'Test Product',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        return (int) $this->db->table('db_products')->insert($payload);
    }

    private function cleanupTestData(): void
    {
        // Clean test data using MySQL
        $this->db->table('db_products')
            ->like('code', 'TEST', 'after')
            ->delete();
    }
}
```

## Pattern 3: Schema Trait Test (Complex Schemas)
**Use for**: Complex database schemas involving multiple related tables.
**Location**: `tests/[Feature]/SchemaTrait.php`

### Example: DevDatabaseTrait + SchemaTrait Pattern

```php
<?php
namespace Tests\Services;

use App\Services\Orders\OrderService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\OrderSchemaTrait;

/**
 * @agent-test: Complex schema testing with DevDatabaseTrait
 * @agent-pattern: Service + SchemaTrait for comprehensive MySQL schemas
 */
class ComplexFeatureTest extends CIUnitTestCase
{
    use DevDatabaseTrait;       // MySQL connection foundation
    use OrderSchemaTrait;       // Creates orders, items, customers tables
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();     // Connect MySQL
        
        // Use schema trait for comprehensive table creation
        $this->resetOrderSchema();  // Creates: orders, order_items, customers, users
        
        $this->service = new OrderService();
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();   // Cleanup transactions
        parent::tearDown();
    }

    /** @test */
    public function it_creates_order_with_items_and_pricing()
    {
        // Arrange - Seed test data in MySQL
        $customer = $this->seedCustomer(['name' => 'Test Customer']);
        $user = $this->seedUser(['username' => 'tester']);
        
        $product1 = $this->seedProduct(['price' => 100000]);
        $product2 = $this->seedProduct(['price' => 200000]);
        
        // Act - Create order với multiple items
        $result = $this->service->create([
            'customer_id' => $customer,
            'created_by' => $user,
            'items' => [
                ['product_id' => $product1, 'quantity' => 2],
                ['product_id' => $product2, 'quantity' => 1],
            ]
        ]);
        
        // Assert - Check MySQL database chính xác
        $this->assertTrue($result['success']);
        $this->assertEquals(400000, $result['data']['total']); // 100k*2 + 200k*1
        
        // Verify in MySQL
        $order = $this->db->table('db_orders')
            ->where('id', $result['data']['id'])
            ->get()->getRowArray();
        
        $this->assertNotNull($order);
        $this->assertEquals(400000, $order['total']);
    }

    /** @test */
    public function it_applies_price_lists_to_order_items()
    {
        // Arrange - Setup price list và items
        $priceList = $this->seedPriceList(['priority' => 1, 'name' => 'VIP']);
        $product = $this->seedProduct(['base_price' => 100000]);
        $this->seedPriceListItem($priceList, $product, 80000); // 20% discount
        
        // Act - Customer tạo order
        $result = $this->service->create([
            'customer_id' => $this->seedCustomer(),
            'items' => [['product_id' => $product, 'quantity' => 1]]
        ]);
        
        // Assert - Price list đã áp dụng trong MySQL
        $this->assertEquals(80000, $result['data']['total']); // Discount applied
        $this->assertInDatabase('db_order_items', [
            'product_id' => $product,
            'final_price' => 80000,
            'price_list_id' => $priceList
        ]);
    }
}
```

## Pattern 4: Error Handling Tests

```php
/** @test */
public function it_handles_unique_constraint_violations()
{
    // Arrange - Create duplicate
    $product = $this->seedProduct(['code' => 'UNIQUE001']);
    
    // Act - Try duplicate code
    $response = $this->withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $this->token
    ])->post('/api/products', [
        'code' => 'UNIQUE001',  // Duplicate
        'name' => 'Duplicate Product'
    ]);
    
    // Assert - MySQL constraint violation handling
    $response->assertStatus(400);
    $data = json_decode($response->getBody(), true);
    $this->assertFalse($data['success']);
    $this->assertStringContainsString('already exists', json_encode($data));
}

/** @test */
public function it_validates_mysql_data_types()
{
    // Test DECIMAL precision với MySQL
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token
    ])->post('/api/prices', [
        'amount' => '99999999999999999',  // Quá lớn
        'product_id' => $this->productId
    ]);
    
    $response->assertStatus(400);
    // MySQL sẽ reject DECIMAL too large - real validation xảy ra
}
```

## 📝 Key Benefits của MySQL-only Approach:

1. **Real Database Validation**: DECIMAL, JSON, TIMESTAMP, Foreign Keys
2. **Production-Like Testing**: Syntax và behavior giống thật
3. **Better Data Type Handling**: Không còn loại TEXT cho mọi thứ
4. **Transaction Isolation**: Nhanh nhưng vẫn an toàn
5. **Constraint Testing**: FK constraints với ON DELETE, ON UPDATE

## 🚨 Lưu ý QUAN TRỌNG:

**1. Không bao giờ dùng SQLite pattern cũ:**
```php
// CŨ (Không dùng) ❌
if (extension_loaded('sqlite3')) {
    $config->tests = ['DBDriver' => 'SQLite3', ...];
}
```

**2. LUÔN dùng DevDatabaseTrait (MẸ)** ✅:
```php
use DevDatabaseTrait;
use YourSchemaTrait;
```

**3. Transaction là auto với DevDatabaseTrait:**
```php
$this->setUpDatabase();     // Auto transaction start
$this->tearDownDatabase();  // Auto rollback
```

**4. Schema created bằng MySQL syntax:**
```sql
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
```

## 🔧 Quick Commands for MySQL-only Testing:

```bash
# Run unit tests (transactions)
docker exec meomeo2-api-1 vendor/bin/phpunit

# Run integration tests (MySQL full stack)
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Run specific test
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/YourServiceTest.php

# Test with coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Check MySQL connection
docker exec meomeo2-api-1 php spark db:info tests
```

## 📈 Migration Status:
- **Pattern 1 (Service)**: ✅ Migrated to DevDatabaseTrait + MySQL
- **Pattern 2 (Integration)**: ✅ Migrated to DevDatabaseTrait + MySQL  
- **Pattern 3 (Repository)**: ✅ Migrated to DevDatabaseTrait + MySQL
- **Pattern 4 (Error)**: ✅ Migrated to DevDatabaseTrait + MySQL

**Key Achievement**: Eliminated SQLite completely, all patterns now MySQL-only ✅

**Copy these patterns cho các service test còn lại!**
