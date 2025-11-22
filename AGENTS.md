# AI Agent Guide - LANO CRM

> **Agent start here!** This is your single source of truth.

---

## 🎯 Your Mission

You are building a **clean, maintainable CRM system** using **CodeIgniter 4 + Clean Architecture**.

**Current state:** Fat controllers (16KB files) - needs refactoring  
**Target state:** Thin controllers (3KB) + Services + Repositories  
**Your job:** Build new modules OR refactor existing ones following clean patterns

---

## 📁 Project Structure

backend-ci/app/
├── Controllers/Api/  → Routing only (3-5KB max per file)
├── Services/         → Business logic (NEW - you'll create this)
├── Repositories/     → Database queries (NEW - you'll create this)
├── Models/           → Schema definition (passive, don't touch)
├── Validators/       → Input validation (NEW)
└── Transformers/     → Data formatting (NEW)

**Tech:** PHP 8.4 + CodeIgniter 4 + MySQL 8.4 + JWT auth

## 🏗️ Architecture Rules (MUST FOLLOW)

### Rule 1: Clean Architecture Layers

Request → Controller → Service → Repository → Model → DB
          (5 lines)   (logic)   (queries)    (schema)

**Never:**
- ❌ Put business logic in Controller
- ❌ Put DB queries in Controller
- ❌ Mix concerns in one file

**Always:**
- ✅ Controller delegates to Service
- ✅ Service contains business logic
- ✅ Repository handles database
- ✅ Each file < 7KB (max 200 lines)

---

### Rule 2: File Size Limits

| Layer | Max Size | Max Lines |
|-------|----------|-----------|
| Controller | 3KB | 100 |
| Service | 7KB | 200 |
| Repository | 5KB | 150 |
| Validator | 2KB | 60 |

**If file > limit:** Split into multiple services/repositories.

## 📝 Code Patterns (Copy These)

### Pattern 1: Controller (Thin)

<?php
namespace App\Controllers\Api;

/**
 * @agent-controller: Products
 * @agent-pattern: Thin controller - routing only
 */
class ProductsController extends BaseController {
    use ResponseTrait;
    
    protected ProductService $service;
    
    public function __construct() {
        $this->service = service('ProductService');
    }
    
    /**
     * List products
     * @agent-method: Standard list pattern
     */
    public function index() {
        $filters = $this->request->getGet();
        $result = $this->service->list($filters);
        return $this->respond($result);
    }
    
    /**
     * Create product
     * @agent-pattern: REUSE for all create endpoints
     */
    public function create() {
        $data = $this->request->getJSON(true);
        $product = $this->service->create($data);
        return $this->respondCreated(['data' => $product]);
    }
}

**Key points:**
- Controller = 5 lines per method
- No business logic
- No validation here
- Delegate everything to Service

### Pattern 2: Service (Business Logic)

<?php
namespace App\Services\Products;

/**
 * @agent-service: Product business logic
 * @agent-reusable: HIGH
 */
class ProductService {
    
    protected ProductRepository $repo;
    protected ProductValidator $validator;
    
    public function __construct() {
        $this->repo = new ProductRepository();
        $this->validator = new ProductValidator();
    }
    
    /**
     * List products with filters
     * @agent-pattern: Standard list pattern - COPY THIS
     */
    public function list(array $filters): array {
        // 1. Validate
        $validated = $this->validator->validateListFilters($filters);
        
        // 2. Get data
        $products = $this->repo->findAll($validated);
        $total = $this->repo->count($validated);
        
        // 3. Format response
        return [
            'success' => true,
            'data' => $products,
            'pagination' => $this->formatPagination($validated, $total)
        ];
    }
    
    /**
     * Create product
     * @agent-pattern: Standard create - COPY THIS
     */
    public function create(array $data): array {
        // 1. Validate
        $validated = $this->validator->validateCreate($data);
        
        // 2. Business rules
        if ($this->repo->codeExists($validated['code'])) {
            throw new \Exception('Code already exists');
        }
        
        // 3. Create
        $product = $this->repo->create($validated);
        
        // 4. Events (optional)
        // event('product.created', $product);
        
        return $product;
    }
}

### Pattern 3: Repository (Database)

<?php
namespace App\Repositories\Products;

use App\Models\ProductModel;

/**
 * @agent-repository: Product database operations
 */
class ProductRepository {
    
    protected ProductModel $model;
    
    public function __construct() {
        $this->model = new ProductModel();
    }
    
    /**
     * Find all with filters
     * @agent-pattern: Standard query pattern
     */
    public function findAll(array $filters): array {
        $builder = $this->model->builder()->where('deleted_at', null);
        
        // Apply filters
        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('code', $filters['search'])
                    ->orLike('name', $filters['search'])
                    ->groupEnd();
        }
        
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        
        // Pagination
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        
        return $builder->orderBy('created_at', 'DESC')
                       ->limit($limit, $offset)
                       ->get()->getResultArray();
    }
    
    public function create(array $data): array {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->model->insert($data);
        $data['id'] = $this->model->getInsertID();
        return $data;
    }
    
    public function codeExists(string $code): bool {
        return $this->model->where('code', $code)
                          ->where('deleted_at', null)
                          ->countAllResults() > 0;
    }
}

## 🔄 Your Workflow

### Step 1: Read Task
# Task file location
docs/tasks/TASK-XXX.md  (for new modules)
docs/tasks/REFACTOR-XXX.md  (for refactoring)

### Step 2: Analyze Existing Code
# If refactoring, read current code:
backend-ci/app/Controllers/Api/ProductsController.php

# Identify:
- What logic to extract
- What patterns to reuse
- What tests exist

### Step 3: Create Files (Order matters!)

**Order:**
1. **Validator** first (validation rules)
2. **Repository** second (database)
3. **Service** third (business logic)
4. **Controller** last (routing)

**Why?** Service needs Repository, Controller needs Service.

### Step 4: Add Inline Docs

Every file you create must have:
/**
 * [Class purpose]
 * 
 * @agent-[layer]: [Description]
 * @agent-pattern: [Pattern name]
 * @agent-reusable: HIGH|MEDIUM|LOW
 */

Every important method:
/**
 * [Method purpose]
 * @agent-use: When to use this
 * @agent-pattern: Pattern to copy
 */

### Step 5: Write Tests
# Create test file
tests/Services/ProductServiceTest.php

# Run tests
docker exec meomeo2-api-1 vendor/bin/phpunit

### Step 6: Update Task Status
# In task file, change:
- [ ] Create service
# To:
- [x] Create service

### Step 7: Create Session Log
# Create file:
docs/session-logs/YYYY-MM-DD-TASK-XXX.md

# Content:
- What you did
- Files created/modified
- Tests passed
- Issues encountered

## 🚫 Common Mistakes (Don't Do This!)

### ❌ Mistake 1: Fat Controller
// BAD - Logic in controller
public function index() {
    $products = $this->model->where('deleted_at', null)->findAll();
    // ... 50 more lines of logic
    return $this->respond($products);
}

### ✅ Correct:
// GOOD - Delegate to service
public function index() {
    $result = $this->service->list($this->request->getGet());
    return $this->respond($result);
}

---

### ❌ Mistake 2: Direct DB in Service
// BAD - DB query in service
public function list() {
    $db = \Config\Database::connect();
    $products = $db->table('products')->get();
}

### ✅ Correct:
// GOOD - Use repository
public function list() {
    return $this->repo->findAll();
}

---


## 🧪 Testing (BẮT BUỘC)

### Test-Driven Development
You MUST write tests. No exceptions.

**Order:**
1. Write test first (RED)
2. Implement code (GREEN)
3. Refactor (REFACTOR)

### Test Types

**Unit Tests** (SQLite - Fast)
- Service logic
- Repository queries
- Validators
- Run: `vendor/bin/phpunit`

**Integration Tests** (MySQL - Real)
- API endpoints
- Database operations
- Authentication flows
- Run: `vendor/bin/phpunit -c backend-ci/phpunit.integration.xml`

### Test Patterns (Copy từ đây)

**Xem chi tiết**: `docs/testing/TESTING-PATTERNS.md`

**Service Test** (60% tests của bạn):
```php
class ProductServiceTest extends CIUnitTestCase {
    /** @test */
    public function it_creates_product() {
        // Arrange
        $data = ['code' => 'P001', 'name' => 'Product'];
        
        // Act
        $result = $this->service->create($data);
        
        // Assert
        $this->assertTrue($result['success']);
    }
}
```

**Integration Test** (30% tests của bạn):
```php
class ProductsApiTest extends FeatureTestCase {
    /** @test */
    public function it_creates_via_api() {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->post('/api/products', ['code' => 'P001']);
        
        $response->assertStatus(201);
    }
}
```

### Vấn đề thường gặp

**Q: PHPUnit pass nhưng dev server fail?**
A: Bạn chỉ chạy unit tests (SQLite). Chạy integration tests với MySQL:
`docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml`

**Q: Tests pass riêng lẻ, fail khi chạy cùng?**
A: Data không được cleanup. Xem pattern trong `docs/testing/TESTING-PATTERNS.md`

**Đọc thêm**: `docs/testing/TESTING-GUIDE.md`

---

## ⚡ Quy trình testing FE (Frontend)

**Tài liệu chi tiết:**
- 📘 **Guide**: `docs/testing/FE-TESTING-GUIDE.md`
- 🧩 **Patterns**: `docs/testing/FE-TESTING-PATTERNS.md`
- ✅ **Checklist**: `docs/testing/FE-TEST-CHECKLIST.md`

### FE Testing: Best Practices
1. **Unit Test**: Test logic & render. Mock hết API.
2. **Integration**: Test flow (Form -> Submit -> API -> Success).
3. **Manual**: Luôn mở Chrome Console check đỏ/vàng trước khi commit.
4. **Coverage**: Đạt tối thiểu 70%. Chạy `npm run test:coverage` để kiểm tra.

**Lệnh quan trọng:**
- `npm test`: Chạy unit/integration tests.
- `npm run test:coverage`: Kiểm tra độ bao phủ.
- `npm run test:e2e`: Chạy test luồng người dùng thật.

---

## Definition of Done (CẬP NHẬT)

A task is complete when:
- [x] All files created
- [x] Clean architecture followed
- [x] **Unit tests written and pass (Backend + Frontend)**
- [x] **Integration tests written and pass**
- [x] **Test coverage >= 70%**
- [x] **FE Checklist completed (docs/testing/FE-TEST-CHECKLIST.md)**
- [x] API endpoints work
- [x] Task status updated
- [x] Session log created


---

**Key Principle:** 
**"Thiếu test sẽ không merge, lặp lại test process đến khi đạt yêu cầu!"** 
> "Copy successful patterns, don't reinvent. Quality > speed."

**Remember:**
- Clean architecture is NOT optional
- File size limits are hard limits
- Tests must pass before PR
- Inline docs are required

---

**Start with:** Read task file → Analyze existing code → Follow patterns → Test → Done ✅