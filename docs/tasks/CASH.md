# CASH-001+002 - Cash Management (Full Stack)

---

**task_id:** CASH-001+002 (merged)

**priority:** CRITICAL

**estimate:** 5 days

**dependencies:** None

**repo:** backend-ci

---

## 🎯 MỤC TIÊU

**Làm gì:**

Implement full-stack cash management (thu chi quỹ) cho hệ thống - từ database layer đến API endpoints.

**Scope:**

- **Phase 1:** Database layer (migration + model)
- **Phase 2:** API layer (service + repository + validator + controller)
- Tracking thu/chi theo categories
- Link với orders, purchase_orders, expenses
- Auto-update balance
- Reports: balance, cash flow, daily summary

**Use cases:**

- Thu tiền từ bán hàng (RECEIPT)
- Chi tiền mua hàng (PAYMENT)
- Chi phí vận hành (PAYMENT)
- Rút tiền, nộp tiền ngân hàng
- Xem báo cáo số dư quỹ
- Xem báo cáo dòng tiền theo ngày/tháng

---

## 📋 YÊU CẦU

### Schema Requirements

**Table:** `cash_transactions`

**Core fields:**

- `id`: BIGINT PRIMARY KEY AUTO_INCREMENT
- `type`: ENUM('RECEIPT', 'PAYMENT') - Thu/Chi
- `amount`: DECIMAL(12,2) - Số tiền (always positive)
- `category`: VARCHAR(50) - Loại (sales, purchase, salary, expense, transfer...)
- `description`: TEXT - Mô tả chi tiết

**Reference fields:**

- `reference_type`: VARCHAR(50) NULL - order, purchase_order, expense, manual
- `reference_id`: BIGINT NULL - ID của order/purchase tương ứng
- `reference_code`: VARCHAR(100) NULL - Mã tham chiếu (HD001, PO001...)

**Context fields:**

- `branch_id`: BIGINT - Chi nhánh
- `created_by`: BIGINT - User thực hiện
- `transaction_date`: DATE - Ngày giao dịch
- `note`: TEXT NULL - Ghi chú thêm

**Timestamps:**

- `created_at`, `updated_at`, `deleted_at`

**Indexes:**

- Primary key: id
- Index: type, category, branch_id
- Index: reference_type, reference_id (composite)
- Index: transaction_date
- Index: created_by

**Foreign Keys:**

- branch_id → branches(id)
- created_by → users(id)

### API Requirements

**Endpoints:**

- `POST /api/cash/receipt` - Tạo phiếu thu
- `POST /api/cash/payment` - Tạo phiếu chi
- `GET /api/cash/transactions` - List transactions (with filters)
- `GET /api/cash/transactions/:id` - Chi tiết 1 transaction
- `GET /api/cash/balance` - Số dư quỹ hiện tại
- `GET /api/cash/balance/branch/:branch_id` - Số dư theo chi nhánh
- `GET /api/cash/report/daily` - Báo cáo thu chi theo ngày
- `DELETE /api/cash/transactions/:id` - Soft delete transaction

**Validation rules:**

- amount > 0
- transaction_date không được future date
- category phải trong danh sách allowed
- branch_id must exist và active
- reference_type + reference_id phải consistent

**Business logic:**

- Auto-calculate balance after each transaction
- Validate reference (order/purchase_order) must exist
- Track created_by for audit trail
- Soft delete only (không hard delete)

---

## 🗄️ FILE STRUCTURE

```jsx
backend-ci/app/
├── Database/
│   └── Migrations/
│       └── YYYY-MM-DD-XXXXXX_CreateCashTransactionsTable.php  # NEW (Phase 1)
├── Models/
│   └── CashTransactionModel.php                                # NEW (Phase 1)
├── Services/
│   └── CashTransactionService.php                              # NEW (Phase 2)
├── Repositories/
│   └── CashTransactionRepository.php                           # NEW (Phase 2)
├── Validators/
│   └── CashTransactionValidator.php                            # NEW (Phase 2)
└── Controllers/
    └── CashTransactionsController.php                          # NEW (Phase 2)
```

---

## 🛠️ IMPLEMENTATION GUIDE

## PHASE 1: DATABASE LAYER

### Bước 1.1: Tạo Migration

**File:** `app/Database/Migrations/YYYY-MM-DD-XXXXXX_CreateCashTransactionsTable.php`

**Class:** `CreateCashTransactionsTable extends Migration`

**Method `up()`:**

**Logic flow:**

