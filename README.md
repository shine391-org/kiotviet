# LANO CRM - AI Agent Development

> **🤖 For AI Agents:** This README is optimized for AI-driven development. For human setup instructions, see [HUMANREADME.md](HUMANREADME.md).

---

## 🎯 Start Here

**Primary Guide:** [AGENTS.md](AGENTS.md) - Your single source of truth

This file contains:
- ✅ Architecture patterns (Controller → Service → Repository)
- ✅ Code examples to copy
- ✅ Testing workflows
- ✅ Common mistakes to avoid
- ✅ Definition of Done

---

## 📁 Project Overview

**Tech Stack:**
- **Backend:** PHP 8.4 + CodeIgniter 4 + Clean Architecture
- **Frontend:** React 18 + Redux + Vite
- **Database:** MySQL 8.4
- **Testing:** PHPUnit 10 + Vitest + Playwright

**Current Status:** [docs/plans/BACKEND-REFACTOR-PLAN.md](docs/plans/BACKEND-REFACTOR-PLAN.md)

---

## 🏗️ Architecture Quick Reference

### Backend Structure
```
app/
├── Controllers/Api/  → Routing only (thin, 3-5KB)
├── Services/         → Business logic
├── Repositories/     → Database queries
├── Models/           → Schema (passive)
└── Validators/       → Input validation
```

### Layer Flow
```
Request → Controller → Service → Repository → Model → Database
          (5 lines)   (logic)   (queries)    (schema)
```

**Key Rule:** Each layer has ONE responsibility. Never mix concerns.

---

## 📚 Essential Documentation

### For Development
1. **[AGENTS.md](AGENTS.md)** - Start here! Complete dev guide
2. **[docs/testing/TESTING-PATTERNS.md](docs/testing/TESTING-PATTERNS.md)** - Copy-paste test patterns
3. **[docs/testing/TESTING-GUIDE.md](docs/testing/TESTING-GUIDE.md)** - Testing strategy & commands
4. **[docs/plans/BACKEND-REFACTOR-PLAN.md](docs/plans/BACKEND-REFACTOR-PLAN.md)** - Task status & roadmap

### Testing Guides
- **Backend:** [docs/testing/TESTING-PATTERNS.md](docs/testing/TESTING-PATTERNS.md)
- **Frontend:** [docs/testing/FE-TESTING-PATTERNS.md](docs/testing/FE-TESTING-PATTERNS.md)
- **Checklist:** [docs/testing/FE-TEST-CHECKLIST.md](docs/testing/FE-TEST-CHECKLIST.md)

### Reference Patterns
- **Refactor Example:** [docs/tasks/refactor/REFACTOR-001-ProductService.md](docs/tasks/refactor/REFACTOR-001-ProductService.md)
- **New Module Example:** [docs/tasks/new-modules/Task-001-Inventory.md](docs/tasks/new-modules/Task-001-Inventory.md)

---

## 🚀 Quick Commands

### Testing Backend
```bash
# Unit tests (SQLite - fast)
docker exec meomeo2-api-1 vendor/bin/phpunit

# Integration tests (MySQL - real DB)
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Specific test file
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/ProductServiceTest.php

# With coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text
```

### Testing Frontend
```bash
cd lanocrm
npm test                # Unit/integration
npm run test:coverage   # Coverage report
npm run test:e2e        # E2E with Playwright
```

### Development
```bash
# Start backend + DB
docker compose up -d db api

# Start frontend
cd lanocrm && npm run dev

# Seed sample data
docker exec meomeo2-api-1 php spark db:seed DevSeeder
```

---

## 📋 Workflow for New Tasks

1. **Read task file** in `docs/tasks/`
2. **Follow AGENTS.md workflow** (Steps 1-7)
3. **Copy patterns** from existing code
4. **Write tests first** (TDD)
5. **Run both unit and integration tests**
6. **Create session log** in `docs/session-logs/`
7. **Commit with proper message**

**Template:** Use [docs/session-logs/TEMPLATE.md](docs/session-logs/TEMPLATE.md) for session logs

---

## ✅ Definition of Done

Before considering ANY task complete:

- [ ] All files follow clean architecture
- [ ] Unit tests written and passing (70%+ coverage)
- [ ] Integration tests passing (if applicable)
- [ ] Inline documentation added (@agent- tags)
- [ ] API endpoints tested manually
- [ ] Session log created
- [ ] Task status updated in plan
- [ ] No console errors in frontend
- [ ] Backward compatible

**Critical:** Tests are NOT optional. No merge without tests.

---

## 🎓 Learning Resources

### Code Patterns
All code patterns are in [AGENTS.md](AGENTS.md):
- Controller pattern (lines 62-99)
- Service pattern (lines 107-167)
- Repository pattern (lines 169-229)

### Test Patterns
Copy-paste ready test code in [docs/testing/TESTING-PATTERNS.md](docs/testing/TESTING-PATTERNS.md):
- Unit test pattern (Pattern 1)
- Integration test pattern (Pattern 2)
- Repository test pattern (Pattern 3)

### Anti-Patterns
Common mistakes to avoid in [AGENTS.md](AGENTS.md) (lines 298-331):
- ❌ Fat controllers
- ❌ Direct DB in services
- ❌ Missing tests
- ❌ Mixed concerns

---

## 📞 Getting Help

1. **Architecture questions:** Read [AGENTS.md](AGENTS.md)
2. **Testing issues:** Check [docs/testing/TESTING-GUIDE.md](docs/testing/TESTING-GUIDE.md) Section 4 (Common Issues)
3. **Pattern reference:** Copy from [docs/tasks/refactor/REFACTOR-001-ProductService.md](docs/tasks/refactor/REFACTOR-001-ProductService.md)
4. **Task status:** See [docs/plans/BACKEND-REFACTOR-PLAN.md](docs/plans/BACKEND-REFACTOR-PLAN.md)

---

## 🔑 Core Principles

1. **Clean Architecture is mandatory** - No exceptions
2. **Copy successful patterns** - Don't reinvent (REFACTOR-001 is template)
3. **Test everything** - No merge without tests
4. **Quality > Speed** - Better slow and correct than fast and broken
5. **Document as you go** - Inline docs + session logs required

---

**For human developers:** See [HUMANREADME.md](HUMANREADME.md) for setup instructions.

**Quick Links:**
- [AGENTS.MD - Your primary guide](AGENTS.md)
- [Task list & status](docs/plans/BACKEND-REFACTOR-PLAN.md)
- [Testing patterns](docs/testing/TESTING-PATTERNS.md)
- [Session logs](docs/session-logs/)
