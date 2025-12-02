---
title: "LanoCRM Documentation Index"
id: "DOCUMENTATION-INDEX"
priority: "P0"
status: "Active"
module: "Documentation"
type: "Navigation"
tags: ["documentation", "index", "navigation", "links"]
purpose: "Central navigation hub for all LanoCRM documentation with cross-references and quick access"
location: "docs"

# Relationships
dependencies: ""
related_to: "AGENT-GUIDE-01, TESTING-GUIDE-01, BUSINESS-DECISIONS-01"
implements: "DOCUMENTATION-STANDARDS"
part_of: "PROJECT-DOCUMENTATION"

# Metadata
author: "Documentation Team"
created_date: "2025-11-27"
last_updated: "2025-11-27"
version: "1.0"
estimated_effort: "1 day"
actual_effort: "1 day"
complexity: "Low"
risk_level: "Low"

# Documentation Network
links_to: ["AGENT-GUIDE-01", "TESTING-GUIDE-01", "BUSINESS-DECISIONS-01", "TESTING-PATTERNS-01", "TESTING-MAIN-DB-01"]
linked_from: []
---

# 📚 LanoCRM Documentation Index

**Last Updated:** 2025-11-27  
**Version:** 1.0  
**Purpose:** Central navigation hub for all project documentation

---

## 🎯 QUICK ACCESS

### 🚀 Getting Started
- **[AGENTS.md](./AGENTS.md)** - AI Agent Guide & Architecture Patterns
- **[.ai/instructions.md](./.ai/instructions.md)** - Development Instructions & Standards
- **[README.md](./README.md)** - Project Overview & Setup

### 📋 Active Tasks
- **[Tasks Dashboard](./tasks/)** - Current development tasks
- **[Main Modules](./tasks/MAIN_MODULES/)** - Core module specifications
- **[Frontend Tasks](./tasks/FEOrder.md)** - Frontend requirements

### 🧪 Testing Documentation
- **[Testing Guide](./testing/TESTING-GUIDE.md)** - MySQL-only testing with production migrations (tests group) + rollback
- **[Test Patterns](./testing/TESTING-PATTERNS.md)** - Copy-pasteable test patterns (DevDatabaseTrait + truncate-only)
- **[Main DB Guide](./testing/TESTING-MAIN-DB-GUIDE.md)** - MySQL main DB usage & rollback model
- **[Test Checklist](./testing/TEST-CHECKLIST.md)** - Mandatory testing checklist
- **[Frontend Testing](./testing/FE-TESTING-GUIDE.md)** - Frontend testing strategy

### 🔗 ERPNext Integration
- **[ERPNext Integration Hub](./erpnext/README.md)** - Liên kết phân tích và kế hoạch ERPNext

---

## 📊 DOCUMENTATION CATEGORIES

### 🏗️ Architecture & Standards
| Document | ID | Status | Description |
|----------|-----|--------|-------------|
| [AGENTS.md](./AGENTS.md) | AGENT-GUIDE-01 | ✅ Active | Clean architecture patterns & AI agent guidelines |
| [Business Decisions](./tasks/MAIN_MODULES/01_BUSINESS_DECISIONS.md) | BUSINESS-DECISIONS-01 | ✅ Final | 39 approved business decisions for Order Workflow |
| [Backend Refactor Plan](./docs/plans/BACKEND-REFACTOR-PLAN.md) | BACKEND-REFACTOR-PLAN-01 | ✅ Active | Overall refactoring roadmap and strategy |

### 📋 Tasks & Implementation
| Category | Documents | Status |
|----------|------------|--------|
| **Completed Tasks** | [Task Directory](./tasks/DONE/) | ✅ Multiple completed |
| **Active Tasks** | [Current Tasks](./tasks/) | 🚧 In Progress |
| **Frontend Tasks** | [FEOrder](./tasks/FEOrder.md), [FE-CASH](./tasks/FE-CASH.md) | 📋 Requirements |
| **Main Modules** | [Module Specs](./tasks/MAIN_MODULES/) | 🚧 Development |

### 🧪 Testing & Quality
| Document | ID | Status | Coverage |
|----------|-----|--------|----------|
| [Testing Guide](./testing/TESTING-GUIDE.md) | TESTING-GUIDE-01 | ✅ Active | MySQL-only approach |
| [Test Patterns](./testing/TESTING-PATTERNS.md) | TESTING-PATTERNS-01 | ✅ Active | Copy-pasteable patterns (DevDatabaseTrait + truncate-only) |
| [Main DB Guide](./testing/TESTING-MAIN-DB-GUIDE.md) | TESTING-MAIN-DB-01 | ✅ Active | Golden migration + rollback |
| [Frontend Testing](./testing/FE-TESTING-GUIDE.md) | FE-TESTING-GUIDE-01 | ✅ Active | FE testing strategy |
| [Test Checklist](./testing/TEST-CHECKLIST.md) | TEST-CHECKLIST-01 | ✅ Active | Mandatory checklist |

