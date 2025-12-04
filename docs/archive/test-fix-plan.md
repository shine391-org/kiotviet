# Test Fix Plan for Database Schema Changes

## Overview
This document outlines the systematic approach to fix test failures after recent database schema changes, including soft delete implementation, constraint additions, and master entity foreign key requirements.

## Recent Schema Changes Impacting Tests

### 1. Soft Delete Implementation
- **Tables affected**: `order_items`, `delivery_note_items`, `returns`, `return_items`, `stock_reconciliation_items`, `subscription_cycles`
- **Impact**: Models now filter `deleted_at IS NULL` by default
- **Fix needed**: Use `withDeleted()` in assertions for soft delete tests

### 2. New Check Constraints
- `orders.total`: CHECK (total >= 0)
- `cash_transactions.amount`: CHECK (amount > 0)
- **Impact**: Tests with negative values will fail
- **Fix needed**: Update test data to use valid values

### 3. New NOT NULL Constraints
- `orders.order_number`: NOT NULL
- **Impact**: Tests creating orders without order_number will fail
- **Fix needed**: Generate valid order numbers in test fixtures

### 4. Unique Constraints
- `product_category_links`: UNIQUE (product_id, category_id)
- **Impact**: Duplicate category links will fail
- **Fix needed**: Remove duplicates or handle unique violations

### 5. New Foreign Key Dependencies
- **Master tables added**: `organizations`, `customer_groups`, `devices`, `suppliers`
- **FK relationships**: 
  - `customers.customer_group_id` → `customer_groups.id`
  - `customers.organization_id` → `organizations.id`
  - `orders.customer_group_id` → `customer_groups.id`
  - `purchase_invoices.supplier_id` → `suppliers.id`
  - `subcontracting_orders.supplier_id` → `suppliers.id`
  - `pos_offline_queue.device_id` → `devices.id`
- **Impact**: Tests must create master entities before child records
- **Fix needed**: Update seed methods to create dependencies first

## Systematic Fix Approach

### Phase 1: Environment Setup & Test Discovery
1. **Set up test environment**
   ```bash
   cd backend-ci
   docker-compose up -d db api
   docker exec meomeo2-api-1 vendor/bin/phpunit --version
   ```

2. **Run initial test suite to capture failures**
   ```bash
   docker exec meomeo2-api-1 vendor/bin/phpunit --colors=always > test-failures.log 2>&1
   ```

3. **Analyze failure patterns**
   - Extract constraint violation messages
   - Group by error type (soft delete, check constraints, FK violations)
   - Create failure matrix spreadsheet

### Phase 2: Create Test Helper Infrastructure
1. **Create MasterEntityTestHelper trait**
   ```php
   trait MasterEntityTestHelper
   {
       protected function createCustomerGroup(array $data = []): int
       protected function createOrganization(array $data = []): int  
       protected function createSupplier(array $data = []): int
       protected function createDevice(array $data = []): int
   }
   ```

2. **Update existing test traits**
   - Add master entity creation methods
   - Ensure proper FK dependency ordering
   - Add soft delete assertion helpers

### Phase 3: Fix Constraint Violations

#### 3.1 Cash Transaction Amount Constraints
**Target files**: 
- `tests/Services/CashTransactionServiceTest.php`
- `tests/Integration/Api/CashTransactionsApiTest.php`

**Fix pattern**:
```php
// Before (violates amount > 0)
'transaction' => ['amount' => -1000, ...]

// After (valid)
'transaction' => ['amount' => 1000, ...]
```

#### 3.2 Order Total Constraints  
**Target files**:
- `tests/Services/OrderServiceTest.php`
- `tests/Integration/Api/OrdersApiTest.php`

**Fix pattern**:
```php
// Before (violates total >= 0)
'order' => ['total' => -500, 'order_number' => null, ...]

// After (valid)
'order' => ['total' => 500, 'order_number' => 'ORD-' . time(), ...]
```

#### 3.3 Product Category Link Uniques
**Target files**:
- `tests/Services/ProductServiceTest.php`
- Any tests creating category links

