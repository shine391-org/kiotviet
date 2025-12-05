# Test Fix Implementation Guide

## Quick Reference for Common Test Fixes

This guide provides ready-to-use code patterns for fixing the most common test failures after the database schema changes.

## 1. Master Entity Creation Helper

### Create: `tests/_support/Database/MasterEntityTestHelper.php`

```php
<?php

namespace Tests\Support\Database;

/**
 * Helper trait for creating master entities in tests
 * Ensures proper FK dependency ordering
 */
trait MasterEntityTestHelper
{
    /**
     * Create a customer group for testing
     */
    protected function createCustomerGroup(array $data = []): int
    {
        $payload = array_merge([
            'code' => 'CG-' . random_int(1000, 9999),
            'name_vi' => 'Test Customer Group',
            'name_en' => 'Test Customer Group',
            'is_default' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('customer_groups')->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * Create an organization for testing
     */
    protected function createOrganization(array $data = []): int
    {
        $payload = array_merge([
            'code' => 'ORG-' . random_int(1000, 9999),
            'name_vi' => 'Test Organization',
            'name_en' => 'Test Organization',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('organizations')->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * Create a supplier for testing
     */
    protected function createSupplier(array $data = []): int
    {
        $payload = array_merge([
            'code' => 'SUP-' . random_int(1000, 9999),
            'name_vi' => 'Test Supplier',
            'name_en' => 'Test Supplier',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('suppliers')->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * Create a device for testing
     */
    protected function createDevice(array $data = []): int
    {
        $payload = array_merge([
            'code' => 'DEV-' . random_int(1000, 9999),
            'name' => 'Test Device',
            'type' => 'pos',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('devices')->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * Create a complete customer with all dependencies
     */
    protected function createCustomerWithDependencies(array $data = []): int
    {
        $groupId = $this->createCustomerGroup();
        $orgId = $this->createOrganization();
        
        $payload = array_merge([
            'customer_group_id' => $groupId,
            'organization_id' => $orgId,
            'name' => 'Test Customer',
            'phone' => '09' . random_int(100000000, 999999999),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('customers')->insert($payload);
        return (int) $this->db->insertID();
    }
}
```

## 2. Soft Delete Assertion Helper

### Create: `tests/_support/Database/SoftDeleteTestHelper.php`

```php
<?php

namespace Tests\Support\Database;

/**
 * Helper trait for soft delete assertions
 */
trait SoftDeleteTestHelper
{
    /**
     * Assert record exists including soft deleted
     */
    protected function seeInDatabaseIncludingDeleted(string $table, array $criteria): void
    {
        $builder = $this->db->table($table);
        
        // Use withDeleted() if the model supports it
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

    /**
     * Assert record is soft deleted
     */
    protected function seeSoftDeletedInDatabase(string $table, array $criteria): void
    {
        $builder = $this->db->table($table);
        
        // Use withDeleted() to find soft deleted records
        if (method_exists($builder, 'withDeleted')) {
            $builder->withDeleted();
        }
        
        foreach ($criteria as $field => $value) {
            $builder->where($field, $value);
        }
        
        $builder->where('deleted_at IS NOT NULL');
        
        $result = $builder->get()->getResultArray();
        
        if (empty($result)) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                "Failed asserting that a soft deleted row exists in table {$table} with criteria: " . json_encode($criteria)
            );
        }
    }

    /**
     * Assert record is NOT soft deleted
     */
    protected function dontSeeSoftDeletedInDatabase(string $table, array $criteria): void
    {
        $builder = $this->db->table($table);
        
        foreach ($criteria as $field => $value) {
            $builder->where($field, $value);
        }
        
        $builder->where('deleted_at IS NOT NULL');
        
        $result = $builder->get()->getResultArray();
        
        if (!empty($result)) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                "Found unexpected soft deleted row in table {$table} with criteria: " . json_encode($criteria)
            );
        }
    }
}
```

## 3. Specific Fix Patterns

### 3.1 Cash Transaction Amount Fixes

**Problem**: `amount <= 0` violates `CHECK (amount > 0)`

**Fix Pattern**:
```php
// Before
private function seedCashTransaction(array $data): int
{
    $payload = array_merge([
        'amount' => -1000, // INVALID
        'type' => 'PAYMENT',
        // ...
    ], $data);
}

// After  
private function seedCashTransaction(array $data): int
{
    $payload = array_merge([
        'amount' => 100000, // VALID - always positive
        'type' => 'RECEIPT',
        // ...
    ], $data);
    
    // Ensure amount is always positive
    if ($payload['amount'] <= 0) {
        $payload['amount'] = 100000;
    }
}
```

### 3.2 Order Total and Number Fixes

**Problem**: `total < 0` or `order_number = NULL`

**Fix Pattern**:
```php
// Before
private function createOrder(string $code, string $status, int $branchId, float $total, float $paid): int
{
    $this->db->table('orders')->insert([
        'order_number' => null, // INVALID
        'total' => $total, // Can be negative
        // ...
    ]);
}

// After
private function createOrder(string $code, string $status, int $branchId, float $total, float $paid): int
{
    $orderNumber = $code ?: 'ORD-' . time() . '-' . random_int(100, 999);
    $validTotal = max(0, abs($total)); // Ensure non-negative
    
    $this->db->table('orders')->insert([
        'order_number' => $orderNumber, // VALID - NOT NULL
        'total' => $validTotal, // VALID - >= 0
        'subtotal' => $validTotal,
        'paid_amount' => $paid,
        'debt_amount' => max(0, $validTotal - $paid),
        // ...
    ]);
}
```

