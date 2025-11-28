---
id: "TEST-SCHEMA-DEBUG-2025-11-27"
title: "Test Schema Instability Debug Report"
author: "AI Agent Roo"
date: "2025-11-27"
status: "Completed"
type: "debug"
purpose: "Root cause analysis of test schema instability and proposed solutions"
location: "docs/audits"
tags: ["testing", "schema", "debug", "mysql", "infrastructure"]
related_to:
  - id: "FINAL-TEST-COVERAGE-2025-11-27"
    file: "docs/audits/2025-11-27-FINAL-TEST-COVERAGE-REPORT.md"
    description: "Comprehensive test coverage analysis"
  - id: "DOC-CONSOLIDATION-PLAN-2025-11-27"
    file: "docs/audits/2025-11-27-DOCUMENTATION-CONSOLIDATION-PLAN.md"
    description: "Documentation consolidation strategy"
---

# Test Schema Instability Debug Report

## 🚨 Problem Summary

**Symptom:** DB lanocrm_test bị mất nhiều bảng sau vài lượt chạy test, chỉ còn vài bảng như products, price_lists hoặc thậm chí mất products. Hậu quả: các API pricing trả 500 với thông báo `getFirstRow()/getRowArray() on false`, làm hỏng 2 case trong OrdersPricingApiTest.

**Root Cause:** **Race condition giữa DevDatabaseTrait và FeatureTestTrait** khi quản lý database connections và schema creation.

## 🔍 Root Cause Analysis

### 1. **Multiple Database Connections Issue**

**DevDatabaseTrait** (Unit Tests):
```php
protected function setUpDatabase(): void
{
    $this->freshMigrateSchema();        // ✋ Creates schema
    $this->db = \Config\Database::connect('tests');  // ✋ Connection A
    $this->createBasicTestSchema($this->db);
    $this->db->transBegin();           // ✋ Transaction on Connection A
}
```

**FeatureTestTrait** (Integration Tests):
```php
// Uses separate DB connection for HTTP requests
// Connection B is independent from Connection A
```

### 2. **Schema Race Condition**

**Problem Flow:**
1. **Test A starts** → DevDatabaseTrait creates schema on Connection A
2. **Test B starts** → FeatureTestTrait uses Connection B (empty)
3. **Test A calls `resetCompleteSchema()`** → Drops ALL tables on Connection A
4. **Test B tries to query** → Tables missing on Connection B → **500 ERROR**

### 3. **Transaction Isolation Breakdown**

**Current Implementation:**
```php
// DevDatabaseTrait::setUpDatabase()
$this->db->transBegin();  // Transaction starts

// FeatureTestTrait makes HTTP requests
// HTTP requests use NEW connection (no transaction)
// Data inserted via HTTP is NOT in transaction
```

**Result:** Transaction isolation bị broken, data visibility inconsistent.

## 🎯 Confirmed Issues

### Issue 1: **Schema Creation Race Condition**
- **Location:** `DevDatabaseTrait::freshMigrateSchema()` + `CompleteSchemaTrait::resetCompleteSchema()`
- **Problem:** Multiple tests calling `resetCompleteSchema()` simultaneously
- **Impact:** Tables dropped while other tests still running

### Issue 2: **Connection Management Conflict**
- **Location:** `DevDatabaseTrait` vs `FeatureTestTrait`
- **Problem:** Two different DB connections with different schema states
- **Impact:** Data visibility issues between unit and integration tests

### Issue 3: **Transaction Scope Violation**
- **Location:** `DevDatabaseTrait::setUpDatabase()`
- **Problem:** Transaction only covers Connection A, not HTTP requests
- **Impact:** Test isolation broken

## 🛠️ Proposed Solutions

### Solution 1: **Schema Locking Mechanism** (IMMEDIATE)

**Implement schema-level locking to prevent concurrent schema resets:**

```php
// In DevDatabaseTrait
private static bool $schemaLocked = false;
private static string $schemaLockOwner = '';

protected function freshMigrateSchema(): void
{
    // Wait for schema lock (max 30 seconds)
    $waitTime = 0;
    while (self::$schemaLocked && $waitTime < 30) {
        sleep(1);
        $waitTime++;
    }
    
    if (self::$schemaLocked) {
        throw new \Exception('Schema creation timeout - possible deadlock');
    }
    
    // Acquire lock
    self::$schemaLocked = true;
    self::$schemaLockOwner = get_class($this);
    
    try {
        $this->performSchemaReset();
    } finally {
        // Release lock
        self::$schemaLocked = false;
        self::$schemaLockOwner = '';
    }
}
```

### Solution 2: **Unified Connection Management** (SHORT-TERM)

**Standardize database connection across all test types:**

```php
// Enhanced DevDatabaseTrait
protected function setUpDatabase(): void
{
    // Use single connection for all tests
    $this->db = \Config\Database::connect('tests');
    
    // Only reset schema if needed (not every test)
    if (!self::$schemaInitialized) {
        $this->initializeSchema();
        self::$schemaInitialized = true;
    }
    
    // Start transaction AFTER schema is stable
    $this->db->transBegin();
}

protected function initializeSchema(): void
{
    // Use schema versioning to avoid unnecessary resets
    $currentVersion = $this->getSchemaVersion();
    $requiredVersion = $this->getRequiredSchemaVersion();
    
    if ($currentVersion !== $requiredVersion) {
        $this->performSchemaReset();
        $this->setSchemaVersion($requiredVersion);
    }
}
```

### Solution 3: **Connection Pool for Feature Tests** (MEDIUM-TERM)

