# Database Schema Test Fixes - Complete Guide

## 🎯 Overview

This project addresses test failures caused by recent database schema changes in the LANO CRM system. The changes include soft delete implementation, new constraints, and foreign key requirements that break existing tests.

## 📁 Documentation Structure

```
docs/
├── test-fix-plan.md                    # High-level strategy & phases
├── test-fix-implementation-guide.md    # Code patterns & specific fixes
├── test-fix-logging-strategy.md        # Logging infrastructure & commands
├── test-fix-execution-plan.md          # Complete execution roadmap
└── README-TEST-FIX.md                  # This file - summary & quick start
```

## 🚨 Problem Summary

### Database Changes Causing Test Failures

1. **Soft Delete Implementation**
   - Tables: `order_items`, `delivery_note_items`, `returns`, `return_items`, `stock_reconciliation_items`, `subscription_cycles`
   - Impact: Models now filter `deleted_at IS NULL` by default
   - Fix: Use `withDeleted()` in assertions

2. **New Check Constraints**
   - `orders.total`: CHECK (total >= 0)
   - `cash_transactions.amount`: CHECK (amount > 0)
   - Impact: Tests with negative values fail
   - Fix: Update test data to use valid values

3. **New NOT NULL Constraints**
   - `orders.order_number`: NOT NULL
   - Impact: Tests creating orders without order_number fail
   - Fix: Generate valid order numbers

4. **Unique Constraints**
   - `product_category_links`: UNIQUE (product_id, category_id)
   - Impact: Duplicate category links fail
   - Fix: Check existence before inserting

5. **New Foreign Key Dependencies**
   - Master tables: `organizations`, `customer_groups`, `devices`, `suppliers`
   - Impact: Tests must create master entities before child records
   - Fix: Update seed methods to create dependencies first

## 🛠️ Quick Start Guide

### 1. Environment Setup
```bash
# Start Docker containers
cd backend-ci
docker-compose up -d db api

# Verify test environment
docker exec meomeo2-api-1 vendor/bin/phpunit --version
```

### 2. Initial Test Analysis
```bash
# Create logging structure
mkdir -p logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run

# Run tests with full logging
docker exec meomeo2-api-1 vendor/bin/phpunit --verbose --log-junit logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run/junit.xml > logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run/full-test-output.log 2>&1

# Extract constraint violations
grep -i "constraint\|violation\|check constraint\|foreign key" logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run/full-test-output.log > logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run/constraint-violations.log
```

### 3. Create Helper Infrastructure
```bash
# Create MasterEntityTestHelper
cat > tests/_support/Database/MasterEntityTestHelper.php << 'EOF'
<?php

namespace Tests\Support\Database;

/**
 * Helper trait for creating master entities in tests
 */
trait MasterEntityTestHelper
{
    protected function createCustomerGroup(array $data = []): int
    {
        $payload = array_merge([
            'code' => 'CG-' . random_int(1000, 9999),
            'name_vi' => 'Test Customer Group',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('customer_groups')->insert($payload);
        return (int) $this->db->insertID();
    }

    protected function createOrganization(array $data = []): int
    {
        $payload = array_merge([
            'code' => 'ORG-' . random_int(1000, 9999),
            'name_vi' => 'Test Organization',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('organizations')->insert($payload);
        return (int) $this->db->insertID();
    }

    protected function createSupplier(array $data = []): int
    {
        $payload = array_merge([
            'code' => 'SUP-' . random_int(1000, 9999),
            'name_vi' => 'Test Supplier',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('suppliers')->insert($payload);
        return (int) $this->db->insertID();
    }
}
EOF

# Create SoftDeleteTestHelper
cat > tests/_support/Database/SoftDeleteTestHelper.php << 'EOF'
<?php

namespace Tests\Support\Database;

/**
 * Helper trait for soft delete assertions
 */
trait SoftDeleteTestHelper
{
    protected function seeInDatabaseIncludingDeleted(string $table, array $criteria): void
    {
        $builder = $this->db->table($table);
        
        if (method_exists($builder, 'withDeleted')) {
            $builder->withDeleted();
        }
        
        foreach ($criteria as $field => $value) {
            $builder->where($field, $value);
        }
        
        $result = $builder->get()->getResultArray();
        
        if (empty($result)) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                "Failed asserting that a row exists (including deleted) in table {$table} with criteria: " . json_encode($criteria)
            );
        }
    }
}
EOF
```

