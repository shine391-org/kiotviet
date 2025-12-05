---
id: "AGENT-GUIDE"
title: "AI Agent Guide - LANO CRM"
type: "guide"
purpose: "Single source of truth for AI agents - architecture, patterns, testing"
status: "Active"
updated: "2025-12-05"
container: "kiotviet-web-1"
tech_stack:
  backend: "PHP 8.4 + CodeIgniter 4"
  frontend: "React 18 + Vite"
  database: "MySQL 8.4"
  testing: "PHPUnit + Vitest"
current_focus:
  status: "Core modules done, FE-BE integration phase"
  priorities:
    - "Frontend-Backend integration"
    - "Bug fixes"
    - "Testing coverage improvement"
  avoid:
    - "Major refactoring without asking"
    - "Adding new dependencies"
    - "Changing database schema"
architecture:
  flow: "Controller → Service → Repository → Model → DB"
  controller_max: 350
  service_max: 700
  repository_max: 500
file_limits:
  controller: "~350 lines - routing only"
  service: "~700 lines - business logic"
  repository: "~500 lines - database queries"
testing:
  database: "lanocrm_test"
  coverage_target: "70%"
  golden_rules:
    - "No artificial test passing"
    - "Database isolation"
    - "No DROP commands"
    - "Real API calls (frontend)"
docs:
  testing_rules: "docs/testing/TESTING-RULES.md"
  backend_testing: "docs/testing/BACKEND-TESTING.md"
  frontend_testing: "docs/testing/FRONTEND-TESTING.md"
---

# AI Agent Guide - LANO CRM

> **Single source of truth** for AI agents working on this project.

---

## Current Focus

**Status:** Core modules implemented, focus on FE-BE integration

**Priorities:**
1. Frontend-Backend integration
2. Bug fixes
3. Testing coverage improvement

**Avoid without asking:**
- Major refactoring
- Adding new dependencies
- Changing database schema

---

## Project Structure

```
backend-ci/app/
├── Controllers/Api/  → Routing only (max ~350 lines)
├── Services/         → Business logic (max ~700 lines)
├── Repositories/     → Database queries (max ~500 lines)
├── Models/           → Schema definition
└── Validators/       → Input validation

lanocrm/src/          → React frontend
docs/                 → Documentation
```

---

## Architecture

```
Request → Controller → Service → Repository → Model → DB
          (routing)   (logic)   (queries)    (schema)
```

**Rules:**
- Controller: routing only, delegate to Service
- Service: business logic, use Repository for DB
- Repository: database queries only
- **Never mix concerns between layers**

---

## Code Patterns

### Controller (Thin)
```php
// With error handling wrapper
public function index() {
    return $this->wrap(fn () => $this->respond(
        $this->service->list($this->request->getGet())
    ));
}

// Simple version
public function show($id) {
    return $this->respond($this->service->get((int) $id));
}
```

### Service (Business Logic)
```php
public function list(array $filters): array {
    $validated = $this->validator->validateListFilters($filters);
    $items = $this->repo->findAll($validated);
    $total = $this->repo->count($validated);
    return ['success' => true, 'data' => $items, 'pagination' => ...];
}
```

### Repository (Database)
```php
public function findAll(array $filters): array {
    $b = $this->applyFilters($filters);
    $limit = $filters['limit'] ?? 20;
    $offset = (($filters['page'] ?? 1) - 1) * $limit;
    return $b->orderBy('created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray();
}
```

---

## Testing

> **MUST READ:** `docs/testing/TESTING-RULES.md`

### Golden Rules
1. **No Artificial Test Passing** - Never modify tests to hide bugs
2. **Database Isolation** - Tests run on `lanocrm_test` only
3. **No DROP commands** - Use transaction rollback + TRUNCATE
4. **Real API calls** - Frontend tests must call real backend

### Commands
```bash
# Unit tests
docker exec kiotviet-web-1 vendor/bin/phpunit

# Integration tests
docker exec kiotviet-web-1 vendor/bin/phpunit -c phpunit.integration.xml

# Coverage (target: 70%+)
docker exec kiotviet-web-1 vendor/bin/phpunit --coverage-text

# Frontend
cd lanocrm && npm test
```

### Test Patterns

**Option 1: With Database (Integration)**
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

**Option 2: With FakeRepo (Unit - faster)**
```php
class YourServiceTest extends CIUnitTestCase
{
    private YourService $service;
    private FakeRepository $repo;

    protected function setUp(): void {
        parent::setUp();
        $this->repo = new FakeRepository();
        $this->service = new YourService($this->repo);
    }
}
```

### What to Test
- All public methods
- Validation errors
- Edge cases (null, empty, special chars, boundaries)
- Error messages

---

## Documentation

| Doc | Purpose |
|-----|---------|
| `docs/testing/TESTING-RULES.md` | **Rules + Commands + Checklist** |
| `docs/testing/BACKEND-TESTING.md` | Backend patterns |
| `docs/testing/FRONTEND-TESTING.md` | Frontend patterns |
| `DEPLOYMENT.md` | Deployment guide |

---

## Definition of Done

- [ ] Clean architecture followed
- [ ] Unit tests pass (70%+ coverage)
- [ ] Integration tests pass
- [ ] No console errors
- [ ] Code reviewed

---

## Common Mistakes

```php
// ❌ BAD - Logic in controller
public function index() {
    $products = $this->model->where('deleted_at', null)->findAll();
}

// ✅ GOOD - Delegate to service
public function index() {
    return $this->respond($this->service->list($this->request->getGet()));
}
```

```php
// ❌ BAD - DB query in service
$db = \Config\Database::connect();
$products = $db->table('products')->get();

// ✅ GOOD - Use repository
$products = $this->repo->findAll($filters);
```

---

## Quick Commands

```bash
# Start containers
docker compose up -d

# Migrations
docker exec kiotviet-web-1 php spark migrate --all

# Seed data
docker exec kiotviet-web-1 php spark db:seed DevSeeder

# Pre-commit checks
bash .ai/pre-commit-checks.sh
```

---

## Principles

- Copy existing patterns, don't reinvent
- Quality > Speed
- Tests are mandatory
- Clean architecture is not optional
