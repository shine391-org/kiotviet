---
title: "AI Agent Guide - LANO CRM"
id: "AGENT-GUIDE-01"
purpose: "Single source of truth for AI agents on architecture, patterns, and development workflow."
version: "1.0"
status: "Active"
location: "root"
tags: ["guideline", "architecture", "patterns", "testing", "workflow", "agent"]
related_to:
  - id: "TESTING-PATTERNS-01"
    description: "Contains mandatory test patterns to be copied."
  - id: "TESTING-GUIDE-01"
    description: "Explains the backend testing philosophy and process."
  - id: "TESTING-MAIN-DB-01"
    description: "Main database testing approach with transaction rollback."
  - id: "TEST-CHECKLIST-01"
    description: "Mandatory checklist for backend changes."
  - id: "FE-TESTING-GUIDE-01"
    description: "Explains the frontend testing philosophy and process."
  - id: "FE-TESTING-PATTERNS-01"
    description: "Frontend code patterns to copy."
  - id: "FE-TEST-CHECKLIST-01"
    description: "Mandatory checklist for frontend changes."
  - id: "BACKEND-REFACTOR-PLAN-01"
    description: "Overall project roadmap and refactoring plan."
  - id: "DEV-DEMO-SEEDER-01"
    description: "Demo data seeding for development environment."
  - id: "DOC-AUDIT-2025-11-26"
    description: "Documentation audit report with connectivity analysis."
---

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
- ✅ Keep files focused and maintainable

---

### Rule 2: File Size Guidelines

| Layer | Target Size | Acceptable Max | Notes |
|-------|-------------|----------------|-------|
| Controller | 3KB (100 lines) | 5KB (150 lines) | Routing only |
| Service | 7KB (200 lines) | 12KB (350 lines) | Business logic |
| Repository | 5KB (150 lines) | 10KB (300 lines) | DB queries |
| Validator | 2KB (60 lines) | 4KB (120 lines) | Validation rules |

**Guidelines not hard limits:** If file exceeds acceptable max, consider splitting into multiple services/repositories. Focus on single responsibility over strict size limits.

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

### Step 5: Write Tests (MANDATORY)

**You MUST write both unit and integration tests:**

**1. Unit Tests (Required for Services/Repositories):**
- Test all public methods
- Test validation errors
- Test edge cases
- Target: 70%+ coverage
- Uses: SQLite in-memory (fast)

# Create test file
tests/Services/ProductServiceTest.php

# Run unit tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Run specific test file
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/ProductServiceTest.php

# Check coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

**2. Integration Tests (Required for API endpoints):**
- Test HTTP responses (200, 400, 401, etc.)
- Test database writes/reads
- Test authentication/authorization
- Uses: MySQL (real database)

# Run integration tests with MySQL
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Run specific integration test
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/Api/ProductsApiTest.php

**Test Order:** Write unit tests first, then integration tests.
**Both must pass before commit.**

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


## 🧪 Testing (BẮT BUỘC) - MAIN DATABASE WITH TRANSACTIONS ⚠️

### 🚨 BREAKING CHANGE: Main Database Testing (2025-11-25)

**ALL tests now use main database (`lanocrm_shop`) with transaction rollback. Separate test database removed for simplicity.**
**Schema source of truth:** `backend-ci/app/Database/Migrations/2025-11-27-000999_TestSchemaSetup.php` (golden migration).  
**Patterns:** truncate-only schema traits + `DevDatabaseTrait` autoloads golden migration.

### Test-Driven Development
You MUST write tests. No exceptions.

**Order:**
1. Write test first (RED)
2. Implement code (GREEN)  
3. Refactor (REFACTOR)

### Test Types (Main Database + Transactions)

**Unit Tests** (Main Database with Transactions - Fast)
- Service logic
- Repository queries
- Validators
- Uses: DevDatabaseTrait + Transactions
- Database: `lanocrm_shop` (main) with auto-rollback
- Run: `docker exec meomeo2-api-1 vendor/bin/phpunit`

**Integration Tests** (Main Database Full Stack)
- API endpoints
- Database operations
- Authentication flows
- Database: `lanocrm_shop` (main) with real data
- Run: `docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml`

