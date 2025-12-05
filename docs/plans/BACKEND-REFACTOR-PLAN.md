---
title: "Backend Refactor Plan - LANO CRM"
id: "BACKEND-REFACTOR-PLAN-01"
project: "LanoCRM"
version: "2.1"
status: "In Progress (Phase 2)"
last_updated: "2025-11-23"
type: "Project Plan"
tags: ["plan", "backend", "refactoring", "modules", "architecture", "roadmap"]
purpose: "Outlines the comprehensive plan to refactor the LANO CRM backend to clean architecture and build 9 essential new modules."
location: "docs/plans"
related_to:
  - id: "AGENT-GUIDE-01"
  - id: "REFACTOR-001"
    description: "ProductService refactoring - completed reference implementation"
  - id: "TASK-001"
    description: "Inventory module - in progress foundation"
  - id: "PRICE-LIST-001"
    description: "Price list formulas - completed feature"
  - id: "DOC-AUDIT-2025-11-26"
    description: "Documentation audit report with connectivity analysis"
    description: "All development and refactoring must follow the rules and patterns in this guide."
---

# Backend Refactor Plan - LANO CRM

**Version:** 2.1
**Updated:** 2025-11-23
**Status:** In Progress (Phase 2)

---

## 🎯 MỤC TIÊU TỔNG QUAN

Refactor backend LANO CRM từ fat controllers sang clean architecture, đồng thời xây dựng 9 modules mới thiết yếu.

### Thành quả kỳ vọng:
- ✅ Code maintainable, testable, reusable
- ✅ AI-friendly architecture (agents dễ hiểu và maintain)
- ✅ Test coverage 80%+
- ✅ Performance tối ưu
- ✅ 9 modules mới production-ready

---

## 📊 HIỆN TRẠNG DỰ ÁN

### ✅ Đã hoàn thành

- **Auth Module:** 100% (JWT, roles, permissions)
- **Users Module:** 100% (CRUD, roles assignment)
- **Products Module:** Refactored to clean architecture
- **REFACTOR-001:** ProductService extraction (Completed 2025-11-21)
- **Price Lists Module:** Complete with pricing engine (Completed 2025-11-23)
- **Inventory Module:** Foundation layer implemented (2025-11-21)

### 🔄 Đang thực hiện

- **Inventory Module:** Advanced features (valuation, alerts)
- **Image/Media Library:** Anti-duplicate system

### 📋 Chưa bắt đầu

- REFACTOR-002: VariantService
- REFACTOR-003: AttributeService
- Customer Relationship Management (CRM)
- Orders & Sales (partial - có order API cơ bản)
- Point of Sale (POS)
- Partners & Suppliers
- Finance & Accounting
- Reporting & Analytics
- Notifications
- Third-party Integrations

---

## 📋 DEVELOPMENT PHASES

### Phase 1: Foundation (Completed)

Refactor existing modules to clean architecture patterns.

**Completed:**
- REFACTOR-001: ProductService extraction
- Price Lists Module with pricing engine
- Inventory Module foundation

**Pending:**
- REFACTOR-002: VariantService
- REFACTOR-003: AttributeService

### Phase 2: Core Business Modules

Essential modules for business operations.

**Priority modules:**
- Inventory Management (advanced features)
- Customer Relationship Management
- Orders & Sales (complete implementation)
- Point of Sale (POS)

### Phase 3: Supply Chain & Finance

Backend support for operations.

- Partners & Suppliers Management
- Finance & Accounting
- Reporting & Analytics

### Phase 4: Extensions

Nice-to-have features.

- Notifications System
- Third-party Integrations (Payment gateways, Shipping, etc.)

---

## 📋 TASK SUMMARY

### Refactor Tasks (Phase 1)

| ID | Module | Priority | Status |
|----|--------|----------|--------|
| REFACTOR-001 | ProductService | High | ✅ Completed (2025-11-21) |
| REFACTOR-002 | VariantService | Medium | 📋 Not Started |
| REFACTOR-003 | AttributeService | Medium | 📋 Not Started |

### New Module Tasks