1. Define all fields theo schema trên
2. Set type ENUM: RECEIPT, PAYMENT
3. Add primary key (id)
4. Add indexes:
    - idx_type_category (type, category)
    - idx_branch (branch_id)
    - idx_reference (reference_type, reference_id)
    - idx_transaction_date (transaction_date)
    - idx_created_by (created_by)
5. Add foreign keys:
    - branch_id FK branches(id) ON DELETE RESTRICT
    - created_by FK users(id) ON DELETE RESTRICT
6. Create table

**Method `down()`:**

- Drop foreign keys first
- Drop table cash_transactions

**Inline docs:**

```php
/**
 * @agent-migration: Cash transactions table
 * @agent-pattern: Financial tracking schema
 */
```

---

### Bước 1.2: Tạo Model (Passive)

**File:** `app/Models/CashTransactionModel.php`

**Class:** `CashTransactionModel extends Model`

**Properties cần define:**

- `$table = 'cash_transactions'`
- `$primaryKey = 'id'`
- `$allowedFields`: tất cả fields trừ id, created_at, updated_at
- `$useTimestamps = true`
- `$useSoftDeletes = true`
- `$dateFormat = 'datetime'`

**Validation rules (basic):**

```php
protected $validationRules = [
    'type' => 'required|in_list[RECEIPT,PAYMENT]',
    'amount' => 'required|decimal|greater_than[0]',
    'category' => 'required|max_length[50]',
    'branch_id' => 'required|is_natural_no_zero',
    'created_by' => 'required|is_natural_no_zero',
    'transaction_date' => 'required|valid_date',
];
```

**Categories constants (define trong model):**

```php
// RECEIPT categories
const CATEGORY_SALES = 'sales';
const CATEGORY_REFUND = 'refund';
const CATEGORY_DEPOSIT = 'deposit';
const CATEGORY_OTHER_INCOME = 'other_income';

// PAYMENT categories
const CATEGORY_PURCHASE = 'purchase';
const CATEGORY_SALARY = 'salary';
const CATEGORY_EXPENSE = 'expense';
const CATEGORY_WITHDRAWAL = 'withdrawal';
const CATEGORY_OTHER_EXPENSE = 'other_expense';
```

**Inline docs:**

```php
/**
 * @agent-model: Cash transactions tracking
 * @agent-pattern: Passive schema-only model
 * @agent-usage: Extend with service/repository layers
 */
```

---

## PHASE 2: API LAYER

### Bước 2.1: Tạo Validator

**File:** `app/Validators/CashTransactionValidator.php`

**Class:** `CashTransactionValidator`

**Methods cần implement:**

**1. `validateReceipt(array $data): array`**

- Validate type = RECEIPT
- Validate amount > 0
- Validate category in allowed receipt categories
- Validate transaction_date <= today
- Validate branch_id exists
- Return validated data hoặc throw ValidationException

**2. `validatePayment(array $data): array`**

- Validate type = PAYMENT
- Validate amount > 0
- Validate category in allowed payment categories
- Validate transaction_date <= today
- Validate branch_id exists
- Return validated data hoặc throw ValidationException

**3. `validateReference(string $type, int $id): bool`**

- If reference_type = 'order': check order exists
- If reference_type = 'purchase_order': check purchase_order exists
- If reference_type = 'manual': return true (no check)
- Throw exception if not found

**Validation rules:**

- Amount: decimal, > 0, max 12 digits
- Category: must be in allowed list (dùng constants từ Model)
- Transaction date: valid date format, <= today
- Branch: must exist và status = active
- Reference: must exist if provided

**Inline docs:**

```php
/**
 * @agent-validator: Cash transaction validation
 * @agent-pattern: Thin validator, delegate to model rules
 */
```

---

### Bước 2.2: Tạo Repository

**File:** `app/Repositories/CashTransactionRepository.php`

**Class:** `CashTransactionRepository`

**Methods cần implement:**

**1. `create(array $data): array`**

- Insert new transaction
- Return created record

**2. `findById(int $id): ?array`**

- Get transaction by id
- Include soft-deleted check
- Return null if not found

**3. `list(array $filters, int $page, int $limit): array`**

- Get paginated list
- Filters: type, category, branch_id, date_from, date_to, reference_type
- Order by transaction_date DESC, id DESC
- Return ['data' => [...], 'total' => N]

**4. `calculateBalance(?int $branchId = null): float`**

- Sum RECEIPT - PAYMENT
- If branch_id provided: filter by branch
- If null: all branches
- Return total balance

**5. `getDailySummary(string $date, ?int $branchId = null): array`**

