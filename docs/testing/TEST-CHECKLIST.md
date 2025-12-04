---
title: "Test Checklist - Backend & Frontend"
id: "TEST-CHECKLIST-01"
version: "4.0"
status: "Active"
module: "Testing"
type: "Checklist"
tags: ["testing", "checklist", "backend", "frontend", "test-database-only", "real-database", "pr-process", "no-artificial-passing", "wsl"]
purpose: "Provides a mandatory checklist for all Pull Requests to ensure test-database-only testing for backend, real database integration for frontend, and no artificial test passing."
location: "docs/testing"
updated: "2025-12-03"
changes: "Updated YAML frontmatter for documentation consolidation, removed reference to deleted FRONTEND-TESTING-PATTERNS-01"
related_to:
  - id: "BACKEND-TESTING-01"
    description: "Refer to the backend testing guide with test database only."
  - id: "FRONTEND-TESTING-01"
    description: "Refer to the frontend testing guide with real database integration."
  - id: "TESTING-PATTERNS-01"
    description: "Testing patterns and assertions reference"
  - id: "TESTING-RULES-01"
    description: "Comprehensive testing rules and guidelines"
  - id: "PLAYWRIGHT-WSL-01"
    description: "Playwright WSL configuration and troubleshooting guide"
---

# Test Checklist - Copy into every PR

> **🚨 BREAKING CHANGE**:
> - **Backend**: Tests must use test database (`lanocrm_test`) ONLY - never modify dev database (`lanocrm_dev`)
> - **Frontend**: Tests must use real database integration - NEVER mock API calls or use fake data
> - **NO ARTIFICIAL PASSING**: Never modify tests to pass falsely. Fix implementation instead. See [Testing Rules](TESTING-RULES.md) for details.

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

## Backend Unit Tests ✓
- [ ] Service tests written and passing (DevDatabaseTrait + transactions)
- [ ] Repository tests written and passing (test database queries only)
- [ ] Validator tests written with test database validation
- [ ] Edge cases covered (null, empty, invalid test data)
- [ ] Exception handling tested with real test database constraints

## Backend Integration Tests ✓
- [ ] API endpoints tested with test database only
- [ ] Authentication/authorization works with test database
- [ ] Database transactions rollback correctly in test database
- [ ] File upload works with test database storage (if applicable)
- [ ] Cross-module interactions work with test database
- [ ] Foreign key constraints tested in test database (ON DELETE/UPDATE)
- [ ] JSON/JSONB columns tested in test database (not TEXT)

## Backend Manual Tests ✓
- [ ] Login flow works with test database auth
- [ ] CRUD operations work via Postman/curl against test database
- [ ] Error messages display correctly with test database constraints
- [ ] Validation messages are user-friendly
- [ ] Decimal precision handling works (DECIMAL vs REAL issues)

## Frontend Tests - Real Database Integration (CRITICAL) ✓

### Environment Setup ✓
- [ ] Backend test server running: `docker-compose up -d db-test api`
- [ ] Frontend dev server running: `cd lanocrm && npm run dev`
- [ ] Real API base URL configured: `VITE_API_BASE_URL=http://localhost:8080/api`
- [ ] Test database accessible from frontend

### Real Database Integration Rules ✓
- [ ] NEVER mock API calls (no vi.mock, no MSW, no fake responses)
- [ ] NEVER use fake data in tests (always use real API responses)
- [ ] ALWAYS call real backend APIs through axios/fetch
- [ ] ALWAYS verify data in real database through API calls
- [ ] Test database helpers used for setup/cleanup

### Frontend Unit Tests ✓
- [ ] Component tests written with real API integration
- [ ] Hook tests written with real API calls
- [ ] Utility function tests with real API integration
- [ ] Edge cases covered with real API responses
- [ ] Error handling tested with real API errors

### Frontend Integration Tests ✓
- [ ] Page tests written with real database integration
- [ ] Form submission tested with real API calls
- [ ] Data fetching tested with real API responses
- [ ] State management tested with real API integration
- [ ] Navigation tested with real data flow