### 3.3 Product Category Link Uniqueness

**Problem**: Duplicate `(product_id, category_id)` pairs

**Fix Pattern**:
```php
// Before
private function seedCategoryLink(int $productId, int $categoryId): void
{
    $this->db->table('product_category_links')->insert([
        'product_id' => $productId,
        'category_id' => $categoryId,
        // ...
    ]);
}

// After
private function seedCategoryLink(int $productId, int $categoryId): void
{
    // Check if link already exists
    $exists = $this->db->table('product_category_links')
        ->where('product_id', $productId)
        ->where('category_id', $categoryId)
        ->where('deleted_at', null)
        ->get()
        ->getRowArray();
    
    if (!$exists) {
        $this->db->table('product_category_links')->insert([
            'product_id' => $productId,
            'category_id' => $categoryId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

### 3.4 Foreign Key Dependency Fixes

**Problem**: Missing master entities for FK constraints

**Fix Pattern for Customers**:
```php
// Before
private function seedCustomer(array $data = []): int
{
    $payload = array_merge([
        'customer_group_id' => 999, // INVALID - doesn't exist
        'organization_id' => 888,   // INVALID - doesn't exist
        // ...
    ], $data);
}

// After
private function seedCustomer(array $data = []): int
{
    // Create dependencies first
    $groupId = $data['customer_group_id'] ?? $this->createCustomerGroup();
    $orgId = $data['organization_id'] ?? $this->createOrganization();
    
    $payload = array_merge([
        'customer_group_id' => $groupId, // VALID - exists
        'organization_id' => $orgId,     // VALID - exists
        'name' => 'Test Customer',
        'phone' => '09' . random_int(100000000, 999999999),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ], $data);
    
    $this->db->table('customers')->insert($payload);
    return (int) $this->db->insertID();
}
```

**Fix Pattern for Orders**:
```php
// Before
private function seedOrder(array $data): int
{
    $payload = array_merge([
        'customer_group_id' => null, // May violate FK if set to invalid ID
        // ...
    ], $data);
}

// After
private function seedOrder(array $data): int
{
    // Handle customer_group_id properly
    if (isset($data['customer_group_id']) && $data['customer_group_id']) {
        // Verify it exists or create new one
        $exists = $this->db->table('customer_groups')
            ->where('id', $data['customer_group_id'])
            ->get()
            ->getRowArray();
        if (!$exists) {
            $data['customer_group_id'] = $this->createCustomerGroup();
        }
    }
    
    $payload = array_merge([
        'order_number' => 'ORD-' . time() . '-' . random_int(100, 999),
        'total' => 0, // Always non-negative
        'status' => 'draft',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ], $data);
    
    $this->db->table('orders')->insert($payload);
    return (int) $this->db->insertID();
}
```

## 4. Test Class Updates

### Example: Updated CashTransactionServiceTest

```php
<?php

namespace Tests\Services;

use App\Services\CashTransactions\CashTransactionService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CashTransactionSchemaTrait;
use Tests\Support\Database\MasterEntityTestHelper;

class CashTransactionServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CashTransactionSchemaTrait;
    use MasterEntityTestHelper; // Add this

    private CashTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCashTransactionSchema();
        $this->service = new CashTransactionService(null, null, null, $this->db);
    }

    // ... test methods ...

    private function seedCashTransaction(array $data): int
    {
        $branchId = $data['branch_id'] ?? $this->createTestBranch();
        $userId = $data['created_by'] ?? $this->createTestUser();
        
        $payload = array_merge([
            'type' => 'RECEIPT',
            'amount' => 100000, // Always positive
            'category' => 'sales',
            'description' => 'Test transaction',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => date('Y-m-d'),
            'reference_type' => 'manual',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        // Ensure amount is always positive
        if ($payload['amount'] <= 0) {
            $payload['amount'] = 100000;
        }

        $this->db->table('cash_transactions')->insert($payload);
        return (int) $this->db->insertID();
    }
}
```

## 5. Running Tests with Specific Categories

### Command Reference

```bash
# Run all tests
docker exec meomeo2-api-1 vendor/bin/phpunit --colors=always

# Run specific test file
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/CashTransactionServiceTest.php

# Run tests with coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Run integration tests
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Run tests matching pattern
docker exec meomeo2-api-1 vendor/bin/phpunit --filter "CashTransaction"
```

## 6. Validation Checklist

After applying fixes, verify:

- [ ] No constraint violation errors in test output
- [ ] All soft delete assertions use `withDeleted()` where appropriate
- [ ] All master entities are created before child records
- [ ] All monetary values are non-negative where required
- [ ] All required fields (order_number) are populated
- [ ] No duplicate unique key violations
- [ ] Test coverage remains >= 70%

## 7. Common Error Messages and Solutions

| Error Message | Cause | Solution |
|---|---|---|
| `CHECK constraint violated: chk_cash_amount` | `amount <= 0` in cash_transactions | Ensure all amounts are positive |
| `CHECK constraint violated: chk_orders_total` | `total < 0` in orders | Use `max(0, $total)` or absolute value |
| `Column 'order_number' cannot be null` | Missing order_number | Generate unique order numbers |
| `Duplicate entry for key 'uq_product_category_links'` | Duplicate category links | Check existence before inserting |
| `Foreign key constraint fails` | Missing master entity | Create dependencies first |

This implementation guide provides ready-to-use patterns for fixing the most common test failures after the database schema changes.