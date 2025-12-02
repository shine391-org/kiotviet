# Test Fix Execution Plan - Complete Roadmap

## 🎯 Mission Statement
Fix all test failures caused by database schema changes (soft delete, constraints, foreign keys) with comprehensive logging and systematic approach.

## 📋 Executive Summary

### Problem Analysis
- **Root Cause**: Recent database schema changes introduced constraints that break existing tests
- **Impact**: ~500+ tests failing due to constraint violations
- **Key Issues**: 
  - Soft delete implementation affecting queries
  - New CHECK constraints (amount > 0, total >= 0)
  - NOT NULL constraints (order_number)
  - Unique constraints (product_category_links)
  - New foreign key dependencies (master tables)

### Solution Strategy
1. **Systematic Discovery**: Run tests with detailed logging to identify all failures
2. **Categorized Fixes**: Fix by constraint type to minimize complexity
3. **Helper Infrastructure**: Create reusable test helpers for master entities
4. **Validation**: Verify each fix category before proceeding
5. **Documentation**: Comprehensive logging for audit trail

## 🗂️ Documentation Created

| Document | Purpose | Status |
|----------|---------|--------|
| `docs/test-fix-plan.md` | High-level strategy and phases | ✅ Complete |
| `docs/test-fix-implementation-guide.md` | Code patterns and specific fixes | ✅ Complete |
| `docs/test-fix-logging-strategy.md` | Logging infrastructure and commands | ✅ Complete |
| `docs/test-fix-execution-plan.md` | Complete execution roadmap | ✅ In Progress |

## 🚀 Execution Phases

### Phase 0: Environment Setup (15 minutes)
```bash
# 1. Create logging structure
./scripts/setup-test-logging.sh

# 2. Verify test environment
docker-compose up -d db api
docker exec meomeo2-api-1 vendor/bin/phpunit --version

# 3. Create helper scripts
mkdir -p scripts/
# (scripts will be created during execution)
```

### Phase 1: Discovery & Analysis (30 minutes)
```bash
# 1. Run initial test suite with full logging
./scripts/run-initial-test-logging.sh

# 2. Analyze constraint violations
cat logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run/constraint-violations.log

# 3. Review failure summary
cat logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run/test-failures-summary.json | jq .

# 4. Create fix priority matrix
./scripts/create-fix-matrix.sh
```

**Expected Output**:
- Complete list of failing tests
- Categorized constraint violations
- Priority order for fixes
- Estimated effort per category

### Phase 2: Helper Infrastructure (45 minutes)
```bash
# 1. Create MasterEntityTestHelper
cp templates/MasterEntityTestHelper.php tests/_support/Database/

# 2. Create SoftDeleteTestHelper  
cp templates/SoftDeleteTestHelper.php tests/_support/Database/

# 3. Update existing test traits
# (automated script to add use statements)

# 4. Test helper functionality
docker exec meomeo2-api-1 vendor/bin/phpunit tests/_support/
```

**Deliverables**:
- `tests/_support/Database/MasterEntityTestHelper.php`
- `tests/_support/Database/SoftDeleteTestHelper.php`
- Updated test traits with helper imports
- Validation of helper functionality

### Phase 3: Constraint Fixes (2-3 hours)

#### 3.1 Cash Transaction Amount Fixes (30 minutes)
```bash
# Start logging for this category
./scripts/run-category-fix-logging.sh "02-cash-transaction-fixes" "Fix cash transaction amount constraints"

# Apply fixes to target files:
# - tests/Services/CashTransactionServiceTest.php
# - tests/Integration/Api/CashTransactionsApiTest.php
# - Any other files with cash transaction tests

# Validate fixes
./scripts/validate-category-fix.sh "02-cash-transaction-fixes"
```

**Fix Pattern**:
```php
// Ensure all amounts are positive
'amount' => max(1, abs($amount)), // Always > 0
```

#### 3.2 Order Total Fixes (30 minutes)
```bash
./scripts/run-category-fix-logging.sh "03-order-total-fixes" "Fix order total constraints"

# Target files:
# - tests/Services/OrderServiceTest.php
# - tests/Integration/Api/OrdersApiTest.php
# - Any order-related tests

./scripts/validate-category-fix.sh "03-order-total-fixes"
```

**Fix Pattern**:
```php
// Ensure totals are non-negative
'total' => max(0, $total), // Always >= 0
'subtotal' => max(0, $subtotal),
'debt_amount' => max(0, $total - $paid_amount),
```

#### 3.3 Order Number Fixes (20 minutes)
```bash
./scripts/run-category-fix-logging.sh "04-order-number-fixes" "Fix order number NOT NULL constraints"

./scripts/validate-category-fix.sh "04-order-number-fixes"
```

**Fix Pattern**:
```php
// Generate valid order numbers
'order_number' => $orderNumber ?: 'ORD-' . time() . '-' . random_int(100, 999),
```

#### 3.4 Category Link Uniqueness Fixes (20 minutes)
```bash
./scripts/run-category-fix-logging.sh "05-category-link-fixes" "Fix product category link unique constraints"

./scripts/validate-category-fix.sh "05-category-link-fixes"
```

**Fix Pattern**:
```php
// Check existence before inserting
if (!$this->categoryLinkExists($productId, $categoryId)) {
    $this->seedCategoryLink($productId, $categoryId);
}
```