| ID | Module | Priority | Dependencies | Status |
|----|--------|----------|--------------|--------|
| PRICE-001 | Price Lists | High | REFACTOR-001 | ✅ Completed (2025-11-23) |
| TASK-001 | Inventory | Critical | REFACTOR-001 | 🚧 In Progress (foundation done) |
| TASK-002 | Customers (CRM) | Critical | None | 📋 Not Started |
| TASK-003 | Orders | High | TASK-001, TASK-002 | 🚧 Partial (basic API exists) |
| TASK-004 | POS | High | TASK-001, TASK-003 | 📋 Not Started |
| TASK-005 | Partners/Suppliers | High | TASK-001 | 📋 Not Started |
| TASK-006 | Finance | Medium | TASK-003, TASK-005 | 📋 Not Started |
| TASK-007 | Reporting | Medium | All above | 📋 Not Started |
| TASK-008 | Notifications | Low | None | 📋 Not Started |
| TASK-009 | Integrations | Medium | TASK-003, TASK-004 | 📋 Not Started |

---

## 🔗 DEPENDENCIES

REFACTOR-001 (Products)
↓
TASK-001 (Inventory) ← CRITICAL PATH
↓
TASK-003 (Orders)
↓
TASK-004 (POS)
↓
TASK-006 (Finance)
↓
TASK-007 (Reporting)

TASK-002 (Customers) → TASK-003 (Orders)
TASK-005 (Suppliers) → TASK-006 (Finance)
TASK-008 (Notifications) - Parallel (anytime)
TASK-009 (Integrations) → TASK-003, TASK-004
---

## 🎨 KIẾN TRÚC CHUẨN (Áp dụng cho tất cả modules)

Controller Layer (Routing only)
↓
Service Layer (Business Logic)
↓
Repository Layer (Database)
↓
Model Layer (Schema definition - passive)

text---

## 🎨 KIẾN TRÚC CHUẨN (Áp dụng cho tất cả modules)

Controller Layer (Routing only)
↓
Service Layer (Business Logic)
↓
Repository Layer (Database)
↓
Model Layer (Schema definition - passive)

### File Structure Template:
app/
├── Controllers/Api/
│ └── {Module}Controller.php (~100-250 dòng)
├── Services/{Module}/
│ ├── {Module}Service.php (~200-500 dòng)
│ └── {Feature}Service.php (nếu cần tách)
├── Repositories/{Module}/
│ └── {Module}Repository.php (~150-400 dòng)
├── Validators/
│ └── {Module}Validator.php (~60-200 dòng)
└── Models/
└── {Module}Model.php (passive, chỉ schema)

tests/
├── Services/
│ └── {Module}ServiceTest.php
└── Repositories/
└── {Module}RepositoryTest.php

---

## ✅ DEFINITION OF DONE (Mỗi task)

- [ ] Files tạo đúng structure
- [ ] Tuân thủ clean architecture
- [ ] Inline docs đầy đủ (@agent- tags)
- [ ] Unit tests viết và pass
- [ ] Integration tests pass
- [ ] API endpoints test thủ công OK
- [ ] Pre-commit checks pass
- [ ] Session log tạo
- [ ] Commit đúng format
- [ ] Code review (nếu có team)

---

## 🚀 GETTING STARTED

**For AI Agents:** Start with [AGENTS.md](../../AGENTS.md) - Complete development guide

**Pick a task:**

1. Review task file in `docs/tasks/`
2. Follow workflow in AGENTS.md
3. Create session log when done

**Monitor progress:** Check `docs/session-logs/` for completed work

---

## 📝 TRACKING

- **Session logs:** Create `docs/session-logs/YYYY-MM-DD-{task-name}.md` after each task
- **Git commits:** Follow proper format with descriptive messages
- **Task status:** Update this file's task table when completing tasks

---

## 🎯 SUCCESS CRITERIA

### Code Quality

- All modules follow clean architecture
- Test coverage 70%+ for Services & Repositories
- Zero critical bugs
- Performance: API response < 200ms (average)

### Functionality

- All planned modules working correctly
- Integration between modules stable
- Frontend integration intact
- Backward compatible with existing code

### Documentation

- Inline docs complete (@agent- tags)
- Session logs for each task
- Task files updated with status

---

## 🔧 TECH STACK

- **Backend:** PHP 8.4 + CodeIgniter 4.5
- **Database:** MySQL 8.4
- **Testing:** PHPUnit 10
- **Docker:** meomeo2 containers
- **AI Development:** Claude Code, Cursor, etc.

---

## 📌 KEY PRINCIPLES

- **Quality > Speed:** Better slow and correct than fast and broken
- **Copy successful patterns:** REFACTOR-001 is the template
- **Test everything:** No merge without tests
- **Document as you go:** Inline docs + session logs mandatory