**Fix pattern**:
```php
// Before (may create duplicates)
$this->seedCategoryLink($productId, 1);
$this->seedCategoryLink($productId, 1); // Duplicate!

// After (check existence first)
if (!$this->categoryLinkExists($productId, 1)) {
    $this->seedCategoryLink($productId, 1);
}
```

### Phase 4: Fix Foreign Key Dependencies

#### 4.1 Customer-Related Tests
**Target files**: Any test creating customers
```php
// Before (missing FK)
$customer = ['customer_group_id' => 999, 'organization_id' => 888];

// After (create dependencies first)
$groupId = $this->createCustomerGroup(['code' => 'TEST-GROUP']);
$orgId = $this->createOrganization(['code' => 'TEST-ORG']);
$customer = ['customer_group_id' => $groupId, 'organization_id' => $orgId];
```

#### 4.2 Order-Related Tests
**Target files**: Order tests with customer_group_id
```php
// Before (missing FK)
$order = ['customer_group_id' => 999];

// After (create dependency first)
$groupId = $this->createCustomerGroup();
$order = ['customer_group_id' => $groupId];
```

#### 4.3 Purchase-Related Tests
**Target files**: Purchase invoice, subcontracting tests
```php
// Before (missing FK)
$invoice = ['supplier_id' => 999];

// After (create dependency first)
$supplierId = $this->createSupplier();
$invoice = ['supplier_id' => $supplierId];
```

### Phase 5: Fix Soft Delete Assertions

#### 5.1 Update Test Assertions
**Target files**: Tests for soft delete functionality
```php
// Before (won't find soft-deleted records)
$this->seeInDatabase('order_items', ['id' => $deletedId]);

// After (includes soft-deleted)
$this->db->table('order_items')->withDeleted()->where('id', $deletedId)->get()->getRowArray();
// OR use helper:
$this->seeInDatabaseIncludingDeleted('order_items', ['id' => $deletedId]);
```

#### 5.2 Update Service Tests
**Target files**: Service tests that verify soft delete behavior
```php
// Before
$this->assertNull($this->db->table('products')->where('id', $id)->get()->getRowArray());

// After  
$this->assertNotNull($this->db->table('products')->withDeleted()->where('id', $id)->get()->getRowArray());
```

## Implementation Strategy

### Step-by-Step Execution
1. **Run tests and capture baseline failures**
2. **Create helper infrastructure** (Phase 2)
3. **Fix one constraint category at a time**:
   - Fix cash transaction amounts → run tests → verify progress
   - Fix order totals → run tests → verify progress  
   - Fix order numbers → run tests → verify progress
   - Fix category links → run tests → verify progress
4. **Fix foreign key dependencies** by module:
   - Customer tests → run tests → verify
   - Order tests → run tests → verify
   - Purchase tests → run tests → verify
5. **Fix soft delete assertions** → run tests → verify
6. **Final full test suite run** → ensure 100% pass

### Validation Criteria
- All unit tests pass: `vendor/bin/phpunit`
- All integration tests pass: `vendor/bin/phpunit -c phpunit.integration.xml`
- Test coverage >= 70% maintained
- No constraint violations in test output
- All soft delete assertions work correctly

## Risk Mitigation

### Potential Issues
1. **Test data conflicts**: Use unique identifiers in test data
2. **Transaction isolation**: Ensure proper transaction handling in tests
3. **Migration dependencies**: Verify migrations run in correct order
4. **Performance impact**: Batch similar fixes to minimize test run time

### Rollback Strategy
- Keep original test files as backups
- Use git branches for each fix category
- Document each change with commit messages referencing constraint types

## Success Metrics
- [ ] All 500+ tests pass without constraint violations
- [ ] Test execution time remains under 10 minutes
- [ ] No test data leakage between test cases
- [ ] All new master entities properly created in tests
- [ ] Soft delete functionality correctly tested

## Next Steps
1. Execute Phase 1: Environment setup and test discovery
2. Create failure analysis document
3. Implement Phase 2: Helper infrastructure
4. Proceed with systematic fixes by category
5. Final validation and documentation

---

**Note**: This plan should be executed in the Code mode where actual test execution and file modifications can be performed.