### 📊 Audits & Reviews
| Document | ID | Status | Focus |
|----------|-----|--------|-------|
| [Documentation Audit](./audits/2025-11-26_Documentation_Audit_Report.md) | DOC-AUDIT-2025-11-26 | ✅ Complete | Documentation connectivity |
| [Cash Flow Audit](./audits/2025-11-27-CASH-FLOW-AUDIT-REPORT.md) | CASH-AUDIT-2025-11-27 | ✅ Complete | Cash management module |
| [Integration Audit](./audits/2025-11-27-CASH-FLOW-INTEGRATION-AUDIT-REPORT.md) | INT-AUDIT-2025-11-27 | ✅ Complete | FE-BE integration |
| [Final Test Coverage](./audits/2025-11-27-FINAL-TEST-COVERAGE-REPORT.md) | COVERAGE-2025-11-27 | ✅ Complete | Coverage checkpoint |
| [Test Schema Debug](./audits/2025-11-27-TEST-SCHEMA-INSTABILITY-DEBUG-REPORT.md) | TEST-SCHEMA-2025-11-27 | ✅ Complete | Schema instability analysis |

---

## 🔗 CROSS-REFERENCE MAP

### Task Dependencies
```
REFACTOR-001-PRODUCT-SERVICE
├── TASK-001-INVENTORY-MODULE
├── IMPORT-EXPORT-001-PRODUCTS-EXCEL
└── CASH-001-CASH-MANAGEMENT

TASK-05-ORDER-CREATE-CI4
├── TASK-07-INVENTORY-HOOKS-CI4
├── TASK-08-CANCEL-ORDER-CI4
└── ORD-003-ORDER-STATUS-MANAGEMENT

CASH-001-CASH-MANAGEMENT
├── FE-CASH-CASH-MANAGEMENT
└── FEORDER-ORDER-MANAGEMENT
```

### Documentation Flow
```
AGENT-GUIDE-01
├── All implementation tasks
├── Testing documentation
└── Architecture decisions

BUSINESS-DECISIONS-01
├── TASK-05-ORDER-CREATE-CI4
├── TASK-07-INVENTORY-HOOKS-CI4
└── All order workflow tasks

TESTING-GUIDE-01
├── TESTING-PATTERNS-01
├── FE-TESTING-GUIDE-01
└── TEST-CHECKLIST-01
```

---

## 📋 DOCUMENTATION STANDARDS

### YAML Frontmatter Template
All documentation files must use the [YAML Frontmatter Template](./templates/YAML_FRONTMATTER_TEMPLATE.md) with these required fields:
- `title`, `id`, `priority`, `status`, `module`, `type`
- `purpose`, `location`, `created_date`, `last_updated`
- `dependencies`, `related_to`, `links_to`, `linked_from`

### ID Format Standards
- **Tasks:** `MODULE-TYPE-NNN` (e.g., `TASK-001-INVENTORY-MODULE`)
- **Documentation:** `CATEGORY-NAME-VERSION` (e.g., `TESTING-GUIDE-01`)
- **Sessions:** `SESSION-YYYY-MM-DD-TASK-NAME`
- **Audits:** `AUDIT-TYPE-YYYY-MM-DD`

### Status Values
- **Tasks:** `Backlog`, `In Progress`, `Done`, `Blocked`
- **Documentation:** `Active`, `Deprecated`, `Draft`
- **Code:** `Not Started`, `In Progress`, `Done`, `Blocked`

---

## 🚀 NAVIGATION TIPS

### For Developers
1. **Start with** [AGENTS.md](./AGENTS.md) for architecture patterns
2. **Check** [BUSINESS-DECISIONS-01](./tasks/MAIN_MODULES/01_BUSINESS_DECISIONS.md) for business rules
3. **Follow** [TESTING-GUIDE-01](./testing/TESTING-GUIDE.md) for testing requirements
4. **Use** [TESTING-PATTERNS-01](./testing/TESTING-PATTERNS.md) for code templates

### For Frontend Developers
1. **Read** [FEOrder.md](./tasks/FEOrder.md) for order management UI
2. **Check** [FE-CASH.md](./tasks/FE-CASH.md) for cash management UI
3. **Follow** [FE-TESTING-GUIDE-01](./testing/FE-TESTING-GUIDE.md) for testing patterns

### For QA/Testers
1. **Use** [TEST-CHECKLIST-01](./testing/TEST-CHECKLIST.md) for mandatory checks
2. **Apply** [TESTING-PATTERNS-01](./testing/TESTING-PATTERNS.md) for test templates
3. **Reference** [FE-TESTING-PATTERNS-01](./testing/FE-TESTING-PATTERNS.md) for frontend tests

---

## 📊 DOCUMENTATION METRICS

### Coverage Analysis
- **Tasks with YAML:** 40% (target: 100%)
- **Documents with cross-references:** 60% (target: 90%)
- **Test coverage documentation:** 80% (target: 95%)
- **Frontend documentation:** 30% (target: 80%)

### Quality Metrics
- **Template compliance:** 70% (target: 100%)
- **Link accuracy:** 85% (target: 95%)
- **Version consistency:** 90% (target: 100%)

---

## 🔄 MAINTENANCE

### Weekly Updates
- Update task statuses
- Add new documentation to index
- Verify cross-reference links
- Update metrics

### Monthly Reviews
- Audit documentation coverage
- Review template compliance
- Update navigation structure
- Check for deprecated content

---

## 📞 SUPPORT & CONTRIBUTIONS

### How to Update Documentation
1. Use [YAML Frontmatter Template](./templates/YAML_FRONTMATTER_TEMPLATE.md)
2. Update cross-references in related files
3. Add new documents to this index
4. Update metrics and navigation

### Contact Points
- **Documentation Issues:** Create GitHub issue with `documentation` label
- **Template Questions:** Reference [AGENTS.md](./AGENTS.md)
- **Navigation Problems:** Update this index file

---

**Last Updated:** 2025-11-27  
**Next Review:** 2025-12-04  
**Maintainers:** Documentation Team
