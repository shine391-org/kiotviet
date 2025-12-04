
---
title: "Integration Testing Guide"
id: "INTEGRATION-TESTING-01"
version: "4.0"
status: "Active"
module: "Testing"
type: "Guide"
tags: ["testing", "integration", "api", "database", "services", "multi-module"]
purpose: "Comprehensive guide for integration testing covering API endpoints, database operations, service integration, and multi-module workflows."
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

# Integration Testing Guide - LANO CRM

## 📋 Mục Lục

1. [Tổng Quan Integration Testing](#tổng-quan-integration-testing)
2. [Kiến Trúc Integration Tests](#kiến-trúc-integration-tests)
3. [Database Integration](#database-integration)
4. [API Integration Testing](#api-integration-testing)
5. [Service Integration Testing](#service-integration-testing)
6. [Multi-Module Integration](#multi-module-integration)
7. [Best Practices](#best-practices)

---

## 📖 Tổng Quan Integration Testing

### Mục Đích
- **Test Component Interaction**: Kiểm tra interaction giữa các components
- **End-to-End Flows**: Test complete user flows
- **Database Integration**: Verify database operations
- **External Service Integration**: Test integration với external APIs
- **Performance Validation**: Đảm bảo performance requirements

### Khi Nào Dùng Integration Tests
- ✅ **API Endpoints**: Test HTTP requests/responses
- ✅ **Database Operations**: Test CRUD với real database
- ✅ **Service Integration**: Test interaction giữa services
- ✅ **Third-party APIs**: Test external service integration
- ✅ **Complex Workflows**: Test multi-step business processes

### Khi KHÔNG Dùng
- ❌ **Simple Unit Logic**: Dùng unit tests thay thế
- ❌ **Framework Features**: Không test CodeIgniter built-in features
- ❌ **External Services**: Mock external services khi có thể
- ❌ **Performance Tests**: Dùng dedicated performance testing tools

---

## 🏗️ Kiến Trúc Integration Tests

### Base Test Class

```php
// tests/Integration/IntegrationTestCase.php
abstract class IntegrationTestCase extends CIUnitTestCase
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
    
    /**
     * Create authenticated user
     */
    protected function createAuthenticatedUser(array $overrides = []): array
    {
        $user = UserFactory::create($overrides);
        $token = JwtService::generateToken($user);
        
        return [
            'user' => $user,
            'token' => $token
        ];
    }
    
    /**
     * Make API request with authentication
     */
    protected function apiRequest(string $method, string $uri, array $data = [], ?string $token = null): TestResponse
    {
        $headers = [];
        if ($token) {
            $headers['Authorization'] = "Bearer $token";
        }
        
        return $this->json($method, $uri, $data, $headers);
    }
}
```

### Feature Test Base

```php
// tests/Feature/FeatureTestCase.php
abstract class FeatureTestCase extends IntegrationTestCase
{
    /**
     * Create test data setup
     */
    protected function createTestData(): void
    {
        // Override in child classes
    }
    
    /**
     * Assert API response structure
     */
    protected function assertApiStructure(TestResponse $response, array $structure): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure($structure);
        $response->assertJson(['success' => true]);
    }
}
```

---

## 🗄️ Database Integration

### Database Transaction Pattern

```php
class ProductRepositoryIntegrationTest extends IntegrationTestCase
{
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetProductSchema();
    }
    
    public function testCreateProduct_WithValidData_SavesToDatabase(): void
    {
        // Arrange
        $productData = ProductFactory::definition();
        $repository = new ProductRepository();
        
        // Act
        $product = $repository->create($productData);
        
        // Assert
        $this->assertNotNull($product->id);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $productData['name'],
            'code' => $productData['code']
        ]);
    }
    
    public function testUpdateProduct_WithChanges_UpdatesDatabase(): void
    {
        // Arrange
        $product = ProductFactory::create();
        $repository = new ProductRepository();
        $updateData = ['name' => 'Updated Product Name'];
        
        // Act
        $result = $repository->update($product->id, $updateData);
        
        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name'
        ]);
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'name' => $product->name
        ]);
    }
    
    public function testDeleteProduct_WithExistingProduct_RemovesFromDatabase(): void
    {
        // Arrange
        $product = ProductFactory::create();
        $repository = new ProductRepository();
        
        // Act
        $result = $repository->delete($product->id);
        
        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
```

### Complex Database Operations

```php
class OrderRepositoryIntegrationTest extends IntegrationTestCase
{
    use OrderSchemaTrait;
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetOrderSchema();
        $this->resetProductSchema();
    }
    
    public function testCreateOrderWithItems_CreatesOrderAndItems(): void
    {
        // Arrange
        $customer = CustomerFactory::create();
        $products = ProductFactory::new()->createMany(3);
        
        $orderData = [
            'customer_id' => $customer->id,
            'status' => 'pending',
            'items' => $products->map(fn($p) => [
                'product_id' => $p->id,
                'quantity' => 2,
                'price' => $p->price
            ])->toArray()
        ];
        
        $repository = new OrderRepository();
        
        // Act
        $order = $repository->createWithItems($orderData);
        
        // Assert
        $this->assertNotNull($order->id);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'pending'
        ]);
        
        foreach ($products as $product) {
            $this->assertDatabaseHas('order_items', [
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 2
            ]);
        }
    }
}
```

---

## 🌐 API Integration Testing

### REST API Testing

```php
class ProductApiIntegrationTest extends IntegrationTestCase
{
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetProductSchema();
    }
    
    public function testGetProducts_ReturnsProductList(): void
    {
        // Arrange
        ProductFactory::new()->createMany(5);
        
        // Act
        $response = $this->getJson('/api/products');
        
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                    'price',
                    'status',
                    'created_at'
                ]
            ],
            'pagination' => [
                'current_page',
                'total',
                'per_page'
            ]
        ]);
        
        $this->assertCount(5, $response->json('data'));
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
    
    public function testCreateProduct_WithInvalidData_ReturnsValidationError(): void
    {
        // Arrange
        $userData = $this->createAuthenticatedUser(['permissions' => ['products.create']]);
        $invalidData = ['name' => '']; // Missing required fields
        
        // Act
        $response = $this->apiRequest('POST', '/api/products', $invalidData, $userData['token']);
        
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'code', 'price']);
    }
    
    public function testUpdateProduct_WithValidData_UpdatesProduct(): void
    {
        // Arrange
        $product = ProductFactory::create();
        $userData = $this->createAuthenticatedUser(['permissions' => ['products.update']]);
        $updateData = ['name' => 'Updated Product'];
        
        // Act
        $response = $this->apiRequest('PUT', "/api/products/{$product->id}", $updateData, $userData['token']);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Updated Product']);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product'
        ]);
    }
    
    public function testDeleteProduct_WithPermission_DeletesProduct(): void
    {
        // Arrange
        $product = ProductFactory::create();
        $userData = $this->createAuthenticatedUser(['permissions' => ['products.delete']]);
        
        // Act
        $response = $this->apiRequest('DELETE', "/api/products/{$product->id}", [], $userData['token']);
        
        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
```

### File Upload Integration

```php
class ProductImageUploadIntegrationTest extends IntegrationTestCase
{
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetProductSchema();
    }
    
    public function testUploadProductImage_WithValidFile_UploadsSuccessfully(): void
    {
        // Arrange
        $product = ProductFactory::create();
        $userData = $this->createAuthenticatedUser(['permissions' => ['products.update']]);
        
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);
        
        // Act
        $response = $this->apiRequest('POST', "/api/products/{$product->id}/images", [
            'image' => $file
        ], $userData['token']);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'filename',
                'path',
                'size'
            ]
        ]);
        
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'filename' => 'product.jpg'
        ]);
    }
    
    public function testUploadProductImage_WithInvalidFile_ReturnsError(): void
    {
        // Arrange
        $product = ProductFactory::create();
        $userData = $this->createAuthenticatedUser(['permissions' => ['products.update']]);
        
        $file = UploadedFile::fake()->create('document.pdf', 5000); // PDF file, too large
        
        // Act
        $response = $this->apiRequest('POST', "/api/products/{$product->id}/images", [
            'image' => $file
        ], $userData['token']);
        
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }
}
```

---

## 🔧 Service Integration Testing

### Service Layer Integration

```php
class ProductServiceIntegrationTest extends IntegrationTestCase
{
    use ProductSchemaTrait;
    use CategorySchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetProductSchema();
        $this->resetCategorySchema();
    }
    
    public function testCreateProductWithCategory_CreatesProductAndAssociatesCategory(): void
    {
        // Arrange
        $category = CategoryFactory::create();
        $productData = ProductFactory::definition();
        $productData['category_id'] = $category->id;
        
        $service = new ProductService(new ProductRepository(), new ProductValidator());
        
        // Act
        $product = $service->create($productData);
        
        // Assert
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($category->id, $product->category_id);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id
        ]);
    }
    
    public function testCalculateProductPrice_WithTaxAndDiscount_ReturnsCorrectPrice(): void
    {
        // Arrange
        $product = ProductFactory::create([
            'price' => 100.00,
            'tax_rate' => 10.0,
            'discount_rate' => 5.0
        ]);
        
        $service = new ProductService(new ProductRepository(), new ProductValidator());
        
        // Act
        $finalPrice = $service->calculateFinalPrice($product);
        
        // Assert
        $expectedPrice = 100.00 * 1.10 * 0.95; // Price with tax and discount
        $this->assertEqualsWithDelta($expectedPrice, $finalPrice, 0.01);
    }
    
    public function testUpdateProductStock_WithValidQuantity_UpdatesStock(): void
    {
        // Arrange
        $product = ProductFactory::create();
        $inventory = InventoryFactory::create([
            'product_id' => $product->id,
            'quantity' => 100
        ]);
        
        $service = new ProductService(new ProductRepository(), new ProductValidator());
        
        // Act
        $result = $service->updateStock($product->id, 50);
        
        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('inventory', [
            'product_id' => $product->id,
            'quantity' => 50
        ]);
    }
}
```

### Email Service Integration

```php
class EmailServiceIntegrationTest extends IntegrationTestCase
{
    public function testSendOrderConfirmation_SendsEmailSuccessfully(): void
    {
        // Arrange
        $customer = CustomerFactory::create(['email' => 'test@example.com']);
        $order = OrderFactory::create(['customer_id' => $customer->id]);
        
        $emailService = new EmailService();
        
        // Mock email transport for testing
        $emailService->setTransport(new TestTransport());
        
        // Act
        $result = $emailService->sendOrderConfirmation($order);
        
        // Assert
        $this->assertTrue($result);
        
        $sentEmails = TestTransport::sentMessages();
        $this->assertCount(1, $sentEmails);
        
        $email = $sentEmails[0];
        $this->assertEquals('test@example.com', $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('Order Confirmation', $email->getSubject());
    }
}
```

---

## 🔗 Multi-Module Integration

### Cross-Module Workflows

```php
class OrderToInvoiceWorkflowIntegrationTest extends IntegrationTestCase
{
    use OrderSchemaTrait;
    use InvoiceSchemaTrait;
    use ProductSchemaTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetOrderSchema();
        $this->resetInvoiceSchema();
        $this->resetProductSchema();
    }
    
    public function testCompleteOrderToInvoiceWorkflow_CreatesInvoiceFromOrder(): void
    {
        // Arrange
        $customer = CustomerFactory::create();
        $products = ProductFactory::new()->createMany(3);
        
        $order = OrderFactory::create([
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);
        
        foreach ($products as $product) {
            OrderItemFactory::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => $product->price
            ]);
        }
        
        $workflowService = new OrderToInvoiceWorkflowService();
        
        // Act
        $invoice = $workflowService->createInvoiceFromOrder($order);
        
        // Assert
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($customer->id, $invoice->customer_id);
        $this->assertEquals($order->total, $invoice->total);
        
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'order_id' => $order->id,
            'customer_id' => $customer->id
        ]);
        
        // Check invoice items created
        foreach ($products as $product) {
            $this->assertDatabaseHas('invoice_items', [
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'quantity' => 2
            ]);
        }
        
        // Check order status updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'invoiced'
        ]);
    }
}
```

### Event-Driven Integration

```php
class EventDrivenIntegrationTest extends IntegrationTestCase
{
    use ProductSchemaTrait;
    use OrderSchemaTrait;
    
    public function testProductPriceUpdate_TriggersOrderRecalculation(): void
    {
        // Arrange
        $product = ProductFactory::create(['price' => 100.00]);
        
        $orders = OrderFactory::new()->createMany(3);
        foreach ($orders as $order) {
            OrderItemFactory::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100.00
            ]);
        }
        
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->listen(ProductPriceUpdated::class, new OrderRecalculationHandler());
        
        // Act
        $eventDispatcher->dispatch(new ProductPriceUpdated($product, 120.00));
        
        // Assert
        foreach ($orders as $order) {
            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'total' => 120.00 // Updated price
            ]);
        }
    }
}
```

---

## 🎯 Best Practices

### 1. Test Isolation

```php
// ✅ Good: Proper setup and teardown
class ProductIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetProductSchema(); // Clean state
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase(); // Rollback transactions
        parent::tearDown();
    }
}

// ❌ Bad: No cleanup
class BadProductTest extends IntegrationTestCase
{
    public function testSomething(): void
    {
        // Creates data but doesn't clean up
        ProductFactory::create();
    }
}
```

### 2. Use Realistic Data

```php
// ✅ Good: Realistic test data
public function testOrderProcessing(): void
{
    $customer = CustomerFactory::create([
        'email' => 'customer@example.com',
        'phone' => '+1234567890'
    ]);
    
    $products = ProductFactory::new()
        ->withInventory(10)
        ->createMany(3);
    
    // Test with realistic data
}

// ❌ Bad: Unrealistic data
public function testOrderProcessing(): void
{
    $customer = CustomerFactory::create([
        'email' => 'test',
        'phone' => '123'
    ]);
    
    // Test with invalid data
}
```

### 3. Test Edge Cases

```php
// ✅ Good: Test edge cases
public function testCreateOrder_WithMaximumItems_HandlesCorrectly(): void
{
    $products = ProductFactory::new()->createMany(100); // Max allowed
    $orderData = $this->buildOrderData($products);
    
    $response = $this->apiRequest('POST', '/api/orders', $orderData, $token);
    
    $response->assertStatus(201);
}

public function testCreateOrder_WithTooManyItems_ReturnsError(): void
{
    $products = ProductFactory::new()->createMany(101); // Over limit
    $orderData = $this->buildOrderData($products);
    
    $response = $this->apiRequest('POST', '/api/orders', $orderData, $token);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items']);
}
```

### 4. Performance Considerations

```php
// ✅ Good: Efficient test setup
public function testBulkProductCreation(): void
{
    // Use factory for efficient bulk creation
    $products = ProductFactory::new()->createMany(1000);
    
    $response = $this->getJson('/api/products');
    
    $response->assertStatus(200);
    $this->assertCount(1000, $response->json('data'));
}

// ❌ Bad: Inefficient setup
public function testBulkProductCreation(): void
{
    // Slow individual creation
    for ($i = 0; $i < 1000; $i++) {
        ProductFactory::create();
    }
    
    // Test...
}
```

### 5. Error Handling

```php
// ✅ Good: Test error scenarios
public function testCreateProduct_WithDatabaseError_ReturnsServerError(): void
{
    // Mock database error
    $this->mock(DatabaseInterface::class)
        ->shouldReceive('insert')
        ->andThrow(new DatabaseException('Connection failed'));
    
    $response = $this->apiRequest('POST', '/api/products', $productData, $token);
    
    $response->assertStatus(500);
    $response->assertJsonFragment([
        'message' => 'Database error occurred'
    ]);
}
```

---

## 📊 Integration Test Categories

### 1. API Endpoint Tests
- HTTP status codes
- Request/response formats
- Authentication/authorization
- Validation errors
- File uploads

### 2. Database Tests
- CRUD operations
- Relationships
- Transactions
- Constraints
- Performance

### 3. Service Integration Tests
- Business logic
- External service calls
- Event handling
- Workflow processes
- Error scenarios

### 4. Cross-Module Tests
- Module interactions
- Data flow
- Event-driven architecture
- Complex workflows
- End-to-end scenarios

---

## 🔧 Debugging Integration Tests

### Common Issues và Solutions

#### 1. Database Connection Issues
```bash
# Check database status
docker exec meomeo2-api-1 php spark db:info

# Reset database
docker exec meomeo2-api-1 php spark migrate:fresh --all
```

#### 2. Test Isolation Problems
```php
// Ensure proper cleanup
protected function tearDown(): void
{
    $this->tearDownDatabase(); // Always call this!
    parent::tearDown();
}
```

#### 3. Performance Issues
```php
// Use transactions for speed
protected function setUp(): void
{
    parent::setUp();
    $this->setUpDatabase(); // Starts transaction
}
```

---

*Document last updated: 2025-12-03*
*Version: 1.0*