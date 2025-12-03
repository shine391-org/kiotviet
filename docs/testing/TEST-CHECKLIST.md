---
title: "Test Checklist - Backend"
id: "TEST-CHECKLIST-01"
version: "2.0"
status: "Active"
module: "Testing"
type: "Checklist"
tags: ["testing", "checklist", "backend", "mysql-only", "pr-process"]
purpose: "Provides a mandatory checklist for all Pull Requests to ensure MySQL-only testing, code quality, test coverage, and functionality for backend changes."
location: "docs/testing"
updated: "2025-11-25"
changes: "Updated for MySQL-only testing with DevDatabaseTrait patterns"
related_to:
  - id: "TESTING-GUIDE-01"
    description: "Refer to the MySQL-only testing guide."
  - id: "TESTING-PATTERNS-01"
    description: "Contains MySQL-only test patterns to copy."
---

# Test Checklist - Copy into every PR

> **🚨 BREAKING CHANGE**: All tests now use MySQL-only architecture. Replace old SQLite patterns.

## MySQL-Only Tests (CRITICAL) ✓

### Environment Setup ✓
- [ ] MySQL test container running: `docker-compose up -d db-test`
- [ ] DB connection works: `docker exec meomeo2-api-1 php spark db:info tests`
- [ ] DevDatabaseTrait used properly (no SQLite fallback)
- [ ] Schema + data chuẩn: đã chạy `php spark migrate --all` **và** `php spark db:seed DemoSeeder` (hoặc import dump `backend-ci/db-dumps/lanocrm_test_seeded_20251203.sql`) để có đủ 176 bảng/301 FK + demo data.

### New Pattern Required ✓
Your tests MUST follow this pattern:

```php
use DevDatabaseTrait;
use YourSchemaTrait;

protected function setUp(): void {
    parent::setUp();
    $this->setUpDatabase();     // Auto MySQL + transaction
    $this->resetYourSchema();   // Schema creation (if needed)
}

protected function tearDown(): void {
    $this->tearDownDatabase();  // Transactions rolled back
    parent::tearDown();
}
```

## Unit Tests ✓
- [ ] Service tests written and passing (DevDatabaseTrait + transactions)
- [ ] Repository tests written and passing (MySQL queries tested)
- [ ] Validator tests written with MySQL validation
- [ ] Edge cases covered (null, empty, invalid MySQL data)
- [ ] Exception handling tested with real MySQL constraints

## Integration Tests ✓
- [ ] API endpoints tested with MySQL (not SQLite)
- [ ] Authentication/authorization works
- [ ] Database transactions rollback correctly
- [ ] File upload works with MySQL storage (if applicable)
- [ ] Cross-module interactions work with MySQL
- [ ] Foreign key constraints tested (ON DELETE/UPDATE)
- [ ] JSON/JSONB columns tested in MySQL (not TEXT)

## Manual Tests ✓
- [ ] Login flow works with MySQL auth
- [ ] CRUD operations work via Postman/curl against MySQL
- [ ] Error messages display correctly with MySQL constraints
- [ ] Validation messages are user-friendly
- [ ] Decimal precision handling works (DECIMAL vs REAL issues)

## Code Quality ✓
- [ ] Coverage >= 70% (check: `docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text`)
- [ ] No PHPUnit warnings/errors
- [ ] Single Responsibility followed (controllers thin, services have logic)
- [ ] Inline docs complete (@agent-* tags, MySQL-specific annotations)
- [ ] No SQLite patterns (check for extension_loaded, SQLite3 strings)

## Safety Checks ✓
- [ ] TUYỆT ĐỐI không dùng `DROP DATABASE`/`DROP TABLE`/`DROP INDEX` trong tests, schema traits hay scripts cleanup; chỉ dùng transaction rollback + `TRUNCATE`/`DELETE` trong `resetYourSchema()`.
- [ ] Run: `bash .ai/pre-commit-checks.sh` - PASS
- [ ] Auth endpoints still work (test login with MySQL)
- [ ] Existing features not broken by MySQL migration
- [ ] Only modified files within task scope
- [ ] No hard-coded database configs (used Database config)