#### 3.5 Foreign Key Dependency Fixes (60 minutes)
```bash
./scripts/run-category-fix-logging.sh "06-foreign-key-fixes" "Fix foreign key dependency constraints"

# This is the most complex phase - affects many test files

./scripts/validate-category-fix.sh "06-foreign-key-fixes"
```

**Fix Pattern**:
```php
// Create dependencies before child records
$groupId = $data['customer_group_id'] ?? $this->createCustomerGroup();
$orgId = $data['organization_id'] ?? $this->createOrganization();
$supplierId = $data['supplier_id'] ?? $this->createSupplier();
```

### Phase 4: Soft Delete Assertion Fixes (30 minutes)
```bash
./scripts/run-category-fix-logging.sh "07-soft-delete-fixes" "Fix soft delete assertions"

./scripts/validate-category-fix.sh "07-soft-delete-fixes"
```

**Fix Pattern**:
```php
// Use withDeleted() for soft delete assertions
$this->db->table('products')->withDeleted()->where('id', $deletedId)->get()->getRowArray();
// OR use helper:
$this->seeInDatabaseIncludingDeleted('products', ['id' => $deletedId]);
```

### Phase 5: Final Validation (30 minutes)
```bash
# 1. Run complete test suite
./scripts/run-final-validation.sh

# 2. Generate coverage report
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-html logs/test-fixes/$(date +%Y-%m-%d)/08-final-validation/coverage/

# 3. Create success summary
./scripts/generate-final-report.sh

# 4. Archive logs
./scripts/archive-logs.sh
```

## 📊 Success Metrics

### Quantitative Targets
- [ ] **0 test failures** - All tests pass
- [ ] **≥70% code coverage** - Maintain coverage standards
- [ ] **<10 minute execution time** - Performance maintained
- [ ] **100% constraint compliance** - No violations in logs

### Qualitative Targets
- [ ] **Clean test output** - No warnings or notices
- [ ] **Proper soft delete handling** - All soft delete tests work correctly
- [ ] **Master entity creation** - All FK dependencies satisfied
- [ ] **Comprehensive logging** - Full audit trail available

## 🔧 Tools & Scripts Required

### Scripts to Create
1. `scripts/setup-test-logging.sh` - Initialize logging structure
2. `scripts/run-initial-test-logging.sh` - Run initial test analysis
3. `scripts/run-category-fix-logging.sh` - Start category-specific fixes
4. `scripts/validate-category-fix.sh` - Validate fixes after implementation
5. `scripts/analyze-test-failures.php` - PHP script for failure analysis
6. `scripts/create-fix-matrix.sh` - Create priority matrix
7. `scripts/run-final-validation.sh` - Final validation suite
8. `scripts/generate-final-report.sh` - Generate completion report
9. `scripts/archive-logs.sh` - Archive logs for future reference

### Helper Files to Create
1. `tests/_support/Database/MasterEntityTestHelper.php` - Master entity creation
2. `tests/_support/Database/SoftDeleteTestHelper.php` - Soft delete assertions
3. `templates/` directory with code templates

## ⚠️ Risk Mitigation

### Technical Risks
1. **Test Data Conflicts**: Use unique identifiers and proper cleanup
2. **Migration Dependencies**: Ensure migrations run in correct order
3. **Transaction Isolation**: Proper transaction handling in tests
4. **Performance Impact**: Batch similar fixes to minimize runtime

### Mitigation Strategies
- **Git Branching**: Use feature branches for each fix category
- **Backups**: Keep original test files as backups
- **Incremental Validation**: Test after each category fix
- **Rollback Plan**: Document rollback procedures for each phase

## 📅 Timeline Estimate

| Phase | Duration | Dependencies |
|-------|----------|--------------|
| Environment Setup | 15 min | Docker environment |
| Discovery & Analysis | 30 min | Test suite access |
| Helper Infrastructure | 45 min | Analysis complete |
| Cash Transaction Fixes | 30 min | Helpers ready |
| Order Total Fixes | 30 min | Previous phase complete |
| Order Number Fixes | 20 min | Previous phase complete |
| Category Link Fixes | 20 min | Previous phase complete |
| Foreign Key Fixes | 60 min | Previous phase complete |
| Soft Delete Fixes | 30 min | Previous phase complete |
| Final Validation | 30 min | All fixes complete |
| **Total Estimated Time** | **4-5 hours** | - |

## 🎯 Next Steps

### Immediate Actions (Switch to Code Mode)
1. **Create logging scripts** - Set up infrastructure
2. **Run initial test analysis** - Get baseline failure data
3. **Create helper traits** - Build reusable infrastructure
4. **Begin systematic fixes** - Start with cash transaction constraints

### Prerequisites for Execution
- [ ] Docker environment running (`db` and `api` containers)
- [ ] Access to test suite (`vendor/bin/phpunit`)
- [ ] Git workspace for tracking changes
- [ ] Write access to `tests/` directory
- [ ] Write access to `logs/` directory

### Success Criteria
- All tests pass without constraint violations
- Comprehensive logging maintained throughout process
- No regression in test coverage or performance
- Clear documentation of all changes made

---

## 📞 Handoff Instructions

This execution plan provides a complete roadmap for fixing the test failures. The next step is to **switch to Code mode** to begin implementation:

1. Use the logging strategy to capture initial test failures
2. Follow the phased approach systematically
3. Validate each phase before proceeding
4. Maintain comprehensive logs throughout

**Ready to execute! 🚀**