- Get transactions for specific date
- Group by type
- Return ['receipt_total' => X, 'payment_total' => Y, 'balance' => Z]

**6. `softDelete(int $id): bool`**

- Soft delete transaction
- Return success/failure

**Query optimization notes:**

- Use indexes for date range queries
- Use composite index for reference lookups
- Cache balance calculations if needed

**Inline docs:**

```php
/**
 * @agent-repository: Cash transaction queries
 * @agent-pattern: Repository pattern - raw SQL/Query Builder
 */
```

---

### Bước 2.3: Tạo Service

**File:** `app/Services/CashTransactionService.php`

**Class:** `CashTransactionService`

**Dependencies:**

- CashTransactionRepository
- CashTransactionValidator

**Methods cần implement:**

**1. `createReceipt(array $data): array`**

**Logic flow:**

1. Validate receipt data (call validator)
2. Set type = RECEIPT
3. Set created_by from auth user
4. Validate reference if provided
5. Insert via repository
6. Return created transaction

**2. `createPayment(array $data): array`**

**Logic flow:**

1. Validate payment data (call validator)
2. Set type = PAYMENT
3. Set created_by from auth user
4. Validate reference if provided
5. Insert via repository
6. Return created transaction

**3. `getTransaction(int $id): array`**

- Get transaction by id
- Throw NotFoundException if not found
- Return transaction data

**4. `listTransactions(array $filters, int $page = 1, int $limit = 20): array`**

- Call repository list() with filters
- Return paginated results

**5. `getBalance(?int $branchId = null): array`**

- Calculate current balance via repository
- Return ['branch_id' => X, 'balance' => Y, 'as_of' => NOW]

**6. `getDailyReport(string $date, ?int $branchId = null): array`**

- Get daily summary via repository
- Add calculated fields (net = receipt - payment)
- Return report data

**7. `deleteTransaction(int $id): bool`**

- Check transaction exists
- Soft delete via repository
- Return success

**Business rules:**

- Amount always stored as positive (type determines +/-)
- Cannot delete if transaction_date < 30 days ago (configurable)
- Cannot edit after created (only delete + recreate)

**Inline docs:**

```php
/**
 * @agent-service: Cash transaction business logic
 * @agent-pattern: Service layer - thin controller delegate
 */
```

---

### Bước 2.4: Tạo Controller

**File:** `app/Controllers/CashTransactionsController.php`

**Class:** `CashTransactionsController extends BaseController`

**Dependencies:**

- CashTransactionService

**Routes:**

```php
$routes->group('api/cash', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->post('receipt', 'CashTransactionsController::createReceipt');
    $routes->post('payment', 'CashTransactionsController::createPayment');
    $routes->get('transactions', 'CashTransactionsController::list');
    $routes->get('transactions/(:num)', 'CashTransactionsController::show/$1');
    $routes->get('balance', 'CashTransactionsController::getBalance');
    $routes->get('balance/branch/(:num)', 'CashTransactionsController::getBranchBalance/$1');
    $routes->get('report/daily', 'CashTransactionsController::dailyReport');
    $routes->delete('transactions/(:num)', 'CashTransactionsController::delete/$1');
});
```

**Methods cần implement:**

**1. `createReceipt(): Response`**

**Logic flow:**

1. Get POST data
2. Call service->createReceipt()
3. Return 201 Created with transaction data
4. Catch validation errors → 400
5. Catch other errors → 500

**Request body:**

```json
{
  "amount": 500000,
  "category": "sales",
  "description": "Bán hàng HD001",
  "branch_id": 1,
  "transaction_date": "2025-11-26",
  "reference_type": "order",
  "reference_id": 123,
  "reference_code": "HD001",
  "note": "Thanh toán tiền mặt"
}
```

**2. `createPayment(): Response`**

- Similar to createReceipt
- Call service->createPayment()

**3. `list(): Response`**

**Query params:**

- type: RECEIPT|PAYMENT
- category: string
- branch_id: int
- date_from: YYYY-MM-DD
- date_to: YYYY-MM-DD
- reference_type: string
- page: int (default 1)
- limit: int (default 20)

**Logic flow:**

1. Get query params
2. Build filters array
3. Call service->listTransactions()
4. Return 200 with paginated data

**4. `show(int $id): Response`**

- Call service->getTransaction($id)
- Return 200 with transaction
- Catch NotFoundException → 404

**5. `getBalance(): Response`**

- Call service->getBalance()
- Return 200 with balance data