### Frontend E2E Tests ✓
- [ ] Complete user flows tested with real backend
- [ ] Browser automation with real API calls
- [ ] Real data scenarios tested end-to-end
- [ ] Cross-browser compatibility tested with real integration

### Frontend Manual Tests ✓
- [ ] UI works with real backend data
- [ ] Forms submit to real database
- [ ] Data displays correctly from real API
- [ ] Error messages show from real API responses
- [ ] Navigation works with real data flow

## No Artificial Test Passing (CRITICAL) ✓
- [ ] KHÔNG xóa assertions để test pass (check: không có `assertTrue(true)` không cần thiết)
- [ ] KHÔNG thay đổi expected values để match implementation bị lỗi
- [ ] KHÔNG mock API/database để hide bugs (check: không có vi.mock, createMock trong test)
- [ ] KHÔNG skip tests thay vì fix implementation (check: không có markTestSkipped không cần thiết)
- [ ] KHÔNG thay đổi logic test để pass với code bị lỗi
- [ ] Fix implementation khi test fail, KHÔNG sửa test để pass

## Code Quality ✓
- [ ] Backend Coverage >= 70% (check: `docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text`)
- [ ] Frontend Coverage >= 70% (check: `cd lanocrm && npm run test:coverage`)
- [ ] No PHPUnit warnings/errors
- [ ] No Vitest warnings/errors
- [ ] Single Responsibility followed (controllers thin, services have logic)
- [ ] Inline docs complete (@agent-* tags, test-database-specific annotations)
- [ ] No SQLite patterns (check for extension_loaded, SQLite3 strings)
- [ ] No API mocking patterns (check for vi.mock, MSW setup)
- [ ] No artificial passing patterns (check for assertTrue(true), removed assertions)

## Safety Checks ✓
- [ ] TUYỆT ĐỐI không dùng `DROP DATABASE`/`DROP TABLE`/`DROP INDEX` trong tests, schema traits hay scripts cleanup; chỉ dùng transaction rollback + `TRUNCATE`/`DELETE` trong `resetYourSchema()`.
- [ ] TUYỆT ĐỐI không sửa cấu trúc bảng (ALTER TABLE), thay đổi FK, hay schema trong test database; chỉ được reset dữ liệu.
- [ ] TUYỆT ĐỐI không kết nối tới dev database (`lanocrm_dev`) trong tests.
- [ ] TUYỆT ĐỐI không mock API calls trong frontend tests; luôn dùng real API calls.
- [ ] Run: `bash .ai/pre-commit-checks.sh` - PASS
- [ ] Auth endpoints still work (test login with test database)
- [ ] Existing features not broken by test database migration
- [ ] Only modified files within task scope
- [ ] No hard-coded database configs (used Database config)
- [ ] No hardcoded API URLs in frontend tests

## Test Database Specific Validation ✓
- [ ] DECIMAL columns handle precision correctly in test database
- [ ] JSON/JSONB columns validate properly in test database (MySQL 8+)
- [ ] TIMESTAMP vs DATETIME handled correctly in test database
- [ ] ENGINE=InnoDB used correctly in test database (not MyISAM)
- [ ] Foreign key constraints defined properly in test database
- [ ] Index patterns optimal in test database (PRIMARY, INDEX, UNIQUE)

## Frontend Real Database Validation ✓
- [ ] API calls reach real backend endpoints
- [ ] Data flows from UI to real database
- [ ] No API mocking detected in test files
- [ ] Real test data used in all scenarios
- [ ] Error responses come from real backend validation

## Performance (High Priority) ✓
- [ ] Query N+1 problems checked (especially with relations in test database)
- [ ] Large dataset tested (>1000 records in test database)
- [ ] Response time < 500ms with test database
- [ ] Index usage verified with EXPLAIN in test database
- [ ] Transaction isolation levels appropriate for test database
- [ ] Frontend API response times < 2 seconds
- [ ] Frontend rendering performance with real data
- [ ] E2E test execution time < 30 seconds per flow