### DevDatabaseTrait Pattern (MANDATORY)

**👉 ALL tests MUST use DevDatabaseTrait:**

```php
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\YourSchemaTrait;

class YourServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;      // Main database connection + transactions
    use YourSchemaTrait;       // Schema creation
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();     // Auto transaction start
        $this->resetYourSchema();   // Create tables
        // Your setup
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();  // Auto rollback - data protection!
        parent::tearDown();
    }
}
```

**🛡️ Data Protection**: Transaction rollback automatically protects your main database data!

### Test Patterns (Copy từ đây)

**👉 BẮT BUỘC: Copy patterns từ file sau:**
`docs/testing/TESTING-PATTERNS.md`

**📖 NEW: Read main database guide:**
`docs/testing/TESTING-MAIN-DB-GUIDE.md`
**📖 NEW: Golden schema:** `backend-ci/app/Database/Migrations/2025-11-27-000999_TestSchemaSetup.php`

File này chứa mẫu chuẩn cho:
- **Service Test** (Main Database with DevDatabaseTrait)
- **Integration Test** (API - Main Database Full Stack)
- **Repository Test** (Main Database)

**Không tự bịa test pattern!** Hãy copy và sửa đổi.

**📗 Documentation Network (YAML-linked):**
- `docs/DOCUMENTATION_INDEX.md` - **CENTRAL INDEX** cho tất cả documentation
- `docs/testing/TESTING-GUIDE.md` - Main testing guide (links to all others)
- `docs/testing/TESTING-PATTERNS.md` - Code patterns to copy
- `docs/testing/TESTING-MAIN-DB-GUIDE.md` - Main database approach
- `docs/testing/TEST-CHECKLIST.md` - Mandatory PR checklist
- `docs/testing/FE-TESTING-GUIDE.md` - Frontend testing guide
- `docs/testing/FE-TESTING-PATTERNS.md` - Frontend code patterns
- `docs/testing/FE-TEST-CHECKLIST.md` - Frontend PR checklist

**📋 Audit Reports (Latest):**
- `docs/audits/2025-11-27-FINAL-TEST-COVERAGE-REPORT.md` - Comprehensive FE/BE test coverage analysis
- `docs/audits/2025-11-27-DOCUMENTATION-CONSOLIDATION-PLAN.md` - Documentation consolidation strategy
- `docs/audits/2025-11-27-CASH-FLOW-AUDIT-REPORT.md` - Cash flow module audit

All documents are now YAML-linked for easy navigation and reference.

### Vấn đề thường gặp

**Q: Test bị lỗi "Connection refused"?**
A: Main database container chưa chạy:
```bash
docker-compose up -d db api
docker exec meomeo2-api-1 php spark db:info
```

**Q: Test bị lỗi "Table doesn't exist"?**
A: Chưa gọi resetSchema trong setUp():
```php
$this->setUpDatabase();
$this->resetYourSchema();  // MUST call this!
```

**Q: Tests pass riêng lẻ, fail khi chạy cùng?**
A: Thiếu tearDownDatabase(). Xem pattern trong `docs/testing/TESTING-PATTERNS.md`

**Q: Data có bị ảnh hưởng sau test không?**
A: KHÔNG! Transaction rollback tự động khôi phục data.

**Đọc thêm**:
- `docs/testing/TESTING-GUIDE.md` (MySQL-only, main DB + rollback)
- `docs/testing/docker-workflow-guide.md` (Docker-only workflow + demo data)
- `docs/seeding/DEV-DEMO-SEEDER.md` (Quy ước seed demo tự động)
- `docs/audits/2025-11-27-FINAL-TEST-COVERAGE-REPORT.md` (Coverage checkpoint)

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
- [x] **Test coverage >= 70%** _(Automatically enforced in CI)_
- [x] **FE Checklist completed (docs/testing/FE-TEST-CHECKLIST.md)** _(Automatically validated in CI)_
- [x] **Inline docs added (@agent- annotations)**
- [x] API endpoints work
- [x] Task status updated
- [x] Session log created

> [!NOTE]
> **Automated Enforcement**: Coverage thresholds and FE checklist completion are automatically enforced in CI/CD pipeline. PRs will fail if coverage < 70% or checklist is incomplete.


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
