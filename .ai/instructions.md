# AI Agent Instructions - LANO CRM Backend

VERSION: 3.1
UPDATED: 2025-11-28
PROJECT: LANO CRM (KiotViet Clone)
TECH STACK: PHP 8.4 + CodeIgniter 4.5 + MySQL 8.4

---

## QUAN TRỌNG: ĐỌC ĐẦU TIÊN (MySQL-Only + Golden Schema)

- Tất cả Unit/Integration tests chạy trên **MySQL thật** (DB group `tests` -> `lanocrm_shop`).
- `DevDatabaseTrait` tự chạy **golden migration** `app/Database/Migrations/2025-11-27-000999_TestSchemaSetup.php` và truncate-only schema traits.
- Không còn SQLite. Không tạo/drop bảng trong test; chỉ truncate.
- Webhook tests dùng **fake repositories in-memory** (tests/_support/Fakes) để tránh lệ thuộc DB/transactions.

TÀI LIỆU CHÍNH:
1. AGENTS.md - Hướng dẫn kiến trúc, patterns, workflow (MySQL-only)
2. docs/DOCUMENTATION_INDEX.md - **Central index** tất cả docs
3. docs/testing/BACKEND-TESTING.md - MySQL testing guide
4. docs/testing/FRONTEND-TESTING.md - React/Vitest/Playwright guide
5. docs/testing/TEST-CHECKLIST.md - Checklist bắt buộc cho PR

**📋 Audit Reports (Latest):**
- docs/audits/2025-11-27-FINAL-TEST-COVERAGE-REPORT.md - Comprehensive FE/BE test coverage analysis
- docs/audits/2025-11-27-DOCUMENTATION-CONSOLIDATION-PLAN.md - Documentation consolidation strategy
- docs/audits/2025-11-27-CASH-FLOW-AUDIT-REPORT.md - Cash flow module audit

---

## KIẾN TRÚC CLEAN (BẮT BUỘC)

Controller (routing only)
↓ Service (business logic)
↓ Repository (database queries)
↓ Model (schema)
↓ DB (MySQL)

KHÔNG trộn logic giữa các layers!

---

## WORKFLOW (TỪNG BƯỚC)

1. ĐỌC tài liệu: AGENTS.md + task file
2. VIẾT TESTS TRƯỚC (TDD) theo patterns MySQL-only
3. IMPLEMENT theo thứ tự: Validator → Repository → Service → Controller
4. CHẠY TESTS (MySQL-only):
   - Full: `docker exec meomeo2-api-1 vendor/bin/phpunit`
   - Integration (legacy file): `docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml` (dùng khi cần)
5. TỰ KIỂM TRA:
   - `docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text`
   - `bash scripts/validate-checklist.sh`
6. COMMIT chỉ khi tests pass
7. TẠO session log: docs/session-logs/YYYY-MM-DD-TASK-XXX.md

---

## NGUYÊN TẮC CODE (Quan trọng hơn số dòng)

### 1. Single Responsibility Principle (SRP)
Một class chỉ làm MỘT việc rõ ràng.

✅ OK: ProductService có 20 methods về products (500 dòng)
❌ SAI: ProductService có methods về products + inventory + orders (300 dòng)

### 2. Separation of Concerns
✅ Controller chỉ routing (dù 250 dòng)
✅ Service chỉ business logic (dù 500 dòng)
✅ Repository chỉ database (dù 400 dòng)

❌ Controller có business logic (dù 80 dòng)
❌ Service có SQL queries (dù 150 dòng)

### 3. Readability > Brevity
Code dễ đọc, dễ maintain > Code ngắn

---

## FILE SIZE GUIDELINES (Linh hoạt)

### Controller
- Mục tiêu: ~100-150 dòng
- Cho phép: Đến 250 dòng nếu nhiều endpoints
- Vượt 250: Tách thành nhiều controllers

### Service
- Mục tiêu: ~200-300 dòng
- Cho phép: Đến 500 dòng cho modules phức tạp
- Vượt 500: Tách thành nhiều services chuyên biệt

### Repository
- Mục tiêu: ~150-200 dòng
- Cho phép: Đến 400 dòng nếu nhiều queries phức tạp
- Vượt 400: Tách queries phức tạp ra QueryBuilder

