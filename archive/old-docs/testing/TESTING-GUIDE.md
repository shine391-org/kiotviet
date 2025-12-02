---
title: "Testing Guide - LANO CRM Backend"
id: "TESTING-GUIDE-01"
version: "2.1"
status: "Active"
module: "Testing"
type: "Guideline"
tags: ["testing", "backend", "unit-tests", "integration-tests", "mysql-only", "ci-cd"]
purpose: "Provides a comprehensive guide to the backend testing strategy, process, environment setup, and requirements for LANO CRM."
location: "docs/testing"
related_to:
  - id: "TESTING-PATTERNS-01"
    description: "Refer to this for code examples and patterns to copy."
  - id: "TEST-CHECKLIST-01"
    description: "Refer to this for mandatory testing checklist."
updated: "2025-11-25"
changes: "MySQL-only with production migrations (tests group), DevDatabaseTrait runs migrate --all, optional truncate-only schema traits."
---

# Testing Guide

## 🚨 BREAKING CHANGE: MySQL-Only + Production Migrations

> **Note (2025-12-01):** Tests dùng **bộ migration production** cho group `tests` (migrate --all) thay vì golden schema. DevDatabaseTrait tự chạy migration này, sau đó bạn có thể dùng schema trait để truncate dữ liệu nếu cần.

**As of 2025-11-25, all tests use MySQL-only architecture. SQLite in-memory testing has been removed.**

### Why MySQL-Only?
- **Eliminates "fake tests"**: SQLite allowed loose syntax that MySQL rejected in production
- **Real production environment**: All tests run against actual MySQL database
- **Better data type validation**: DECIMAL, JSON, TIMESTAMP handled correctly
- **Consistent behavior**: What passes in tests will work in production

## Section 1: Test Pyramid (Updated)

We follow the standard Test Pyramid with **MySQL-only** approach:

- **60% Unit Tests**: Fast, isolated tests for Services, Repositories. **Now uses MySQL with transactions.**
- **30% Integration Tests**: API endpoints + database interactions. Uses MySQL.
- **10% E2E Tests**: Critical user flows (Login, Checkout).

### Trade-offs (MySQL-Only)
| Type | Speed | Cost | Confidence | Database |
|------|-------|------|------------|----------|
| Unit | Fast* | Low | High (Real DB) | MySQL (Transactional) |
| Integration | Medium | Medium | High (System) | MySQL (Full Stack) |
| E2E | Slow | High | High (User) | MySQL (Production-like) |

*Unit tests use transactions for fast rollback, making them nearly as fast as SQLite

## Section 2: Mandatory Testing Process

### Local Development (Every Commit)
1. **Write Test FIRST (TDD)**: Define behavior before implementation.
2. **Run Unit Tests**: `vendor/bin/phpunit` (MySQL with transactions)
3. **Verify MySQL Connection**: Tests will fail if MySQL is not running.

### Pre-commit (Mandatory)
1. All unit tests must pass (MySQL).
2. Integration tests must pass.
3. Test database must be accessible.
4. Run Safety Checks: `bash scripts/pre-commit-checks.sh`

### Pre-merge (Critical)
1. Full test suite pass on MySQL.
2. Coverage >= 70% for modified files.
3. Manual smoke test on dev server.

## Section 3: Test Environment Setup

### Prerequisites
**CRITICAL**: MySQL container (`db`) must be running before any tests:

```bash
# Start database + API
docker-compose up -d db api

# Verify connection
docker exec meomeo2-api-1 php spark db:info tests
```

### MySQL-Only Configuration (Main DB + rollback)
- **Database**: MySQL 8.4 (`lanocrm_test`/`lanocrm_shop` tùy env) – dùng rollback bảo vệ dữ liệu
- **Connection**: `backend-ci/app/Config/Database.php` group `tests` trỏ về DB test, `DBPrefix` rỗng
- **Test Config**: `phpunit.xml.dist` (group `tests`)
- **Auto-migration**: `DevDatabaseTrait` chạy `migrate --all` cho group `tests` (migration production), schema traits chỉ truncate dữ liệu nếu cần.

### Running Tests

```bash
# Unit Tests (MySQL with transactions)
docker exec meomeo2-api-1 vendor/bin/phpunit

# Integration Tests (MySQL full stack)
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Specific Test File
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/OrderServiceTest.php

# With Coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text
```

## Section 4: DevDatabaseTrait Pattern (NEW)

All tests now use **DevDatabaseTrait** for unified MySQL testing:

```php
<?php
namespace Tests\Services;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\[Specific]SchemaTrait;

class YourServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;           // MySQL connection + transactions
    use [Specific]SchemaTrait;      // Schema creation (if needed)
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();     // Opens connection + starts transaction
        
        // Optional: Create specific schema
        $this->reset[Specific]Schema();
        
        // Your test setup
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();  // Rollback transaction + close
        parent::tearDown();
    }
}
```

### Key Benefits:
- ✅ Automatic MySQL connection management
- ✅ Transaction-based isolation (fast cleanup)
- ✅ Schema auto-migration từ **migration production** (group `tests`) + schema traits (truncate-only)
- ✅ No SQLite fallback complexity
- ✅ Consistent with production environment

