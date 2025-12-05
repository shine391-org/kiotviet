---
id: "PROJECT-README"
title: "LANO CRM - Project Overview"
type: "navigation"
purpose: "Entry point for AI agents and developers"
status: "Active"
updated: "2025-12-05"
container: "kiotviet-web-1"
tech_stack:
  backend: "PHP 8.4 + CodeIgniter 4"
  frontend: "React 18 + Vite"
  database: "MySQL 8.4"
  testing: "PHPUnit + Vitest + Playwright"
primary_docs:
  - path: "AGENTS.md"
    purpose: "Architecture patterns, code examples, workflow"
  - path: "DEPLOYMENT.md"
    purpose: "Deployment guide"
testing_docs:
  - path: "docs/testing/TESTING-RULES.md"
    purpose: "Rules + Commands + Checklist - MUST READ"
  - path: "docs/testing/BACKEND-TESTING.md"
    purpose: "Backend test patterns"
  - path: "docs/testing/FRONTEND-TESTING.md"
    purpose: "Frontend test patterns"
---

# LANO CRM

> **AI Agents:** Start with `AGENTS.md` - your single source of truth.  
> **Humans:** See `HUMANREADME.md` for setup instructions.

---

## Quick Start

```bash
# Start all containers
docker compose up -d

# Run backend tests
docker exec kiotviet-web-1 vendor/bin/phpunit

# Run frontend tests
cd lanocrm && npm test
```

---

## Project Structure

```
backend-ci/app/
├── Controllers/Api/  → Routing only
├── Services/         → Business logic
├── Repositories/     → Database queries
├── Models/           → Schema
└── Validators/       → Input validation

lanocrm/src/          → React frontend
docs/                 → Documentation
```

---

## Architecture

```
Request → Controller → Service → Repository → Model → DB
```

**Rules:**
- Controller: routing only, max ~350 lines
- Service: business logic, max ~700 lines
- Repository: database queries, max ~500 lines
- **Never mix concerns between layers**

---

## Documentation

| File | Purpose |
|------|---------|
| `AGENTS.md` | **Start here** - patterns, workflow |
| `DEPLOYMENT.md` | Deployment guide |
| `docs/testing/TESTING-RULES.md` | **Rules + Commands + Checklist** |
| `docs/testing/BACKEND-TESTING.md` | Backend patterns |
| `docs/testing/FRONTEND-TESTING.md` | Frontend patterns |

---

## Commands

### Backend
```bash
# Tests
docker exec kiotviet-web-1 vendor/bin/phpunit
docker exec kiotviet-web-1 vendor/bin/phpunit -c phpunit.integration.xml
docker exec kiotviet-web-1 vendor/bin/phpunit --coverage-text

# Migrations & Seed
docker exec kiotviet-web-1 php spark migrate --all
docker exec kiotviet-web-1 php spark db:seed DevSeeder
```

### Frontend
```bash
cd lanocrm
npm test                # Unit tests
npm run test:coverage   # Coverage
npm run test:e2e        # E2E tests
npm run dev             # Dev server
```

### Pre-commit
```bash
bash .ai/pre-commit-checks.sh
```

---

## Testing Rules

1. **No artificial test passing** - never modify tests to hide bugs
2. **Database isolation** - tests run on `lanocrm_test` only
3. **No DROP commands** - use transaction rollback
4. **Coverage target** - 70%+

---

## Definition of Done

- [ ] Clean architecture followed
- [ ] Unit tests pass (70%+ coverage)
- [ ] Integration tests pass
- [ ] No console errors
- [ ] Code reviewed

---

## Principles

- Copy existing patterns, don't reinvent
- Quality > Speed
- Tests are mandatory
- Clean architecture is not optional
