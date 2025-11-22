# Testing Patterns

## COPY THESE PATTERNS TO START FAST

## Pattern 1: Service Test (Unit - SQLite)
**Use for**: Business logic, calculations, data transformation.
**Location**: `tests/Services/`

```php
<?php
namespace Tests\Services;

use App\Services\Products\ProductService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use InvalidArgumentException;

/**
 * @agent-test: ProductService unit tests
 * @agent-pattern: Standard service test - COPY THIS
 */
class ProductServiceTest extends CIUnitTestCase
{
    private ProductService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests'); // SQLite
        $this->resetSchema();
        $this->service = new ProductService();
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
        
        // Verify in database
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

    // Helper methods
    private function resetSchema(): void
    {
        // Drop tables
        $this->db->query('DROP TABLE IF EXISTS db_products');
        
        // Create tables
        $this->db->query('CREATE TABLE db_products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL,
            name TEXT NOT NULL,
            status TEXT DEFAULT "active",
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');
    }

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

## Pattern 2: Integration Test (API + MySQL)
**Use for**: API Endpoints, Database constraints, Auth flow.
**Location**: `tests/Integration/Api/`

```php
<?php
namespace Tests\Integration\Api;

use CodeIgniter\Test\FeatureTestCase;
use Config\Database;

/**
 * @agent-test: Products API integration tests
 * @agent-pattern: Standard API integration test - COPY THIS
 * @agent-note: Uses MySQL test database, not SQLite
 */
class ProductsApiTest extends FeatureTestCase
{
    protected $db;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use MySQL test database
        $this->db = Database::connect('tests');
        
        // Get auth token
        $this->token = $this->getAuthToken();
        
        // Clean test data
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_product_via_api()
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json'
        ])->post('/api/products', [
            'code' => 'TEST001',
            'name' => 'Test Product',
            'status' => 'active'
        ]);

        // Assert HTTP
        $response->assertStatus(201);
        $response->assertJSONFragment([
            'success' => true
        ]);

        // Assert Database
        $this->seeInDatabase('db_products', [
            'code' => 'TEST001',
            'name' => 'Test Product'
        ]);
    }

    /** @test */
    public function it_lists_products_with_pagination()
    {
        // Arrange - Create test data
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

        // Assert
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
    }

    // Helper methods
    private function getAuthToken(): string
    {
        $response = $this->post('/api/auth/login', [
            'username' => 'devadmin',
            'password' => 'Admin@123'
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

        $this->db->table('db_products')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function cleanupTestData(): void
    {
        // Clean test products (code starts with TEST)
        $this->db->table('db_products')
            ->like('code', 'TEST', 'after')
            ->delete();
    }
}
```

## Pattern 3: Repository Test
**Use for**: Complex queries, filters, data retrieval.
**Location**: `tests/Repositories/`

```php
<?php
namespace Tests\Repositories;

use App\Repositories\Products\ProductRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * @agent-test: ProductRepository unit tests
 * @agent-pattern: Standard repository test
 */
class ProductRepositoryTest extends CIUnitTestCase
{
    private ProductRepository $repo;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->repo = new ProductRepository();
    }

    /** @test */
    public function it_finds_all_active_products()
    {
        // Arrange
        $this->seedProduct(['code' => 'A001', 'status' => 'active']);
        $this->seedProduct(['code' => 'A002', 'status' => 'active']);
        $this->seedProduct(['code' => 'I001', 'status' => 'inactive']);

        // Act
        $results = $this->repo->findAll(['status' => 'active']);

        // Assert
        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_searches_by_code_or_name()
    {
        // Arrange
        $this->seedProduct(['code' => 'SEARCH001', 'name' => 'Findable']);
        $this->seedProduct(['code' => 'OTHER002', 'name' => 'Different']);

        // Act
        $results = $this->repo->findAll(['search' => 'SEARCH']);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('SEARCH001', $results[0]['code']);
    }

    // ... more tests
}
```