**6. `getBranchBalance(int $branchId): Response`**

- Call service->getBalance($branchId)
- Return 200 with branch balance

**7. `dailyReport(): Response`**

**Query params:**

- date: YYYY-MM-DD (required)
- branch_id: int (optional)

**Logic flow:**

1. Get query params
2. Validate date format
3. Call service->getDailyReport()
4. Return 200 with report

**8. `delete(int $id): Response`**

- Call service->deleteTransaction($id)
- Return 204 No Content
- Catch errors → 400/404/500

**Response format:**

```json
{
  "success": true,
  "message": "Created successfully",
  "data": { ... }
}
```

**Error response:**

```json
{
  "success": false,
  "message": "Validation error",
  "errors": { ... }
}
```

**Inline docs:**

```php
/**
 * @agent-controller: Cash transactions API
 * @agent-pattern: Thin controller - delegate to service
 */
```

---

## 🧪 TESTING REQUIREMENTS

### Phase 1: Migration & Model Tests

**Manual verification:**

```bash
# Run migration
php spark migrate

# Check table created
php spark db:table cash_transactions

# Check indexes
SHOW INDEX FROM cash_transactions;

# Check foreign keys
SHOW CREATE TABLE cash_transactions;
```

**Test cases:**

- ✅ Migration runs without errors
- ✅ Table created with correct schema
- ✅ All indexes exist
- ✅ Foreign keys properly constrained
- ✅ ENUM values correct (RECEIPT, PAYMENT)
- ✅ Soft delete column exists (deleted_at)

---

### Phase 2: API Tests

**Unit tests:**

**CashTransactionValidator tests:**

- ✅ Valid receipt data passes
- ✅ Valid payment data passes
- ✅ Invalid amount rejected (<= 0)
- ✅ Invalid category rejected
- ✅ Future transaction_date rejected
- ✅ Invalid branch_id rejected
- ✅ Reference validation works

**CashTransactionRepository tests:**

- ✅ create() inserts record
- ✅ findById() returns correct record
- ✅ list() with filters works
- ✅ calculateBalance() correct for branch
- ✅ calculateBalance() correct for all branches
- ✅ getDailySummary() groups correctly
- ✅ softDelete() sets deleted_at

**CashTransactionService tests:**

- ✅ createReceipt() creates RECEIPT
- ✅ createPayment() creates PAYMENT
- ✅ getTransaction() throws on not found
- ✅ listTransactions() respects filters
- ✅ getBalance() returns correct format
- ✅ getDailyReport() calculates net
- ✅ deleteTransaction() soft deletes

**Integration tests:**

**API endpoint tests:**

```bash
# Test create receipt
POST /api/cash/receipt
Body: { amount: 100000, category: "sales", ... }
Expected: 201, transaction created

# Test create payment
POST /api/cash/payment
Body: { amount: 50000, category: "expense", ... }
Expected: 201, transaction created

# Test list transactions
GET /api/cash/transactions?branch_id=1&date_from=2025-11-01
Expected: 200, array of transactions

# Test get transaction
GET /api/cash/transactions/1
Expected: 200, transaction details

# Test balance
GET /api/cash/balance
Expected: 200, { balance: 50000 }

# Test branch balance
GET /api/cash/balance/branch/1
Expected: 200, { branch_id: 1, balance: 50000 }

# Test daily report
GET /api/cash/report/daily?date=2025-11-26
Expected: 200, { receipt_total, payment_total, balance }

# Test delete
DELETE /api/cash/transactions/1
Expected: 204
```

**Test scenarios:**

- ✅ Receipt increases balance
- ✅ Payment decreases balance
- ✅ Multiple branches track separately
- ✅ Date filters work correctly
- ✅ Reference linking to orders works
- ✅ Soft delete doesn't affect balance
- ✅ Validation errors return 400
- ✅ Not found returns 404

---

## ✅ DEFINITION OF DONE

### Phase 1: Database Layer

- [ ]  Migration created: CreateCashTransactionsTable.php
- [ ]  Migration runs successfully
- [ ]  Model created: CashTransactionModel.php
- [ ]  Model validation rules defined
- [ ]  Category constants defined
- [ ]  Table exists with correct schema
- [ ]  All indexes created
- [ ]  Foreign keys working
- [ ]  Soft delete enabled

### Phase 2: API Layer