### Validator
- Mục tiêu: ~60-100 dòng
- Cho phép: Đến 200 dòng cho validation phức tạp
- Vượt 200: Tách thành nhiều validator classes

**KHI NÀO TÁCH:**
- Class vi phạm Single Responsibility
- Một method quá dài (>50 dòng)
- Khó maintain/test
- KHÔNG chỉ vì vượt số dòng mục tiêu

---

## PATTERNS CHUẨN (MySQL-only - Copy từ docs/testing/TESTING-PATTERNS.md)

### Controller (Thin):
```php
public function index()
{
    try {
        $filters = $this->request->getGet();
        $result = $this->service->list($filters);
        return $this->respond($result);
    } catch (\Throwable $e) {
        return $this->fail($e->getMessage(), 500);
    }
}
```

### Service (Business Logic):
```php
public function list(array $filters): array
{
    $validated = $this->validator->validateListFilters($filters);
    $items = $this->repo->findAll($validated);
    $total = $this->repo->count($validated);

    return [
        'success' => true,
        'data' => $items,
        'pagination' => $this->formatPagination($validated, $total)
    ];
}
```

### Repository (Database):
```php
public function findAll(array $filters): array
{
    $builder = $this->model->builder()->where('deleted_at', null);

    if (!empty($filters['search'])) {
        $builder->groupStart()
                ->like('name', $filters['search'])
                ->orLike('code', $filters['search'])
                ->groupEnd();
    }

    $page = $filters['page'] ?? 1;
    $limit = $filters['limit'] ?? 20;
    $offset = ($page - 1) * $limit;

    return $builder->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->get()->getResultArray();
}
```

### Test (MySQL-only) với DevDatabaseTrait:
```php
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\YourSchemaTrait;

class YourServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;      // MySQL connection + transactions
    use YourSchemaTrait;       // Schema creation

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();    // Auto MySQL + transaction
        $this->resetYourSchema();  // Create tables
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase(); // Auto rollback
        parent::tearDown();
    }
}
```

### Webhook tests (in-memory)
- Dùng `FakeWebhookSubscriptionRepository` và `FakeWebhookEventRepository` (tests/_support/Fakes).
- Không DevDatabaseTrait, không DB.  
- Inject fakes vào `WebhookDispatcher` trong setUp test.

---

## PHẠM VI LÀM VIỆC (QUAN TRỌNG)

CHỈ sửa files trong scope task:

Ví dụ REFACTOR-001 (Products):
✅ app/Controllers/Api/ProductsController.php  
✅ app/Services/Products/*  
✅ app/Repositories/Products/*  
✅ app/Validators/ProductValidator.php  
✅ tests/*

❌ app/Config/Routes.php (trừ khi task yêu cầu)  
❌ app/Controllers/Auth.php  
❌ Bất kỳ controller/module khác

NẾU CẦN sửa ngoài scope: DỪNG và hỏi user trước!

---

## SAFETY CHECKS (BẮT BUỘC TRƯỚC COMMIT)

1. Chỉ sửa files trong scope?
```bash
git diff --cached --name-only
```

2. Tests pass (MySQL-only)?
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit
```

3. Auth endpoints vẫn work?
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"devadmin","password":"Admin@123"}'
```

4. Module endpoints work?
```bash
curl http://localhost:8000/api/products
```

5. Inline docs có đủ?
```bash
grep -c "@agent-" app/Services/Products/ProductService.php
```

NẾU BẤT KỲ CHECK NÀO FAIL → ROLLBACK!
```bash
git reset --hard HEAD~1
```

---

## CODE STANDARDS

### PHP:
- PHP 8.4 syntax
- Type hints BẮT BUỘC
- Return types BẮT BUỘC
- PSR-12 formatting
- Zero warnings/errors

### CodeIgniter 4:
- Namespaces: App\Controllers\Api, App\Services\{Module}
- Models extend CodeIgniter\Model
- Controllers extend BaseController
- Dùng ResponseTrait cho API

### Inline Docs BẮT BUỘC:
/**
@agent-service Products  
@agent-pattern Standard CRUD service  
@agent-reusable HIGH
*/
class ProductService { }

/**
@agent-method list
@param array $filters
@return array
*/
public function list(array $filters): array { }

---

## API RESPONSE FORMAT

### Success:
```json
{
  "success": true,
  "data": {...},
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "total_pages": 8
  }
}
```