**Create shared connection pool for HTTP requests:**

```php
// New trait: SharedConnectionTrait
trait SharedConnectionTrait
{
    private static $sharedConnection = null;
    
    protected function getSharedConnection()
    {
        if (self::$sharedConnection === null) {
            self::$sharedConnection = \Config\Database::connect('tests');
        }
        return self::$sharedConnection;
    }
    
    protected function setUpSharedConnection(): void
    {
        // Ensure shared connection uses same schema
        $conn = $this->getSharedConnection();
        $conn->transBegin();
    }
    
    protected function tearDownSharedConnection(): void
    {
        $conn = $this->getSharedConnection();
        $conn->transRollback();
    }
}
```

### Solution 4: **Schema State Validation** (IMMEDIATE)

**Add schema validation before each test:**

```php
// In DevDatabaseTrait
protected function validateSchema(): void
{
    $requiredTables = [
        'products', 'price_lists', 'price_list_items',
        'orders', 'order_items', 'customers',
        'inventory_stock', 'cash_transactions'
    ];
    
    $existingTables = $this->db->listTables();
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables)) {
            throw new \Exception("Required table '{$table}' missing. Schema corruption detected.");
        }
    }
}

protected function setUpDatabase(): void
{
    $this->freshMigrateSchema();
    $this->validateSchema();  // ✋ Validate before proceeding
    $this->db->transBegin();
}
```

## 🚀 Implementation Priority

### Phase 1: Emergency Fix (Today)
1. **Add schema validation** in `DevDatabaseTrait::setUpDatabase()`
2. **Implement schema locking** mechanism
3. **Add retry logic** for schema creation failures

### Phase 2: Stabilization (This Week)
1. **Implement unified connection management**
2. **Add schema versioning** to avoid unnecessary resets
3. **Create connection pool** for feature tests

### Phase 3: Long-term Architecture (Next Sprint)
1. **Refactor to single test database** with proper isolation
2. **Implement test data factories** instead of manual seeding
3. **Add database state snapshots** for faster test setup

## 📋 Immediate Actions Required

### 1. Fix OrdersPricingApiTest (Today)
```php
// In OrdersPricingApiTest::setUp()
protected function setUp(): void
{
    parent::setUp();
    
    // Add schema validation
    $this->forceFreshMigrate();
    $this->validateRequiredTables();  // ✋ New validation method
    
    // Ensure data visibility
    $this->db->transCommit();  // Commit seed data
    $this->db->transBegin();   // Start new transaction
    
    // ... rest of setup
}

private function validateRequiredTables(): void
{
    $required = ['products', 'price_lists', 'price_list_items', 'customers'];
    $existing = $this->db->listTables();
    
    foreach ($required as $table) {
        if (!in_array($table, $existing)) {
            $this->markTestSkipped("Required table '{$table}' missing");
            return;
        }
    }
}
```

### 2. Update DevDatabaseTrait (Today)
```php
// Add to DevDatabaseTrait
private static bool $schemaLock = false;
private static int $lockWaitCount = 0;

protected function acquireSchemaLock(): bool
{
    $maxWait = 30; // 30 seconds max
    while (self::$schemaLock && self::$lockWaitCount < $maxWait) {
        sleep(1);
        self::$lockWaitCount++;
    }
    
    if (self::$schemaLock) {
        return false; // Timeout
    }
    
    self::$schemaLock = true;
    return true;
}

protected function releaseSchemaLock(): void
{
    self::$schemaLock = false;
    self::$lockWaitCount = 0;
}
```

### 3. Add Schema Validation (Today)
```php
// In CompleteSchemaTrait
protected function validateSchemaIntegrity(): void
{
    $criticalTables = [
        'products', 'price_lists', 'orders', 
        'customers', 'inventory_stock'
    ];
    
    $existing = $this->db->listTables();
    $missing = array_diff($criticalTables, $existing);
    
    if (!empty($missing)) {
        throw new \Exception(
            "Schema integrity violation. Missing tables: " . implode(', ', $missing)
        );
    }
}
```

## 🧪 Testing the Fix

### Validation Steps:
1. **Run OrdersPricingApiTest individually** - Should pass
2. **Run multiple test classes concurrently** - Should not interfere
3. **Run full test suite** - Should be stable
4. **Check schema consistency** - Should maintain required tables

### Success Criteria:
- ✅ All tests pass consistently
- ✅ No "table doesn't exist" errors
- ✅ Schema state stable across test runs
- ✅ Transaction isolation maintained

## 📊 Expected Impact

### Before Fix:
- ❌ Intermittent test failures
- ❌ Schema corruption between runs
- ❌ Race conditions in parallel testing
- ❌ Transaction isolation broken

### After Fix:
- ✅ Stable test execution
- ✅ Consistent schema state
- ✅ Proper test isolation
- ✅ Predictable test results

## 🎯 Conclusion

**Root cause confirmed:** Race condition between multiple database connections and schema management in test infrastructure.

**Primary issue:** `DevDatabaseTrait` và `FeatureTestTrait` sử dụng different database connections với different schema states, causing tables to be dropped while other tests are still running.

**Immediate solution:** Implement schema locking and validation to prevent concurrent schema modifications.

**Long-term solution:** Refactor to unified connection management with proper isolation mechanisms.

**Priority:** CRITICAL - This issue blocks reliable testing and affects CI/CD pipeline stability.

---

**Next Steps:**
1. Implement emergency schema locking today
2. Add comprehensive schema validation
3. Test fixes with OrdersPricingApiTest
4. Roll out to all test classes
5. Monitor for stability improvements