- [ ]  Validator created with all methods
- [ ]  Repository created with all methods
- [ ]  Service created with all methods
- [ ]  Controller created with all endpoints
- [ ]  Routes registered in Config/Routes.php
- [ ]  All inline docs have @agent- tags
- [ ]  Unit tests written for validator
- [ ]  Unit tests written for repository
- [ ]  Unit tests written for service
- [ ]  Integration tests written for API
- [ ]  All tests passing
- [ ]  Can create receipt via API
- [ ]  Can create payment via API
- [ ]  Can list transactions with filters
- [ ]  Can get balance (all & by branch)
- [ ]  Can get daily report
- [ ]  Can soft delete transaction
- [ ]  Validation errors return proper 400 responses
- [ ]  Balance calculation accurate

---

## 📚 REFERENCE PATTERNS

**Copy migration pattern từ:**

- Existing migrations trong `app/Database/Migrations/`
- Example: `CreateProductsTable.php`, `CreateOrdersTable.php`

**Copy model pattern từ:**

- `app/Models/ProductModel.php` (passive model example)

**Copy validator pattern từ:**

- `app/Validators/ProductValidator.php`
- Use existing validation rules

**Copy repository pattern từ:**

- `app/Repositories/ProductRepository.php`
- Query Builder hoặc raw SQL

**Copy service pattern từ:**

- `app/Services/ProductService.php`
- Thin controller delegate pattern

**Copy controller pattern từ:**

- `app/Controllers/ProductsController.php`
- RESTful API pattern

**Schema reference:**

- KiotViet cash management: https://kiotviet.vn/quan-ly-doanh-thu-va-chi-phi-hieu-qua/

**CI4 docs:**

- Migrations: [codeigniter4.github.io/userguide/dbmgmt/migration.html](http://codeigniter4.github.io/userguide/dbmgmt/migration.html)
- Models: [codeigniter4.github.io/userguide/models/model.html](http://codeigniter4.github.io/userguide/models/model.html)
- Validation: CI4 validation documentation

---

## 🎯 BUSINESS RULES (for reference)

### Type Categories Mapping

**RECEIPT (Thu):**

- `sales`: Thu từ bán hàng (reference_type=order)
- `refund`: Hoàn tiền từ nhà cung cấp
- `deposit`: Tiền đặt cọc từ khách
- `other_income`: Thu nhập khác

**PAYMENT (Chi):**

- `purchase`: Chi mua hàng (reference_type=purchase_order)
- `salary`: Chi lương nhân viên
- `expense`: Chi phí vận hành (điện, nước, thuê...)
- `withdrawal`: Rút tiền nộp ngân hàng
- `other_expense`: Chi phí khác

### Reference Linking

- **Orders:** reference_type='order', reference_id=order_id, reference_code='HD001'
- **Purchase orders:** reference_type='purchase_order', reference_id=po_id, reference_code='PO001'
- **Manual:** reference_type='manual', reference_id=NULL

### Validation Notes

- Amount must be > 0 (always positive, type determines in/out)
- transaction_date cannot be future date
- Branch must exist and be active
- User must exist
- Cannot delete transactions older than 30 days (configurable)

### Balance Calculation

- Balance = SUM(RECEIPT amounts) - SUM(PAYMENT amounts)
- Calculate by branch or all branches
- Exclude soft-deleted transactions
- Cache if performance issue

---

## 🚀 EXECUTION PLAN

### Phase 1: Database Layer

1. Create migration
2. Run migration & verify schema
3. Create model with validation
4. Test model insert/update
5. ✅ Phase 1 complete

### Phase 2: API Layer - Step 1 (Validator + Repository)

1. Create validator with all rules
2. Write validator unit tests
3. Create repository with all queries
4. Write repository unit tests
5. ✅ Test coverage for data layer

### Phase 2: API Layer - Step 2 (Service + Controller)

1. Create service with business logic
2. Write service unit tests
3. Create controller with all endpoints
4. Register routes
5. ✅ API structure complete

### Phase 2: API Layer - Step 3 (Integration Testing)

1. Write integration tests for all endpoints
2. Test balance calculations
3. Test date filtering
4. Test reference linking
5. Fix bugs & edge cases
6. ✅ Full stack complete

---

---

**Reference docs:**

- KiotViet cash management: https://kiotviet.vn/quan-ly-doanh-thu-va-chi-phi-hieu-qua/
- CI4 migrations: [codeigniter4.github.io/userguide/dbmgmt/migration.html](http://codeigniter4.github.io/userguide/dbmgmt/migration.html)
- CI4 models: [codeigniter4.github.io/userguide/models/model.html](http://codeigniter4.github.io/userguide/models/model.html)

**Last updated:** 2025-11-26