## Migration Cleanup ✓
- [ ] Remove old SQLite fallback code:
  ```php
  // REMOVE these ❌
  if (extension_loaded('sqlite3')) { ... }
  $config->tests = ['DBDriver' => 'SQLite3', ...];
  ```
- [ ] Remove old API mocking code:
  ```javascript
  // REMOVE these ❌
  vi.mock('../api/productApi', () => ({ ... }));
  setupServer(...); // MSW setup
  ```
- [ ] Ensure all schema traits use MySQL syntax
- [ ] Validate all `VARCHAR` instead of `TEXT` where appropriate
- [ ] Test JSON operations work with MySQL functions
- [ ] Ensure all frontend tests use real API calls

---

**CRITICAL ITEMS that MUST pass:**

### Backend Requirements:
1. **Test database container must be running** or all backend tests will fail
2. **DevDatabaseTrait + SchemaTrait pattern required** - no exceptions
3. **NEVER connect to dev database** - tests must use `lanocrm_test` only
4. **No DROP/ALTER commands** - use TRUNCATE/DELETE for data only, never modify schema
5. **Backend Coverage >= 70%** - automatically enforced in CI

### Frontend Requirements:
6. **Real database integration required** - never mock API calls or use fake data
7. **Frontend Coverage >= 70%** - automatically enforced in CI
8. **No API mocking patterns** - will be blocked by pre-commit hooks
9. **Real API calls only** - all tests must call actual backend endpoints

### Combined Requirements:
10. **Test data isolation** - never affect dev data
11. **End-to-end validation** - complete data flow from UI to database
12. **Real error testing** - test actual API error responses, not mocked ones

**If ANY item FAILS → FIX before merging!**

### Quick Commands:

#### Backend Tests:
```bash
# Start test environment
docker-compose up -d db-test

# Run backend unit tests (test database only)
docker exec meomeo2-api-1 vendor/bin/phpunit

# Run backend integration tests (test database only)
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Check backend coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Check test database connection
docker exec meomeo2-api-1 php spark db:info tests

# Run backend lint checks
docker exec meomeo2-api-1 vendor/bin/phpcs tests/
```

#### Frontend Tests:
```bash
# Start frontend test environment
cd lanocrm
npm run dev

# Run frontend unit tests (real database integration)
npm test

# Run frontend integration tests (real database integration)
npm test

# Check frontend coverage
npm run test:coverage

# Run frontend E2E tests (real database integration)
npm run test:e2e

# Run specific E2E test with chromium
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# Run frontend lint checks
npm run lint
```

#### WSL-Specific Commands:
```bash
# On WSL, ensure proper permissions for E2E tests
cd ~/projects/kiotviet/lanocrm

# Install Playwright browsers with admin rights if needed
sudo npx playwright install --with-deps chromium

# Run E2E with proper environment
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# If permission issues occur
sudo npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts
```

#### Full Stack Tests:
```bash
# Start complete test environment
docker-compose up -d db-test api frontend

# Run all tests (backend + frontend)
npm run test:all

# Verify test database isolation
docker exec db-test mysql -u root -p -e "SELECT DATABASE();"
```

---

**CRITICAL**: This is the test-database-only + real-database-integration + no-artificial-passing era.
**Backend**: All tests must use `lanocrm_test` and never touch `lanocrm_dev`.
**Frontend**: All tests must use real API calls and never mock responses.
**No Artificial Passing**: Always fix implementation, never modify tests to pass falsely.
**WSL Support**: Ensure tests work properly on WSL environments with proper permissions.
**These patterns are mandatory** for all new tests and refactoring.

### Quick E2E Command Reference:
```bash
# Primary E2E command (example)
cd ~/projects/kiotviet/lanocrm
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# All E2E tests
npm run test:e2e

# With UI for debugging
npm run test:e2e -- --ui

# WSL with admin rights (if needed)
sudo npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts
```
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