### 4. Apply Common Fix Patterns

#### Cash Transaction Amount Fixes
```php
// In test files, ensure amounts are always positive
'amount' => max(1, abs($amount)), // Always > 0
```

#### Order Total Fixes
```php
// Ensure totals are non-negative
'total' => max(0, $total), // Always >= 0
'subtotal' => max(0, $subtotal),
```

#### Order Number Fixes
```php
// Generate valid order numbers
'order_number' => $orderNumber ?: 'ORD-' . time() . '-' . random_int(100, 999),
```

#### Foreign Key Dependency Fixes
```php
// Create dependencies before child records
$groupId = $data['customer_group_id'] ?? $this->createCustomerGroup();
$orgId = $data['organization_id'] ?? $this->createOrganization();
```

#### Soft Delete Assertion Fixes
```php
// Use withDeleted() for soft delete assertions
$this->db->table('products')->withDeleted()->where('id', $deletedId)->get()->getRowArray();
// OR use helper:
$this->seeInDatabaseIncludingDeleted('products', ['id' => $deletedId]);
```

## 📊 Common Error Messages & Solutions

| Error Message | Cause | Solution |
|---|---|---|
| `CHECK constraint violated: chk_cash_amount` | `amount <= 0` in cash_transactions | Ensure all amounts are positive |
| `CHECK constraint violated: chk_orders_total` | `total < 0` in orders | Use `max(0, $total)` or absolute value |
| `Column 'order_number' cannot be null` | Missing order_number | Generate unique order numbers |
| `Duplicate entry for key 'uq_product_category_links'` | Duplicate category links | Check existence before inserting |
| `Foreign key constraint fails` | Missing master entity | Create dependencies first |

## 🔄 Step-by-Step Execution

### Phase 1: Discovery (30 min)
1. Run initial test suite with logging
2. Extract and categorize all failures
3. Create fix priority matrix

### Phase 2: Infrastructure (45 min)
1. Create helper traits for master entities
2. Create soft delete assertion helpers
3. Update existing test classes

### Phase 3: Systematic Fixes (2-3 hours)
1. Fix cash transaction amount constraints
2. Fix order total constraints
3. Fix order number constraints
4. Fix category link uniqueness
5. Fix foreign key dependencies
6. Fix soft delete assertions

### Phase 4: Validation (30 min)
1. Run complete test suite
2. Generate coverage report
3. Create success documentation

## 📈 Success Metrics

- [ ] **0 test failures** - All tests pass
- [ ] **≥70% code coverage** - Maintain coverage standards
- [ ] **<10 minute execution time** - Performance maintained
- [ ] **100% constraint compliance** - No violations in logs

## 🛠️ Required Tools

- Docker environment with `db` and `api` containers
- PHP 8.4+ with PHPUnit
- Git for version control
- Write access to `tests/` and `logs/` directories

## 📞 Next Steps

1. **Switch to Code mode** to begin implementation
2. **Run initial test analysis** to get baseline failure data
3. **Follow the phased approach** systematically
4. **Validate each phase** before proceeding
5. **Maintain comprehensive logs** throughout

## 📚 Additional Resources

- **Detailed Strategy**: `docs/test-fix-plan.md`
- **Code Patterns**: `docs/test-fix-implementation-guide.md`
- **Logging Guide**: `docs/test-fix-logging-strategy.md`
- **Execution Roadmap**: `docs/test-fix-execution-plan.md`

---

**Ready to execute! 🚀**

This comprehensive guide provides everything needed to systematically fix all test failures caused by the database schema changes. The approach ensures no regressions while maintaining full audit trails through detailed logging.