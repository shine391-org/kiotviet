---
id: "TESTING-RULES"
title: "Testing Rules, Commands & Checklist"
type: "guide"
purpose: "Single entry point for all testing - rules, commands, checklist, troubleshooting"
status: "Active"
updated: "2025-12-05"
container: "kiotviet-web-1"
database:
  test: "lanocrm_test"
  dev: "lanocrm_dev (NEVER touch in tests)"
coverage_target: "70%"
related_docs:
  - path: "BACKEND-TESTING.md"
    purpose: "Backend patterns detail"
  - path: "FRONTEND-TESTING.md"
    purpose: "Frontend patterns detail"
  - path: "INTEGRATION-TESTING-GUIDE.md"
    purpose: "Integration testing"
---

# Testing Rules, Commands & Checklist

> **Container:** `kiotviet-web-1`  
> **Test DB:** `lanocrm_test` (NEVER touch `lanocrm_dev`)  
> **Coverage Target:** 70%+

---

## Quick Commands

### Backend
```bash
# Unit tests
docker exec kiotviet-web-1 vendor/bin/phpunit

# Integration tests
docker exec kiotviet-web-1 vendor/bin/phpunit -c phpunit.integration.xml

# Coverage
docker exec kiotviet-web-1 vendor/bin/phpunit --coverage-text

# Specific test
docker exec kiotviet-web-1 vendor/bin/phpunit tests/Services/ProductServiceTest.php

# Check DB connection
docker exec kiotviet-web-1 php spark db:info tests
```

### Frontend
```bash
cd lanocrm

# Unit tests
npm test

# Coverage
npm run test:coverage

# E2E tests
npm run test:e2e

# E2E specific
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts
```

### Pre-commit
```bash
bash .ai/pre-commit-checks.sh
```

---

## Golden Rules

### 1. No Artificial Test Passing

**FORBIDDEN:**
```php
// ❌ Removing assertions
$this->assertTrue(true);

// ❌ Changing expected to match bug
$this->assertEquals(150, $result); // Should be 200

// ❌ Mocking to hide bugs
$mockDb = $this->createMock(Database::class);

// ❌ Skipping instead of fixing
$this->markTestSkipped('Feature broken');
```

**CORRECT:**
```php
// ✅ Test real behavior
$this->assertNotNull($product->id);
$this->assertEquals($validData['name'], $product->name);

// ✅ Test errors
$this->expectException(ValidationException::class);
```

### 2. Database Isolation

- **Backend:** Tests use `lanocrm_test` only
- **Frontend:** Real API calls to test backend
- **Never:** DROP, ALTER, or touch `lanocrm_dev`
- **Cleanup:** Transaction rollback + TRUNCATE

### 3. Frontend: No Mocking

```javascript
// ❌ FORBIDDEN
vi.mock('../api/productApi', () => ({...}));

// ✅ REQUIRED - Real API calls
const result = await api.createProduct(data);
expect(result.success).toBe(true);
```

---

## Test Pattern

```php
use Tests\Support\Database\DevDatabaseTrait;

class YourServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    
    protected function setUp(): void {
        parent::setUp();
        $this->setUpDatabase();
    }
    
    protected function tearDown(): void {
        $this->tearDownDatabase();
        parent::tearDown();
    }
}
```

---

## PR Checklist

### Before Commit
- [ ] All tests pass
- [ ] Coverage >= 70%
- [ ] No `assertTrue(true)` patterns
- [ ] No `vi.mock` (frontend)
- [ ] No `markTestSkipped` without reason
- [ ] `bash .ai/pre-commit-checks.sh` passes

### Backend
- [ ] Service tests written
- [ ] Edge cases covered
- [ ] Error handling tested
- [ ] DevDatabaseTrait used

### Frontend
- [ ] Real API calls only
- [ ] No mocking patterns
- [ ] Error messages tested

### Safety
- [ ] No DROP/ALTER commands
- [ ] No connection to `lanocrm_dev`

---

## Common Issues

### "Connection refused"
```bash
docker-compose up -d db-test
docker exec kiotviet-web-1 php spark db:info tests
```

### "Table doesn't exist"
```bash
docker exec kiotviet-web-1 php spark migrate --all
```

### Coverage too low
```bash
# Backend
docker exec kiotviet-web-1 vendor/bin/phpunit --coverage-text

# Frontend
cd lanocrm && npm run test:coverage
```

### E2E timeout (WSL)
```bash
npm run test:e2e -- --timeout=60000
```

---

## Edge Case Examples

```php
// Null
$this->expectException(ValidationException::class);
$this->service->create(['code' => null]);

// Empty
$this->expectException(ValidationException::class);
$this->service->create(['code' => '']);

// Long string
$this->expectException(ValidationException::class);
$this->service->create(['code' => str_repeat('A', 300)]);

// Boundary
$this->expectException(ValidationException::class);
$this->service->create(['selling_price' => -1]);
```

---

## Factory Pattern

```php
class ProductFactory
{
    public static function create(array $overrides = []): array
    {
        return array_merge([
            'code' => 'PROD-' . uniqid(),
            'name' => 'Test Product',
            'selling_price' => 100000,
            'status' => 'active',
        ], $overrides);
    }
}
```

---

## Related Docs

| Doc | Purpose |
|-----|---------|
| `BACKEND-TESTING.md` | Backend patterns detail |
| `FRONTEND-TESTING.md` | Frontend patterns detail |
| `INTEGRATION-TESTING-GUIDE.md` | Integration testing |

---

**Remember:** Fix implementation when tests fail, never the test.