## MySQL-Specific Validation ✓
- [ ] DECIMAL columns handle precision correctly
- [ ] JSON/JSONB columns validate properly (MySQL 8+)
- [ ] TIMESTAMP vs DATETIME handled correctly
- [ ] ENGINE=InnoDB used correctly (not MyISAM)
- [ ] Foreign key constraints defined properly
- [ ] Index patterns optimal (PRIMARY, INDEX, UNIQUE)

## Performance (High Priority) ✓
- [ ] Query N+1 problems checked (especially with relations)
- [ ] Large dataset tested (>1000 records in MySQL)
- [ ] Response time < 500ms with MySQL
- [ ] Index usage verified with EXPLAIN
- [ ] Transaction isolation levels appropriate

## Migration Cleanup ✓
- [ ] Remove old SQLite fallback code:
  ```php
  // REMOVE these ❌
  if (extension_loaded('sqlite3')) { ... }
  $config->tests = ['DBDriver' => 'SQLite3', ...];
  ```
- [ ] Ensure all schema traits use MySQL syntax
- [ ] Validate all `VARCHAR` instead of `TEXT` where appropriate
- [ ] Test JSON operations work with MySQL functions

---

**CRITICAL ITEMS that MUST pass:**

1. **MySQL container must be running** or all tests will fail
2. **DevDatabaseTrait + SchemaTrait pattern required** - no exceptions
3. **Coverage >= 70%** - automatically enforced in CI
4. **No SQLite patterns** - will be blocked by pre-commit hooks
5. **MySQL data types validated** - DECIMAL, JSON, TIMESTAMP

**If ANY item FAILS → FIX before merging!**

### MySQL-Only Quick Commands:

```bash
# Start environment
docker-compose up -d db-test

# Run unit tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Run integration tests
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Check coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Check MySQL connection
docker exec meomeo2-api-1 php spark db:info tests

# Run lint checks
docker exec meomeo2-api-1 vendor/bin/phpcs tests/
```

---

**CRITICAL**: This is the MySQL-only era. All legacy SQLite code has been eliminated. 
**These patterns are mandatory** cho tất cả test mới và refactoring.
<environment_details>
# Visual Studio Code Visible Files
docs/testing/TEST-CHECKLIST.md

# Visual Studio Code Open Tabs
backend-ci/app/Controllers/Api/ReturnsController.php
backend-ci/phpunit.xml.dist
backend-ci/tests/Services/ProductServiceTest.php
backend-ci/composer.json
backend-ci/tests/Services/OrderServiceTest.php
backend-ci/tests/_support/Database/SupportingSchemaTrait.php
backend-ci/tests/_support/Database/StatusSchemaTrait.php
backend-ci/tests/_support/Database/WebhookSchemaTrait.php
backend-ci/tests/_support/Database/PaymentMethodSchemaTrait.php
backend-ci/tests/_support/Database/InventoryStockSchemaTrait.php
backend-ci/tests/_support/Database/PriceListSchemaTrait.php
backend-ci/tests/_support/Database/ReturnSchemaTrait.php
backend-ci/tests/Support/Database/DevDatabaseTrait.php
backend-ci/tests/Services/PriceListServiceTest.php
backend-ci/tests/Services/WebhookSubscriptionServiceTest.php
backend-ci/tests/Services/ReturnServiceTest.php
backend-ci/tests/Services/InvoiceServiceTest.php
backend-ci/tests/_support/Database/InvoiceSchemaTrait.php
docs/testing/TESTING-GUIDE.md
AGENTS.md
docs/testing/TESTING-PATTERNS.md
docs/testing/TEST-CHECKLIST.md

# Current Time
11/25/2025, 1:26:16 AM (UTC, UTC+0:00)

# Context Window Usage
130,329 / 128K tokens used (102%)

# Current Mode
ACT MODE
</environment_details>
