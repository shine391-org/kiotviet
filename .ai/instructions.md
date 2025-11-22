# AI Agent Instructions - LANO CRM Backend

VERSION: 2.0
UPDATED: 2025-11-21
PROJECT: LANO CRM (KiotViet Clone)
TECH STACK: PHP 8.4 + CodeIgniter 4.5 + MySQL 8.4

---

## QUAN TRỌNG: ĐỌC ĐẦU TIÊN

Bạn đang refactor backend PHP (CodeIgniter 4) từ fat controllers sang clean architecture.

TÀI LIỆU CHÍNH:
1. AGENTS.md - Hướng dẫn kiến trúc, patterns, workflow
2. docs/plans/BACKEND-REFACTOR-PLAN.md - Kế hoạch 8 tuần
3. docs/tasks/refactor/REFACTOR-XXX.md - Tasks refactor
4. docs/tasks/new-modules/TASK-XXX.md - Tasks modules mới

---

## KIẾN TRÚC CLEAN (BẮT BUỘC)

Controller (routing only)
↓ Service (business logic) 
↓ Repository (database u vv)

KHÔNG trộn logic giữa các layers!

---

## WORKFLOW (TỪNG BƯỚC)

1. ĐỌC tài liệu: AGENTS.md + task file
2. IMPLEMENT theo thứ tự: Validator → Repository → Service → Controller
3. VIẾT tests (unit + integration)
4. TỰ KIỂM TRA:
docker exec meomeo2-api-1 vendor/bin/phpunit
bash .ai/pre-commit-checks.sh
5. COMMIT chỉ khi tests pass
6. TẠO session log: docs/session-logs/YYYY-MM-DD-TASK-XXX.md

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

## PATTERNS CHUẨN (Copy từ AGENTS.md)

### Controller (Thin):
public function index()
{
try {
$filters = $this->request->getGet();
$result = $this->service->list($filters);
return $this->respond($result);
} catch (\Exception $e) {
return $this->fail($e->getMessage(), 500);
}
}

### Service (Business Logic):
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


### Repository (Database):
public function findAll(array $filters): array
{
$builder = $this->model->builder()->where('deleted_at', null);

if (!empty($filters['search'])) {
    $builder->like('name', $filters['search']);
}

$page = $filters['page'] ?? 1;
$limit = $filters['limit'] ?? 20;
$offset = ($page - 1) * $limit;

return $builder->limit($limit, $offset)->get()->getResultArray();
}


---

## PHẠM VI LÀM VIỆC (QUAN TRỌNG)

CHỈ sửa files trong scope task:

REFACTOR-001 (Products):
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
git diff --cached --name-only

2. Tests pass?
docker exec meomeo2-api-1 vendor/bin/phpunit

3. Auth endpoints vẫn work?
curl -X POST http://localhost:8000/api/auth/login
-H "Content-Type: application/json"
-d '{"username":"devadmin","password":"Admin@123"}'

4. Module endpoints work?
curl http://localhost:8000/api/products

5. Inline docs có đủ?
grep -c "@agent-" app/Services/Products/ProductService.php


NẾU BẤT KỲ CHECK NÀO FAIL → ROLLBACK!

git reset --hard HEAD~1


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

Product Service - Business logic layer

@agent-service Products

@agent-pattern Standard CRUD service

@agent-reusable HIGH
*/
class ProductService { }

/**

List products với filters

@agent-method list

@param array $filters

@return array
*/
public function list(array $filters): array { }

text

---

## API RESPONSE FORMAT

### Success:
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


### Error:
{
"success": false,
"message": "Error message",
"errors": {
"field": ["Validation error"]
}
}


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

HÃY:
- ✅ Tuân thủ clean architecture
- ✅ Single## WORKFLOW (CẬP NHẬT)

1. ĐỌC tài liệu: AGENTS.md + task file
2. **VIẾT TESTS TRƯỚC (TDD)**: `docs/testing/TESTING-PATTERNS.md`
3. IMPLEMENT theo thứ tự: Validator → Repository → Service → Controller
4. **CHẠY TESTS**: 
   - Unit: `vendor/bin/phpunit`
   - Integration: `vendor/bin/phpunit -c phpunit.integration.xml`
5. TỰ KIỂM TRA: `bash .ai/pre-commit-checks.sh`
6. COMMIT chỉ khi tests pass
7. TẠO session log với test checklist

## DEFINITION OF DONE (CẬP NHẬT)

Task hoàn thành khi:
- [ ] Files tạo/sửa đúng scope
- [ ] Tuân thủ Single Responsibility
- [ ] Separation of Concerns đúng
- [ ] **Unit tests viết và pass (NEW)**
- [ ] **Integration tests viết và pass (NEW)**
- [ ] **Coverage >= 70% (NEW)**
- [ ] **Test checklist hoàn thành (NEW)**
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
4. Tests PHẢI pass
5. Inline docs BẮT BUỘC
6. Safety checks TRƯỚC commit
7. Rollback nếu phá vỡ

Good luck! 🚀

---

VERSION HISTORY:
- 1.0 (2025-11-21): Initial với hard limits
- 2.0 (2025-11-21): Guidelines linh hoạt, tập trung principles