### Naming conventions (IMPORTANT)
- **Không dùng prefix `db_` cho bảng test**. Tất cả schema traits (CompleteSchemaTrait, PriceListSchemaTrait, WebhookSchemaTrait, …) tạo bảng với tên gốc: `products`, `orders`, `webhook_subscriptions`, v.v.
- Model/Repository cũng phải trỏ về tên gốc. Nếu thấy `db_*` trong code hoặc test, xem lại và sửa ngay.

## Section 5: Main Database + Transaction Rollback (tóm tắt)

- Mọi test chạy trên DB group `tests` (MySQL) nhưng luôn nằm trong transaction, rollback ở `tearDownDatabase()` ⇒ không làm bẩn dữ liệu thật.
- Cấu hình bắt buộc:
  - `app/Config/Database.php` group `tests`: hostname `db`/`db-test`, database `lanocrm_test` (hoặc main tùy env), `DBPrefix` rỗng.
  - `phpunit.xml.dist`: env `database.tests.*` khớp với config trên.
- Quy trình: `setUpDatabase()` mở kết nối + `transBegin()`, `tearDownDatabase()` rollback. Luôn gọi cả hai.
- Không dùng database/schema phụ; mọi *SchemaTrait* **chỉ truncate** bảng đã tạo bởi migration production.
- Nếu cần refresh schema thủ công: `docker exec meomeo2-api-1 php spark migrate --all -g tests` (production migrations).

## Section 6: Common Issues & Solutions

### ISSUE 1: "Connection refused" or "Database not found"
**Cause**: MySQL test container not running.
**Solution**:
```bash
# Start MySQL test container
docker-compose up -d db-test

# Check if accessible
docker exec meomeo2-api-1 php spark db:info tests
```

### ISSUE 2: Tests fail with "Table doesn't exist"
**Cause**: Schema trait not properly configured or not called.
**Solution**:
```php
protected function setUp(): void {
    parent::setUp();
    $this->setUpDatabase();
    $this->resetYourSchema();  // Make sure this is called!
}
```

### ISSUE 3: Tests pass individually but fail together
**Cause**: Transaction not properly rolled back or schema conflicts.
**Solution**: Ensure `tearDownDatabase()` is called in every test:
```php
protected function tearDown(): void {
    $this->tearDownDatabase();  // MUST be called!
    parent::tearDown();
}
```

### ISSUE 4: Slow tests
**Cause**: Schema recreation or no transaction usage.
**Solution**: 
- Use DevDatabaseTrait (transactions are automatic)
- Golden migration chạy một lần khi cần; schema traits chỉ truncate dữ liệu
- Chỉ data được rollback (nhanh)

### ISSUE 5: Webhook tests
**Cause**: Transaction isolation / prefix mismatch.  
**Solution**: Webhook tests dùng fakes in-memory (`tests/_support/Fakes/FakeWebhookSubscriptionRepository.php`, `FakeWebhookEventRepository.php`) – không cần DB.

## Section 7: Migration from SQLite (For Reference)

If you encounter old tests using SQLite, convert them:

### OLD Pattern (SQLite):
```php
$config->tests = [
    'DBDriver' => 'SQLite3',
    'database' => ':memory:',
    // ...
];
```

### NEW Pattern (MySQL-only):
```php
use DevDatabaseTrait;
use YourSchemaTrait;

protected function setUp(): void {
    parent::setUp();
    $this->setUpDatabase();  // Automatic MySQL
    $this->resetYourSchema();
}
```

**Important**: Remove all `if (extension_loaded('sqlite3'))` checks!

## Section 8: Coverage Requirements (Unchanged)
- **Minimum 70%** for all new Services and Repositories
- **100%** for critical business logic (Calculations, Permissions)
- **Tools**: Xdebug with PHPUnit for coverage reports

```bash
# Generate coverage report
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-html build/coverage
```

## Section 9: CI/CD Integration
- All tests run against MySQL in CI/CD
- Test database automatically provisioned
- Pre-merge hooks block commits if tests fail
- Coverage reports generated in `build/logs/`

### CI/CD Configuration:
```yaml
# Example GitHub Actions
services:
  mysql-test:
    image: mysql:8.4
    env:
      MYSQL_DATABASE: lanocrm_test
      MYSQL_USER: lanocrm_user
      MYSQL_PASSWORD: test_password
```

## Section 10: Best Practices

### DO:
✅ Use DevDatabaseTrait for all tests
✅ Call `setUp()` and `tearDown()` properly
✅ Write tests against real MySQL
✅ Use transactions for fast cleanup
✅ Keep schema traits focused and reusable

### DON'T:
❌ Use SQLite for any tests
❌ Skip `tearDownDatabase()` calls
❌ Hardcode database config in tests
❌ Mix SQLite and MySQL approaches
❌ Forget to start MySQL container

## Section 10: Quick Reference

```bash
# Start environment
docker-compose up -d db-test

# Run all tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Run specific test
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/OrderServiceTest.php

# Check coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Debug failing test
docker exec meomeo2-api-1 vendor/bin/phpunit --filter=test_method_name

# Integration tests
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml
```

---

**Last Updated**: 2025-11-25
**Migration Status**: MySQL-only architecture complete
**Deprecated**: SQLite in-memory testing (removed)