### Error:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error"]
  }
}
```

---

## LỖI THƯỜNG GẶP (TRÁNH)

ĐỪNG:
- ❌ Business logic trong Controller
- ❌ DB queries trong Service
- ❌ Trộn concerns trong 1 file
- ❌ Bỏ qua inline docs
- ❌ Bỏ qua tests
- ❌ Sửa files ngoài scope
- ❌ Commit khi tests fail

- ✅ Tuân thủ clean architecture
- ✅ Single Responsibility Principle

---

## WORKFLOW (CẬP NHẬT - MySQL-only)

1. ĐỌC tài liệu: AGENTS.md + task file  
2. **VIẾT TESTS TRƯỚC (TDD)**: `docs/testing/TESTING-PATTERNS.md` (DevDatabaseTrait + SchemaTrait)  
3. IMPLEMENT theo thứ tự: Validator → Repository → Service → Controller  
4. **CHẠY TESTS**:
   - Unit (Transactions): `docker exec meomeo2-api-1 vendor/bin/phpunit`
   - Integration (Full Stack): `docker exec meomeo2-api-1 vendor/bin/phpunit -c backend-ci/phpunit.integration.xml`
5. TỰ KIỂM TRA: `bash scripts/validate-checklist.sh`  
6. COMMIT chỉ khi tests pass  
7. TẠO session log với test checklist

---

## COVERAGE MEASUREMENT

**Tool:** PHPUnit + Xdebug/PCOV

**Commands:**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-html coverage/
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-clover coverage.xml
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text
```

**Yêu cầu:**
- **Coverage >= 70%** là **HARD REQUIREMENT**
- Đo coverage cho cả Unit và Integration tests
- CI sẽ kiểm tra coverage tự động (nếu cấu hình)

**Xem chi tiết:** `docs/testing/TESTING-GUIDE.md`

---

## DEFINITION OF DONE (CẬP NHẬT)

Task hoàn thành khi:
- [ ] Files tạo/sửa đúng scope
- [ ] Tuân thủ Single Responsibility & Separation of Concerns
- [ ] **Unit tests (MySQL + transactions) viết và pass**
- [ ] **Integration tests (MySQL full stack) viết và pass**
- [ ] **Coverage >= 70%**
- [ ] **Test checklist hoàn thành**
- [ ] Inline docs đầy đủ
- [ ] API endpoints test OK
- [ ] Safety checks pass
- [ ] Session log tạo
- [ ] Commit đúng format

---

## VỊ TRÍ FILES

Code:
- backend-ci/app/Controllers/Api/
- backend-ci/app/Services/{Module}/
- backend-ci/app/Repositories/{Module}/
- backend-ci/app/Validators/

Tests:
- backend-ci/tests/Services/
- backend-ci/tests/Repositories/
- backend-ci/tests/Integration/

Docs:
- docs/plans/BACKEND-REFACTOR-PLAN.md
- docs/tasks/refactor/REFACTOR-XXX.md
- docs/tasks/new-modules/TASK-XXX.md
- docs/session-logs/YYYY-MM-DD-TASK-XXX.md

---

## ƯU TIÊN HIỆN TẠI

Phase 1: Refactor
- REFACTOR-001: ProductService (BẮT ĐẦU)
- REFACTOR-002: VariantService
- REFACTOR-003: AttributeService

Phase 2: Modules mới
- TASK-001: InventoryModule (CRITICAL)
- TASK-002: CustomersModule
- TASK-003: OrdersModule

---

## NGUYÊN TẮC THEN CHỐT

**"Copy patterns thành công, đừng sáng tạo."**  
**"Quality > Speed"**  
**"Principles > Rules"**

Single Responsibility & Separation of Concerns > Số dòng

---

GHI NHỚ:
1. AGENTS.md là bible
2. Task files là assignments
3. Clean architecture KHÔNG optional
4. Tests PHẢI pass (MySQL-only)
5. Inline docs BẮT BUỘC
6. Safety checks TRƯỚC commit
7. Rollback nếu phá vỡ

Good luck! 🚀

---

VERSION HISTORY:
- 1.0 (2025-11-21): Initial với hard limits
- 2.0 (2025-11-21): Guidelines linh hoạt, tập trung principles
- 3.0 (2025-11-25): MySQL-only testing, DevDatabaseTrait + SchemaTraits, remove SQLite hoàn toàn
